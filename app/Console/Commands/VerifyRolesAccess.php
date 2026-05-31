<?php

namespace App\Console\Commands;

use App\Filament\Resources\CatalogItemResource;
use App\Filament\Resources\ProjectResource\Pages\ViewBom;
use App\Filament\Resources\ProjectResource\Pages\ViewShortage;
use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Services\BomEngine;
use App\Models\Project;
use Illuminate\Console\Command;

class VerifyRolesAccess extends Command
{
    protected $signature = 'crown:verify-roles';

    protected $description = 'اختبار قبول الأدوار والصلاحيات';

    public function handle(BomEngine $engine): int
    {
        $project = Project::query()->where('code', 'CROWN-FATTEN')->first();
        $h = $project
            ? (float) (collect($engine->calculate($project->fresh('variables')))->firstWhere('code', 'leg_post')['total'] ?? 0)
            : 0;
        $okH = abs($h - 2424) <= 2;
        $this->line(sprintf('leg_post H = %s → %s', $h, $okH ? 'OK' : 'FAIL'));

        $checks = [
            ['admin@crown-bom.test', 'admin', [
                'ViewAny:User' => true,
                'ViewAny:CatalogItem' => true,
                'Update:CatalogItem' => true,
                'View:ViewBom' => true,
                'View:ViewShortage' => true,
            ]],
            ['logistics@crown-bom.test', 'logistics', [
                'ViewAny:User' => false,
                'Update:CatalogItem' => false,
                'View:ViewBom' => false,
                'View:ViewShortage' => true,
            ]],
            ['viewer@crown-bom.test', 'viewer', [
                'ViewAny:Project' => true,
                'Create:Project' => false,
                'Update:Project' => false,
                'View:ViewBom' => true,
            ]],
            ['production@crown-bom.test', 'production', [
                'View:ViewBom' => true,
                'View:ViewWbs' => true,
                'View:ViewShortage' => false,
                'Update:CatalogItem' => false,
            ]],
        ];

        $allOk = $okH;

        foreach ($checks as [$email, $role, $perms]) {
            $user = User::where('email', $email)->first();
            if (! $user) {
                $this->error("المستخدم {$email} غير موجود — شغّل CrownRolesSeeder");
                $allOk = false;

                continue;
            }

            $this->line("--- {$role} ({$email}) ---");
            foreach ($perms as $perm => $expected) {
                $actual = $user->can($perm);
                $ok = $actual === $expected;
                $allOk = $allOk && $ok;
                $this->line(sprintf('  %s → %s (متوقع %s) %s', $perm, $actual ? 'نعم' : 'لا', $expected ? 'نعم' : 'لا', $ok ? 'OK' : 'FAIL'));
            }

            auth()->login($user);
            $canBom = ViewBom::canAccess();
            $canUsers = UserResource::canViewAny();
            auth()->logout();

            $this->line(sprintf('  ViewBom::canAccess → %s', $canBom ? 'نعم' : 'لا'));
            $this->line(sprintf('  UserResource nav → %s', $canUsers ? 'نعم' : 'لا'));
        }

        $this->info($allOk ? 'اختبار الأدوار نجح.' : 'فشل اختبار.');

        return $allOk ? self::SUCCESS : self::FAILURE;
    }
}
