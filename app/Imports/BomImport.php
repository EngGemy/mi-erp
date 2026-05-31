<?php

namespace App\Imports;

use App\Models\Item;
use App\Models\Project;
use App\Models\Section;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/**
 * يستورد/يحدّث الأصناف من ملف Excel.
 * الأعمدة المتوقعة (heading row): code, name, section, length,
 * formula, scrap_mode, scrap_percent, scrap_fixed, rounding, sort, unit, notes
 * المطابقة على (project_id, code): يُحدّث إن وُجد، يُنشأ إن لم يوجد (Upsert).
 */
class BomImport implements ToCollection, WithHeadingRow
{
    public function __construct(protected Project $project) {}

    public function collection(Collection $rows): void
    {
        $sort = (int) $this->project->items()->max('sort');

        foreach ($rows as $row) {
            $code = trim((string) ($row['code'] ?? ''));
            if ($code === '') {
                continue;
            }

            // القسم: إنشاء عند الحاجة
            $sectionId = null;
            $sectionName = trim((string) ($row['section'] ?? ''));
            if ($sectionName !== '') {
                $section = Section::firstOrCreate(
                    ['project_id' => $this->project->id, 'name' => $sectionName],
                );
                $sectionId = $section->id;
            }

            $scrapMode = trim((string) ($row['scrap_mode'] ?? 'inherit')) ?: 'inherit';

            Item::updateOrCreate(
                ['project_id' => $this->project->id, 'code' => $code],
                [
                    'section_id'    => $sectionId,
                    'name'          => trim((string) ($row['name'] ?? $code)),
                    'piece_length'  => $this->num($row['length'] ?? null),
                    'unit'          => $row['unit'] ?? null,
                    'formula'       => $row['formula'] ?? null,
                    'scrap_mode'    => in_array($scrapMode, ['inherit','percent','fixed','formula','none']) ? $scrapMode : 'inherit',
                    'scrap_percent' => $this->num($row['scrap_percent'] ?? null),
                    'scrap_fixed'   => $this->num($row['scrap_fixed'] ?? null),
                    'rounding'      => trim((string) ($row['rounding'] ?? 'up')) ?: 'up',
                    'sort'          => $row['sort'] ?? ++$sort,
                    'notes'         => $row['notes'] ?? null,
                    'is_active'     => true,
                ]
            );
        }
    }

    protected function num($v): ?float
    {
        if ($v === null || $v === '') {
            return null;
        }
        return is_numeric($v) ? (float) $v : null;
    }
}
