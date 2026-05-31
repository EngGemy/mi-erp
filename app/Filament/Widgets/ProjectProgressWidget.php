<?php

namespace App\Filament\Widgets;

use App\Services\DashboardStatsService;
use Filament\Widgets\Widget;

class ProjectProgressWidget extends Widget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.widgets.project-progress';

    public array $projects = [];

    public static function canView(): bool
    {
        return app(DashboardStatsService::class)->canViewProjects();
    }

    public function mount(): void
    {
        $this->projects = app(DashboardStatsService::class)->projectsWithProgress(10);
    }
}
