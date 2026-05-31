<?php

namespace App\Console\Commands;

use App\Models\Project;
use App\Models\User;
use App\Services\BomEngine;
use App\Services\CrownSettings;
use App\Services\CrownThemeResolver;
use App\Support\CrownThemePalettes;
use Illuminate\Console\Command;

class VerifyThemePreferences extends Command
{
    protected $signature = 'crown:verify-theme';

    protected $description = 'اختبار تخصيص الهوية والمظهر + leg_post H';

    public function handle(BomEngine $engine): int
    {
        $this->call('migrate', ['--force' => true]);

        $project = Project::query()->where('code', 'CROWN-FATTEN')->first();
        if (! $project) {
            $this->error('مشروع CROWN-FATTEN غير موجود');

            return self::FAILURE;
        }

        $h = (float) (collect($engine->calculate($project->fresh('variables')))->firstWhere('code', 'leg_post')['total'] ?? 0);
        $okH = abs($h - 2424) <= 2;
        $this->line(sprintf('leg_post H = %s → %s', $h, $okH ? 'OK' : 'FAIL'));

        CrownSettings::set('default_theme_color', 'calm_blue');
        $orgKey = CrownThemeResolver::organizationColorKey();
        $okOrg = $orgKey === 'calm_blue';
        $this->line(sprintf('افتراضي المؤسسة calm_blue → %s', $okOrg ? 'OK' : 'FAIL'));

        $userA = User::where('email', 'production@crown-bom.test')->first();
        $userB = User::where('email', 'viewer@crown-bom.test')->first();
        if (! $userA || ! $userB) {
            $this->error('مستخدمي الاختبار غير موجودين');

            return self::FAILURE;
        }

        $userA->update(['theme_color' => 'teal', 'theme_mode' => 'dark']);
        $userB->update(['theme_color' => null, 'theme_mode' => 'system']);

        $okA = CrownThemeResolver::resolveColorKey($userA) === 'teal'
            && CrownThemeResolver::resolveThemeMode($userA) === 'dark';
        $okB = CrownThemeResolver::resolveColorKey($userB) === 'calm_blue';
        $this->line(sprintf('مستخدم بتفضيل teal/dark → %s', $okA ? 'OK' : 'FAIL'));
        $this->line(sprintf('مستخدم بدون تفضيل يرث calm_blue → %s', $okB ? 'OK' : 'FAIL'));

        $palette = CrownThemePalettes::filamentPrimary('teal');
        $okPalette = isset($palette[600], $palette[50], $palette[950]);
        $this->line(sprintf('لوحة teal 50-950 → %s', $okPalette ? 'OK' : 'FAIL'));

        CrownSettings::set('default_theme_color', CrownThemePalettes::DEFAULT_KEY);

        $allOk = $okH && $okOrg && $okA && $okB && $okPalette;

        return $allOk ? self::SUCCESS : self::FAILURE;
    }
}
