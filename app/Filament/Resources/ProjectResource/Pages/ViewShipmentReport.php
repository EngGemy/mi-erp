<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Concerns\AuthorizesCrownPage;
use App\Exports\ShipmentReportExport;
use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use App\Services\ShipmentReportService;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;

class ViewShipmentReport extends Page
{
    use AuthorizesCrownPage;

    protected static string $resource = ProjectResource::class;

    protected static function crownPagePermission(): ?string
    {
        return 'View:ViewShipmentReport';
    }

    protected string $view = 'filament.pages.view-shipment-report';

    public function getTitle(): string
    {
        return 'تقرير الحمولات';
    }

    public Project $record;

    public array $shipments = [];

    public float $projectTotalDelivered = 0;

    public ?int $expandedShipmentId = null;

    public function mount(Project $record): void
    {
        $this->record = $record;
        $this->loadReport();
    }

    public function loadReport(): void
    {
        $data = app(ShipmentReportService::class)->build($this->record);
        $this->shipments = $data['shipments'];
        $this->projectTotalDelivered = $data['project_total_delivered'];
    }

    public function toggleShipment(int $shipmentId): void
    {
        $this->expandedShipmentId = $this->expandedShipmentId === $shipmentId ? null : $shipmentId;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportExcel')
                ->label('تصدير Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => Excel::download(
                    new ShipmentReportExport($this->record),
                    'shipments_'.$this->record->code.'.xlsx'
                )),

            Action::make('exportPdf')
                ->label('تصدير PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(fn () => route('shipment-report.print', ['project' => $this->record->id]))
                ->openUrlInNewTab(),
        ];
    }
}
