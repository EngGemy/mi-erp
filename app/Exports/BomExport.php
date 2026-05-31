<?php

namespace App\Exports;

use App\Models\Project;
use App\Services\BomEngine;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BomExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    public function __construct(protected Project $project) {}

    public function title(): string
    {
        return $this->project->name;
    }

    public function headings(): array
    {
        // نفس ترتيب أعمدة شيت كراون
        return [
            'القسم', 'الكود', 'بيان الصنف', 'طول القطعة',
            'الكمية الصافية (D)', 'الزيادة (E)', 'العدد بالزيادة (F)',
            'نسبة الزيادة % (G)', 'الإجمالي (H)', 'المعادلة', 'ملاحظات',
        ];
    }

    public function array(): array
    {
        $rows = app(BomEngine::class)->calculate(
            $this->project->fresh(['variables', 'items.section'])
        );

        $itemsByCode = $this->project->items->keyBy('code');

        return collect($rows)->map(function ($r) use ($itemsByCode) {
            $item = $itemsByCode->get($r['code']);
            return [
                $r['section'] ?? '',
                $r['code'],
                $r['name'],
                $r['length'],
                $r['net'],
                $r['scrap'],
                $r['gross'],
                $r['scrap_pct'],
                $r['total'],
                $item?->formula ?? '',
                $r['error'] ? ('خطأ: '.$r['error']) : ($item?->notes ?? ''),
            ];
        })->toArray();
    }

    public function styles(Worksheet $sheet): array
    {
        // تصميم أبيض/أسود (ماكينزي) - رأس غامق فقط
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1A1A1A']],
            ],
        ];
    }
}
