<?php

namespace App\Filament\Pages;

use App\Models\MaterialRequest;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Filament\Pages\Page;

class WarehouseDashboard extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'لوحة مدير المخازن';

    protected static ?string $title = 'لوحة مدير المخازن';

    protected static string|\UnitEnum|null $navigationGroup = 'المخزون';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.warehouse-dashboard';

    public int $pendingMaterialRequests = 0;

    public int $pendingFinishedReceipts = 0;

    public array $lowStock = [];

    public array $recentMovements = [];

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return $user && ($user->hasRole('warehouse_manager') || $user->hasRole('admin'));
    }

    public static function canAccess(): bool
    {
        return static::shouldRegisterNavigation();
    }

    public function mount(): void
    {
        $this->pendingMaterialRequests = MaterialRequest::where('status', MaterialRequest::STATUS_PENDING)->count();
        $this->pendingFinishedReceipts = \App\Models\FinishedReceipt::where('status', \App\Models\FinishedReceipt::STATUS_PENDING)->count();

        $this->lowStock = StockBalance::query()
            ->with(['warehouse', 'stockable'])
            ->where('qty_on_hand', '<=', 50)
            ->orderBy('qty_on_hand')
            ->limit(8)
            ->get()
            ->map(fn (StockBalance $b) => [
                'warehouse' => $b->warehouse?->name,
                'item'      => $b->stockable?->name ?? '—',
                'qty'       => $b->qty_on_hand,
            ])
            ->all();

        $this->recentMovements = StockMovement::query()
            ->with(['warehouse', 'stockable', 'user'])
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn ($m) => [
                'at'        => $m->created_at?->format('Y-m-d H:i'),
                'warehouse' => $m->warehouse?->name,
                'type'      => $m->type,
                'item'      => $m->stockable?->name ?? '—',
                'qty'       => $m->qty,
                'user'      => $m->user?->name ?? '—',
            ])
            ->all();
    }
}
