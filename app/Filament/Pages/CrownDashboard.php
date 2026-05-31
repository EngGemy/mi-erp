<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\FinishedReceiptsPendingWidget;
use App\Filament\Widgets\GlobalShortagesWidget;
use App\Filament\Widgets\LowStockWidget;
use App\Filament\Widgets\MaterialRequestsPendingWidget;
use App\Filament\Widgets\ProjectProgressWidget;
use App\Filament\Widgets\ProjectsOverviewWidget;
use App\Filament\Widgets\PurchaseOrdersPendingWidget;
use App\Filament\Widgets\RecentActivityWidget;
use Filament\Pages\Dashboard as BaseDashboard;

class CrownDashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'لوحة المؤشرات';

    protected static ?string $title = 'لوحة المؤشرات';

    /**
     * @return array<class-string>
     */
    public function getWidgets(): array
    {
        return [
            ProjectsOverviewWidget::class,
            ProjectProgressWidget::class,
            GlobalShortagesWidget::class,
            LowStockWidget::class,
            MaterialRequestsPendingWidget::class,
            FinishedReceiptsPendingWidget::class,
            PurchaseOrdersPendingWidget::class,
            RecentActivityWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
