<?php

namespace App\Exports;

use App\Models\Project;
use App\Services\ShortageService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ShortageExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected array $data;

    public function __construct(protected Project $project)
    {
        $this->data = app(ShortageService::class)->build($project);
    }

    public function title(): string
    {
        return 'النواقص';
    }

    public function headings(): array
    {
        $base = ['القسم', 'الكود', 'بيان الصنف', 'المطلوب'];
        foreach ($this->data['shipments'] as $sh) {
            $base[] = $sh['name'];
        }
        return array_merge($base, ['المُسلّم', 'الناقص', 'نسبة الإنجاز %', 'الحالة']);
    }

    public function array(): array
    {
        $statusLabels = [
            'complete' => 'مكتمل', 'partial' => 'جزئي', 'open' => 'لم يبدأ',
            'over' => 'زائد', 'none' => 'بلا مرجع',
        ];
        $shipments = $this->data['shipments'];

        return collect($this->data['rows'])->map(function ($r) use ($shipments, $statusLabels) {
            $row = [$r['section'] ?? '', $r['code'], $r['name'], $r['required']];
            foreach ($shipments as $sh) {
                $row[] = $r['matrix'][$sh['id']] ?? 0;
            }
            $row[] = $r['delivered'];
            $row[] = $r['shortage'];
            $row[] = $r['pct'];
            $row[] = $statusLabels[$r['status']] ?? $r['status'];
            return $row;
        })->toArray();
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '1A1A1A']],
            ],
        ];
    }
}
