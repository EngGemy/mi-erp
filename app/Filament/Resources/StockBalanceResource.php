<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StockBalanceResource\Pages;
use App\Models\StockBalance;
use App\Models\Warehouse;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockBalanceResource extends Resource
{
    protected static ?string $model = StockBalance::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'أرصدة المخزون';

    protected static ?string $modelLabel = 'رصيد';

    protected static ?string $pluralModelLabel = 'أرصدة المخزون';

    protected static string|\UnitEnum|null $navigationGroup = 'المخزون';

    protected static ?int $navigationSort = 3;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['warehouse', 'stockable']))
            ->columns([
                TextColumn::make('warehouse.name')->label('المخزن')->sortable(),
                TextColumn::make('warehouse.type')->label('النوع')
                    ->formatStateUsing(fn ($s) => $s === Warehouse::TYPE_RAW ? 'خام' : 'تام')
                    ->badge(),
                TextColumn::make('stockable_display')
                    ->label('الصنف')
                    ->state(function (StockBalance $record) {
                        $s = $record->stockable;

                        return $s ? (($s->code ?? '').' — '.($s->name ?? '')) : '—';
                    }),
                TextColumn::make('qty_on_hand')->label('الرصيد')->numeric(decimalPlaces: 2),
                TextColumn::make('qty_reserved')->label('محجوز')->numeric(decimalPlaces: 2),
                TextColumn::make('available')
                    ->label('متاح')
                    ->state(fn (StockBalance $r) => $r->availableQty())
                    ->numeric(decimalPlaces: 2)
                    ->color(fn (StockBalance $r) => $r->availableQty() <= 0 ? 'danger' : 'success'),
            ])
            ->filters([
                SelectFilter::make('warehouse_id')
                    ->label('المخزن')
                    ->relationship('warehouse', 'name'),
                SelectFilter::make('warehouse_type')
                    ->label('نوع المخزن')
                    ->options(['raw' => 'خام', 'finished' => 'تام'])
                    ->query(fn (Builder $q, array $data) => $data['value']
                        ? $q->whereHas('warehouse', fn ($w) => $w->where('type', $data['value']))
                        : $q),
            ])
            ->defaultSort('qty_on_hand', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockBalances::route('/'),
        ];
    }
}
