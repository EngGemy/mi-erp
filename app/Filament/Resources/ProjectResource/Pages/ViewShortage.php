<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Exports\ShortageExport;
use App\Filament\Concerns\AuthorizesCrownPage;
use App\Filament\Concerns\ManagesActiveShipment;
use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;

class ViewShortage extends Page
{
    use AuthorizesCrownPage;
    use ManagesActiveShipment;

    protected static function crownPagePermission(): ?string
    {
        return 'View:ViewShortage';
    }

    protected static string $resource = ProjectResource::class;

    protected string $view = 'filament.pages.view-shortage';

    public function getTitle(): string
    {
        return 'متابعة النواقص';
    }

    public Project $record;

    public array $rows = [];

    public array $totals = [];

    public bool $showShipmentCols = false;

    public bool $showActiveShipmentQty = true;

    public function mount(Project $record): void
    {
        $this->record = $record;
        $this->initActiveShipment();
        $this->load();
    }

    public function load(): void
    {
        $data = app(\App\Services\ShortageService::class)->build($this->record, $this->activeShipmentId);
        $this->shipments = $data['shipments'];
        $this->rows = $data['rows'];
        $this->totals = $data['totals'];
        $this->shortageByItem = collect($data['rows'])->keyBy('item_id')->all();
    }

    protected function afterShipmentContextReloaded(): void
    {
        $data = app(\App\Services\ShortageService::class)->build($this->record, $this->activeShipmentId);
        $this->rows = $data['rows'];
        $this->totals = $data['totals'];
    }

    public function toggleShipmentCols(): void
    {
        $this->showShipmentCols = ! $this->showShipmentCols;
    }

    public function toggleActiveShipmentQty(): void
    {
        $this->showActiveShipmentQty = ! $this->showActiveShipmentQty;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('toggleActiveQty')
                ->label(fn () => $this->showActiveShipmentQty ? 'إخفاء عمود الحمولة الحالية' : 'إظهار عمود الحمولة الحالية')
                ->icon('heroicon-o-eye')
                ->action(fn () => $this->toggleActiveShipmentQty()),

            Action::make('toggleCols')
                ->label(fn () => $this->showShipmentCols ? 'إخفاء أعمدة الحمولات' : 'إظهار أعمدة الحمولات')
                ->icon('heroicon-o-view-columns')
                ->action(fn () => $this->toggleShipmentCols()),

            Action::make('export')
                ->label('تصدير Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => Excel::download(
                    new ShortageExport($this->record),
                    'shortage_'.$this->record->code.'.xlsx'
                )),
        ];
    }
}
