<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RawMaterialResource\Pages;
use App\Models\RawMaterial;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RawMaterialResource extends Resource
{
    protected static ?string $model = RawMaterial::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-beaker';

    protected static ?string $navigationLabel = 'مواد خام';

    protected static ?string $modelLabel = 'مادة خام';

    protected static ?string $pluralModelLabel = 'مواد خام';

    protected static string|\UnitEnum|null $navigationGroup = 'المخزون';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FormSection::make()->schema([
                TextInput::make('code')->label('الكود')->required()->unique(ignoreRecord: true),
                TextInput::make('name')->label('الاسم')->required(),
                TextInput::make('unit')->label('الوحدة')->default('كجم'),
                Toggle::make('is_active')->label('نشط')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('code')->label('الكود')->searchable(),
            TextColumn::make('name')->label('الاسم')->searchable(),
            TextColumn::make('unit')->label('الوحدة'),
            IconColumn::make('is_active')->label('نشط')->boolean(),
        ])->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListRawMaterials::route('/'),
            'create' => Pages\CreateRawMaterial::route('/create'),
            'edit'   => Pages\EditRawMaterial::route('/{record}/edit'),
        ];
    }
}
