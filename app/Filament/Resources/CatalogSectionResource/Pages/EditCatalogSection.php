<?php

namespace App\Filament\Resources\CatalogSectionResource\Pages;

use App\Filament\Resources\CatalogSectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCatalogSection extends EditRecord
{
    protected static string $resource = CatalogSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
