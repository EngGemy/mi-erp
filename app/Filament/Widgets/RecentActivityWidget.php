<?php

namespace App\Filament\Widgets;

use App\Services\DashboardStatsService;
use Filament\Widgets\Widget;

class RecentActivityWidget extends Widget
{
    protected static ?int $sort = 8;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.recent-activity';

    public array $movements = [];

    public array $workOrders = [];

    public static function canView(): bool
    {
        $svc = app(DashboardStatsService::class);

        return $svc->canViewInventory() || auth()->user()?->can('ViewAny:WorkOrder') ?? false;
    }

    public function mount(): void
    {
        $svc = app(DashboardStatsService::class);
        if ($svc->canViewInventory()) {
            $this->movements = $svc->recentMovements(8);
        }
        if (auth()->user()?->can('ViewAny:WorkOrder')) {
            $this->workOrders = $svc->recentWorkOrders(8);
        }
    }
}
