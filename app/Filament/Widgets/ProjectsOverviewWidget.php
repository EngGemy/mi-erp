<?php

namespace App\Filament\Widgets;

use App\Services\DashboardStatsService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectsOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'المشاريع';

    public static function canView(): bool
    {
        return app(DashboardStatsService::class)->canViewProjects();
    }

    protected function getStats(): array
    {
        $s = app(DashboardStatsService::class)->projectSummary();

        return [
            Stat::make('إجمالي المشاريع', (string) $s['total']),
            Stat::make('مسودة', (string) $s['draft'])->color('gray'),
            Stat::make('قيد التنفيذ', (string) $s['in_progress'])->color('warning'),
            Stat::make('مُسلّم', (string) $s['delivered'])->color('success'),
            Stat::make('متوسط الإكتمال', $s['avg_progress'].'%')->color('primary'),
        ];
    }
}
