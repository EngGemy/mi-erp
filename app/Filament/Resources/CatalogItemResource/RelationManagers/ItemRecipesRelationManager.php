<?php

namespace App\Filament\Resources\CatalogItemResource\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ItemRecipesRelationManager extends RelationManager
{
    protected static string $relationship = 'recipes';

    protected static ?string $title = 'وصفة المواد الخام (BOM خام)';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('raw_material_id')
                ->label('المادة الخام')
                ->relationship('rawMaterial', 'name')
                ->searchable()
                ->preload()
                ->required(),
            TextInput::make('qty_per_unit')
                ->label('الكمية لكل وحدة تام')
                ->numeric()
                ->required()
                ->minValue(0.0001)
                ->helperText('مثال: رجل قايم = 2 كجم حديد/وحدة'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('rawMaterial.code')->label('كود الخام'),
                TextColumn::make('rawMaterial.name')->label('المادة'),
                TextColumn::make('rawMaterial.unit')->label('الوحدة'),
                TextColumn::make('qty_per_unit')->label('كمية/وحدة')->numeric(decimalPlaces: 4),
            ])
            ->headerActions([CreateAction::make()->label('إضافة مادة خام')])
            ->actions([EditAction::make(), DeleteAction::make()]);
    }
}
