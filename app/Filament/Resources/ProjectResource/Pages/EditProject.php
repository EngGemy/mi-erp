<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Concerns\SyncsProjectVariables;
use App\Services\CatalogApplyService;
use App\Support\ProjectDefaultVariables;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditProject extends EditRecord
{
    use SyncsProjectVariables;

    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return ProjectDefaultVariables::mergeIntoForm($this->record, $data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->extractPendingVariables($data);
    }

    protected function afterSave(): void
    {
        $this->persistProjectVariables();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('loadCrownTemplate')
                ->label('تحميل قالب كراون')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('warning')
                ->requiresConfirmation(fn (): bool => $this->record->items()->exists())
                ->modalHeading('تحميل قالب كراون')
                ->modalDescription(fn (): string => $this->record->items()->exists()
                    ? 'المشروع يحتوي أصنافاً. سيتُزامَن من الكتالوج المركزي مع الإبقاء على التجاوزات المحلية (مثل required_override).'
                    : 'سيتم سحب كل أقسام وأصناف الكتالوج المركزي إلى المشروع.')
                ->modalSubmitActionLabel('تحميل القالب')
                ->action(function (): void {
                    $mode = $this->record->items()->exists()
                        ? CatalogApplyService::MODE_SYNC
                        : CatalogApplyService::MODE_REPLACE;

                    $stats = app(CatalogApplyService::class)->applyFromCatalog(
                        $this->record->fresh(),
                        $mode
                    );

                    Notification::make()
                        ->title('تم تحميل قالب كراون')
                        ->body("{$stats['sections']} أقسام، {$stats['items']} صنف.")
                        ->success()
                        ->send();
                }),

            Action::make('viewBom')
                ->label('عرض الحصر')
                ->icon('heroicon-o-calculator')
                ->color('success')
                ->url(fn () => ViewBom::getUrl(['record' => $this->record])),

            Action::make('shipments')
                ->label('تقرير الحمولات')
                ->icon('heroicon-o-truck')
                ->color('info')
                ->url(fn () => ViewShipmentReport::getUrl(['record' => $this->record])),

            DeleteAction::make(),
        ];
    }
}
