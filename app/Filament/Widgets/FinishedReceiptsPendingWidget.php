<?php

namespace App\Filament\Widgets;

use App\Services\DashboardStatsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinishedReceiptsPendingWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 6;

    public static function canView(): bool
    {
        return MaterialRequestsPendingWidget::canView();
    }

    protected function getStats(): array
    {
        $c = app(DashboardStatsService::class)->pendingCounts();

        return [
            Stat::make('استلام تام', (string) $c['finished_receipts'])->color('info'),
        ];
    }
}
