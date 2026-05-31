<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'حركات المخزون';

    protected static ?string $modelLabel = 'حركة';

    protected static ?string $pluralModelLabel = 'سجل الحركات';

    protected static string|\UnitEnum|null $navigationGroup = 'المخزون';

    protected static ?int $navigationSort = 5;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['warehouse', 'stockable', 'user'])->latest('created_at'))
            ->columns([
                TextColumn::make('created_at')->label('التاريخ')->dateTime('Y-m-d H:i')->sortable(),
                TextColumn::make('warehouse.name')->label('المخزن'),
                TextColumn::make('type')->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'in' => 'إدخال',
                        'out' => 'صرف',
                        'transfer' => 'تحويل',
                        'adjust' => 'تسوية',
                        default => $s,
                    })
                    ->color(fn ($s) => match ($s) {
                        'in' => 'success',
                        'out' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('stockable_label')
                    ->label('الصنف')
                    ->state(function (StockMovement $r) {
                        $s = $r->stockable;

                        return $s ? (($s->code ?? '').' — '.($s->name ?? '')) : '—';
                    }),
                TextColumn::make('qty')->label('الكمية')->numeric(decimalPlaces: 2),
                TextColumn::make('user.name')->label('المستخدم')->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('type')->label('النوع')->options([
                    'in' => 'إدخال', 'out' => 'صرف', 'transfer' => 'تحويل', 'adjust' => 'تسوية',
                ]),
                SelectFilter::make('warehouse_id')->label('المخزن')->relationship('warehouse', 'name'),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListStockMovements::route('/')];
    }
}
