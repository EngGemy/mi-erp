<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectItemProgress;
use App\Models\Stage;
use Illuminate\Support\Collection;

/**
 * تجميع نسبة الإكتمال Bottom-up (WBS).
 * المطلوب لكل صنف = required_override أو total (H) من BomEngine — دون تعديل المحرك.
 */
class ProgressService
{
    public function __construct(protected BomEngine $engine) {}

    /**
     * @return array{
     *   project_pct: float,
     *   sections: array<int, array>
     * }
     */
    public function rollup(Project $project): array
    {
        $project = $project->fresh(['sections.items', 'variables']);

        $stages = Stage::query()->orderBy('sort')->get();
        $stageWeights = $this->normalizeStageWeights($stages);

        $bom = collect($this->engine->calculate($project))->keyBy('code');

        $progressRows = ProjectItemProgress::query()
            ->whereIn('item_id', $project->items->pluck('id'))
            ->get()
            ->groupBy('item_id');

        $sectionsOut = [];
        $sectionEntries = [];

        foreach ($project->sections->sortBy('sort') as $section) {
            $itemEntries = [];
            $itemsOut = [];

            foreach ($section->items->where('is_active', true)->sortBy('sort') as $item) {
                $bomRow = $bom->get($item->code);
                $required = $item->required_override !== null
                    ? (float) $item->required_override
                    : (float) ($bomRow['total'] ?? 0);

                $byStage = ($progressRows[$item->id] ?? collect())->keyBy('stage_id');
                $itemPct = $this->itemPercent($required, $stages, $stageWeights, $byStage);

                $stagesOut = [];
                foreach ($stages as $stage) {
                    $stagesOut[] = [
                        'stage_id'   => $stage->id,
                        'name'       => $stage->name,
                        'weight'     => $stageWeights[$stage->id] ?? 1,
                        'done_qty'   => (float) ($byStage[$stage->id]->done_qty ?? 0),
                    ];
                }

                $itemsOut[] = [
                    'item_id'  => $item->id,
                    'code'     => $item->code,
                    'name'     => $item->name,
                    'required' => round($required, 2),
                    'pct'      => $itemPct,
                    'stages'   => $stagesOut,
                ];

                $itemEntries[] = [
                    'pct'    => $itemPct,
                    'weight' => $item->weight,
                ];
            }

            $sectionPct = $this->weightedPercent($itemEntries);
            $sectionsOut[] = [
                'section_id' => $section->id,
                'name'       => $section->name,
                'pct'        => $sectionPct,
                'weight'     => $section->weight,
                'items'      => $itemsOut,
            ];

            $sectionEntries[] = [
                'pct'    => $sectionPct,
                'weight' => $section->weight,
            ];
        }

        $projectPct = $this->weightedPercent($sectionEntries);

        $project->update(['progress_cached' => $projectPct]);

        return [
            'project_pct' => $projectPct,
            'sections'      => $sectionsOut,
        ];
    }

    public function ensureProgressRows(Project $project): void
    {
        $stageIds = Stage::query()->pluck('id');
        $itemIds = $project->items()->pluck('id');

        foreach ($itemIds as $itemId) {
            foreach ($stageIds as $stageId) {
                ProjectItemProgress::firstOrCreate(
                    ['item_id' => $itemId, 'stage_id' => $stageId],
                    ['done_qty' => 0]
                );
            }
        }
    }

    /**
     * @param  array<int, float|null>  $rawWeights  keyed by stage id via parallel list — we pass normalized map
     */
    protected function itemPercent(
        float $required,
        Collection $stages,
        array $stageWeights,
        Collection $byStage
    ): float {
        if ($required <= 0) {
            return 0.0;
        }

        $acc = 0.0;
        $totalW = 0.0;

        foreach ($stages as $stage) {
            $w = $stageWeights[$stage->id] ?? 1.0;
            $totalW += $w;
            $done = (float) ($byStage[$stage->id]->done_qty ?? 0);
            $ratio = min(1.0, max(0.0, $done / $required));
            $acc += $w * $ratio;
        }

        if ($totalW <= 0) {
            return 0.0;
        }

        return round(($acc / $totalW) * 100, 2);
    }

    /**
     * @param  array<int, array{pct: float, weight: float|null}>  $entries
     */
    protected function weightedPercent(array $entries): float
    {
        if ($entries === []) {
            return 0.0;
        }

        $weights = [];
        foreach ($entries as $e) {
            $weights[] = ($e['weight'] !== null && (float) $e['weight'] > 0)
                ? (float) $e['weight']
                : 1.0;
        }

        $sumW = array_sum($weights);
        if ($sumW <= 0) {
            return 0.0;
        }

        $acc = 0.0;
        foreach ($entries as $i => $e) {
            $w = $weights[$i];
            $acc += $w * min(100.0, max(0.0, (float) $e['pct']));
        }

        return round($acc / $sumW, 2);
    }

    /**
     * @return array<int, float> stage_id => effective weight
     */
    protected function normalizeStageWeights(Collection $stages): array
    {
        $result = [];
        $sum = 0.0;

        foreach ($stages as $stage) {
            $w = (float) $stage->weight;
            $result[$stage->id] = $w;
            if ($w > 0) {
                $sum += $w;
            }
        }

        if ($sum <= 0) {
            foreach ($stages as $stage) {
                $result[$stage->id] = 1.0;
            }
        }

        return $result;
    }
}
