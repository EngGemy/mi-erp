<?php

namespace App\Filament\Widgets;

use App\Services\DashboardStatsService;
use Filament\Widgets\Widget;

class GlobalShortagesWidget extends Widget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    protected string $view = 'filament.widgets.global-shortages';

    public int $totalLines = 0;

    public float $totalQty = 0;

    public array $topItems = [];

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user && ($user->can('View:ViewShortage') || $user->hasRole('admin') || $user->hasRole('logistics'));
    }

    public function mount(): void
    {
        $data = app(DashboardStatsService::class)->globalShortages();
        $this->totalLines = $data['total_shortage_lines'];
        $this->totalQty = $data['total_shortage_qty'];
        $this->topItems = $data['top_items'];
    }
}
