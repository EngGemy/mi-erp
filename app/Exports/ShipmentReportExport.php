<?php

namespace App\Exports;

use App\Models\Project;
use App\Services\ShipmentReportService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ShipmentReportExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize, WithTitle
{
    protected array $data;

    public function __construct(protected Project $project)
    {
        $this->data = app(ShipmentReportService::class)->build($project);
    }

    public function title(): string
    {
        return 'تقرير الحمولات';
    }

    public function headings(): array
    {
        return [
            'اسم الحمولة', 'التاريخ', 'السائق', 'رقم السيارة', 'المسؤول', 'موعد الوصول',
            'عدد الأصناف', 'إجمالي الكمية', 'نسبة المساهمة %', 'ملاحظات',
        ];
    }

    public function array(): array
    {
        return collect($this->data['shipments'])->map(fn ($s) => [
            $s['name'],
            $s['shipped_at'],
            $s['driver_name'],
            $s['vehicle_no'],
            $s['responsible'],
            $s['arrival_time'],
            $s['items_count'],
            $s['total_qty'],
            $s['contribution_pct'],
            $s['notes'],
        ])->toArray();
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
