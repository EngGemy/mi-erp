<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CatalogSectionResource\Pages;
use App\Models\CatalogSection;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CatalogSectionResource extends Resource
{
    protected static ?string $model = CatalogSection::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'أقسام الكتالوج';

    protected static ?string $modelLabel = 'قسم كتالوج';

    protected static ?string $pluralModelLabel = 'أقسام الكتالوج';

    protected static string|\UnitEnum|null $navigationGroup = 'الكتالوج المركزي';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FormSection::make('بيانات القسم')->schema([
                TextInput::make('name')
                    ->label('اسم القسم')
                    ->required()
                    ->maxLength(255),
                TextInput::make('sort')
                    ->label('الترتيب')
                    ->numeric()
                    ->default(0)
                    ->required(),
                TextInput::make('weight_default')
                    ->label('الوزن الافتراضي (WBS)')
                    ->numeric()
                    ->nullable()
                    ->helperText('للمرحلة القادمة — نسبة الإكتمال'),
            ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort')
            ->columns([
                TextColumn::make('sort')->label('الترتيب')->sortable(),
                TextColumn::make('name')->label('اسم القسم')->searchable()->sortable(),
                TextColumn::make('items_count')->counts('items')->label('عدد الأصناف')->alignCenter(),
                TextColumn::make('weight_default')->label('الوزن %')->placeholder('—'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListCatalogSections::route('/'),
            'create' => Pages\CreateCatalogSection::route('/create'),
            'edit'   => Pages\EditCatalogSection::route('/{record}/edit'),
        ];
    }
}
