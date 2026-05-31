<?php

namespace App\Filament\Widgets;

use App\Services\DashboardStatsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PurchaseOrdersPendingWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 7;

    public static function canView(): bool
    {
        return app(DashboardStatsService::class)->canViewPurchasing()
            || app(DashboardStatsService::class)->canViewInventory();
    }

    protected function getStats(): array
    {
        $c = app(DashboardStatsService::class)->pendingCounts();

        return [
            Stat::make('أوامر شراء للاستلام', (string) $c['purchase_orders'])->color('primary'),
        ];
    }
}
