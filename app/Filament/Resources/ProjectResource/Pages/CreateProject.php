<?php

namespace App\Filament\Resources\ProjectResource\Pages;

use App\Filament\Resources\ProjectResource;
use App\Filament\Resources\ProjectResource\Concerns\SyncsProjectVariables;
use App\Services\CatalogApplyService;
use App\Services\CrownSettings;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateProject extends CreateRecord
{
    use SyncsProjectVariables;

    protected static string $resource = ProjectResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $defaults = CrownSettings::projectDefaults();
        $data['default_scrap_percent'] ??= $defaults['default_scrap_percent'];
        $data['units_multiplier'] ??= $defaults['units_multiplier'];

        return $this->extractPendingVariables($data);
    }

    protected function afterCreate(): void
    {
        $this->persistProjectVariables();

        app(CatalogApplyService::class)->applyFromCatalog(
            $this->record->fresh(),
            CatalogApplyService::MODE_REPLACE
        );
    }

    protected function getRedirectUrl(): string
    {
        return ViewBom::getUrl(['record' => $this->record]);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('تم إنشاء المشروع')
            ->body('تم حفظ المتغيرات وسحب أقسام وأصناف كراون تلقائياً — يمكنك مراجعة الحصر.')
            ->success();
    }
}
