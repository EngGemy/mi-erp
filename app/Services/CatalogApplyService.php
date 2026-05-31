<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\CatalogSection;
use App\Models\Item;
use App\Models\Project;
use App\Models\Section;
use InvalidArgumentException;

class CatalogApplyService
{
    public const MODE_REPLACE = 'replace';

    public const MODE_SYNC = 'sync';

    /**
     * ينسخ أقسام وأصناف الكتالوج المركزي إلى مشروع (جدول items + catalog_item_id).
     *
     * @return array{sections: int, items: int}
     */
    public function applyFromCatalog(Project $project, string $mode = self::MODE_REPLACE): array
    {
        if (! in_array($mode, [self::MODE_REPLACE, self::MODE_SYNC], true)) {
            throw new InvalidArgumentException("وضع غير معروف: {$mode}");
        }

        $sectionMap = $this->ensureProjectSections($project);

        if ($mode === self::MODE_REPLACE) {
            Item::query()->where('project_id', $project->id)->delete();
        }

        $sort = 0;
        $itemCount = 0;

        $catalogItems = CatalogItem::query()
            ->where('is_active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        foreach ($catalogItems as $catalogItem) {
            $sectionId = $sectionMap[$catalogItem->catalog_section_id] ?? null;
            $existing = Item::query()
                ->where('project_id', $project->id)
                ->where('code', $catalogItem->code)
                ->first();

            $payload = $this->snapshotFromCatalog($catalogItem, $sectionId, $existing);
            $payload['sort'] = $sort++;

            Item::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'code'       => $catalogItem->code,
                ],
                $payload
            );

            $itemCount++;
        }

        return [
            'sections' => count($sectionMap),
            'items'    => $itemCount,
        ];
    }

    /**
     * @return array<int, int> catalog_section_id => project section_id
     */
    protected function ensureProjectSections(Project $project): array
    {
        $map = [];

        foreach (CatalogSection::query()->orderBy('sort')->get() as $catalogSection) {
            $map[$catalogSection->id] = Section::updateOrCreate(
                [
                    'project_id' => $project->id,
                    'name'       => $catalogSection->name,
                ],
                ['sort' => $catalogSection->sort]
            )->id;
        }

        return $map;
    }

    protected function snapshotFromCatalog(
        CatalogItem $catalogItem,
        ?int $sectionId,
        ?Item $existing = null
    ): array {
        $data = [
            'catalog_item_id' => $catalogItem->id,
            'section_id'      => $sectionId,
            'name'            => $catalogItem->name,
            'piece_length'    => $catalogItem->piece_length,
            'unit'            => $catalogItem->unit,
            'formula'         => $catalogItem->formula,
            'scrap_mode'      => $catalogItem->scrap_mode,
            'scrap_percent'   => $catalogItem->scrap_percent,
            'scrap_fixed'     => $catalogItem->scrap_fixed,
            'rounding'        => $catalogItem->rounding,
            'is_active'       => $catalogItem->is_active,
        ];

        if ($existing !== null) {
            $data['required_override'] = $existing->required_override;
            $data['notes'] = $existing->notes;
        }

        return $data;
    }
}
