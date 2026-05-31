<?php

namespace App\Filament\Resources\CatalogSectionResource\Pages;

use App\Filament\Resources\CatalogSectionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCatalogSections extends ListRecords
{
    protected static string $resource = CatalogSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('إضافة قسم'),
        ];
    }
}
