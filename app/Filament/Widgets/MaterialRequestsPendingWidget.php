<?php

namespace App\Filament\Widgets;

use App\Services\DashboardStatsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MaterialRequestsPendingWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 5;

    protected ?string $heading = 'طلبات معلّقة';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && (
            $user->hasRole('warehouse_manager')
            || $user->hasRole('admin')
            || $user->can('ViewAny:MaterialRequest')
        );
    }

    protected function getStats(): array
    {
        $c = app(DashboardStatsService::class)->pendingCounts();

        return [
            Stat::make('صرف خام', (string) $c['material_requests'])->color('warning'),
        ];
    }
}
