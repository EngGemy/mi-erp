<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Concerns\AuthorizesCrownPage;
use App\Filament\Resources\ProjectResource;
use App\Models\Project;
use App\Models\ProjectItemProgress;
use App\Services\ProgressService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ViewWbs extends Page
{
    use AuthorizesCrownPage;

    protected static string $resource = ProjectResource::class;

    protected static function crownPagePermission(): ?string
    {
        return 'View:ViewWbs';
    }

    protected string $view = 'filament.pages.view-wbs';

    public function getTitle(): string
    {
        return 'هيكل الإكتمال (WBS)';
    }

    public Project $record;

    public array $tree = [];

    public float $projectPct = 0;

    public function mount(Project $record): void
    {
        $this->record = $record->load(['sections.items']);
        $service = app(ProgressService::class);
        $service->ensureProgressRows($this->record);
        $this->refreshTree();
    }

    public function refreshTree(): void
    {
        $result = app(ProgressService::class)->rollup($this->record->fresh());
        $this->tree = $result['sections'];
        $this->projectPct = $result['project_pct'];
        $this->record->refresh();
    }

    public function updateDoneQty(int $itemId, int $stageId, $value): void
    {
        if (! auth()->user()?->can('Update:Project')) {
            abort(403);
        }

        $qty = max(0, is_numeric($value) ? (float) $value : 0);

        ProjectItemProgress::query()->updateOrCreate(
            ['item_id' => $itemId, 'stage_id' => $stageId],
            ['done_qty' => $qty]
        );

        $this->refreshTree();

        Notification::make()
            ->title('تم تحديث الإكتمال')
            ->success()
            ->duration(1500)
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('bom')
                ->label('الحصر')
                ->icon('heroicon-o-calculator')
                ->url(fn () => ViewBom::getUrl(['record' => $this->record])),
            Action::make('edit')
                ->label('تعديل المشروع')
                ->icon('heroicon-o-pencil-square')
                ->url(fn () => ProjectResource::getUrl('edit', ['record' => $this->record])),
        ];
    }
}
