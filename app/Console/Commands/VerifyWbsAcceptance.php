<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Project;
use App\Models\ProjectItemProgress;
use App\Models\Stage;
use App\Services\BomEngine;
use App\Services\ProgressService;
use Illuminate\Console\Command;

class VerifyWbsAcceptance extends Command
{
    protected $signature = 'crown:verify-wbs';

    protected $description = 'اختبار قبول المرحلة 2 — WBS ونسبة الإكتمال';

    public function handle(BomEngine $engine, ProgressService $progress): int
    {
        $this->call('db:seed', ['--class' => 'StageSeeder', '--force' => true]);

        $project = Project::query()->where('code', 'CROWN-FATTEN')->first();
        if (! $project) {
            $this->error('مشروع CROWN-FATTEN غير موجود — شغّل CrownProjectSeeder أولاً.');

            return self::FAILURE;
        }

        $bom = collect($engine->calculate($project))->keyBy('code');
        $leg = $bom->get('leg_post');
        $h = (float) ($leg['total'] ?? 0);
        $okH = abs($h - 2424) <= 2;
        $this->line(sprintf('leg_post H = %s (متوقع 2424 ±2) → %s', $h, $okH ? 'OK' : 'FAIL'));

        $progress->ensureProgressRows($project);
        $stages = Stage::orderBy('sort')->get();
        $items = Item::where('project_id', $project->id)->where('is_active', true)->get();

        ProjectItemProgress::whereIn('item_id', $items->pluck('id'))->update(['done_qty' => 0]);
        $r0 = $progress->rollup($project->fresh());
        $okZero = abs($r0['project_pct']) < 0.01;
        $this->line(sprintf('مشروع صفر تقدّم → %s%% → %s', $r0['project_pct'], $okZero ? 'OK' : 'FAIL'));

        $sample = $items->first();
        $required = (float) ($bom->get($sample->code)['total'] ?? 0);
        foreach ($stages as $stage) {
            ProjectItemProgress::updateOrCreate(
                ['item_id' => $sample->id, 'stage_id' => $stage->id],
                ['done_qty' => $required]
            );
        }
        $rItem = $progress->rollup($project->fresh());
        $itemPct = collect($rItem['sections'])->flatMap(fn ($s) => $s['items'])->firstWhere('item_id', $sample->id)['pct'] ?? 0;
        $ok100 = abs($itemPct - 100) < 0.01;
        $this->line(sprintf('صنف %s بكل المراحل = المطلوب → %s%% → %s', $sample->code, $itemPct, $ok100 ? 'OK' : 'FAIL'));

        ProjectItemProgress::whereIn('item_id', $items->pluck('id'))->update(['done_qty' => 0]);
        $half = (int) ceil($items->count() / 2);
        foreach ($items->take($half) as $item) {
            $req = (float) ($bom->get($item->code)['total'] ?? 0);
            if ($req <= 0) {
                continue;
            }
            foreach ($stages as $stage) {
                ProjectItemProgress::updateOrCreate(
                    ['item_id' => $item->id, 'stage_id' => $stage->id],
                    ['done_qty' => $req]
                );
            }
        }
        $rHalf = $progress->rollup($project->fresh());
        $pctHalf = $rHalf['project_pct'];
        $okHalf = $pctHalf >= 45 && $pctHalf <= 55;
        $this->line(sprintf('نصف الأصناف منجزة (متساوية) → %s%% (متوقع ~50) → %s', $pctHalf, $okHalf ? 'OK' : 'FAIL'));

        $allOk = $okH && $okZero && $ok100 && $okHalf;
        $this->info($allOk ? 'جميع اختبارات القبول نجحت.' : 'فشل اختبار واحد أو أكثر.');

        return $allOk ? self::SUCCESS : self::FAILURE;
    }
}
