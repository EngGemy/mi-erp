<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Models\Warehouse;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WarehouseResource extends Resource
{
    protected static ?string $model = Warehouse::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'المخازن';

    protected static ?string $modelLabel = 'مخزن';

    protected static ?string $pluralModelLabel = 'المخازن';

    protected static string|\UnitEnum|null $navigationGroup = 'المخزون';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FormSection::make()->schema([
                TextInput::make('name')->label('اسم المخزن')->required(),
                Select::make('type')
                    ->label('النوع')
                    ->options([
                        Warehouse::TYPE_RAW      => 'خام',
                        Warehouse::TYPE_FINISHED => 'تام',
                    ])
                    ->required(),
                TextInput::make('location')->label('الموقع'),
                Toggle::make('is_active')->label('نشط')->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label('المخزن')->searchable(),
            TextColumn::make('type')->label('النوع')
                ->badge()
                ->formatStateUsing(fn ($s) => $s === Warehouse::TYPE_RAW ? 'خام' : 'تام')
                ->color(fn ($s) => $s === Warehouse::TYPE_RAW ? 'warning' : 'success'),
            TextColumn::make('location')->label('الموقع'),
            IconColumn::make('is_active')->label('نشط')->boolean(),
        ])->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'edit'   => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}
