<?php

namespace App\Filament\Widgets;

use App\Services\DashboardStatsService;
use Filament\Widgets\Widget;

class LowStockWidget extends Widget
{
    protected static ?int $sort = 4;

    protected string $view = 'filament.widgets.low-stock';

    public array $items = [];

    public static function canView(): bool
    {
        return app(DashboardStatsService::class)->canViewInventory();
    }

    public function mount(): void
    {
        $this->items = app(DashboardStatsService::class)->lowestRawStock(5);
    }
}
