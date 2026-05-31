<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FinishedReceiptResource\Pages;
use App\Models\FinishedReceipt;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FinishedReceiptResource extends Resource
{
    protected static ?string $model = FinishedReceipt::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';

    protected static ?string $navigationLabel = 'طلبات استلام التام';

    protected static ?string $modelLabel = 'طلب استلام';

    protected static ?string $pluralModelLabel = 'طلبات استلام التام';

    protected static string|\UnitEnum|null $navigationGroup = 'المخزون';

    protected static ?int $navigationSort = 11;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->label('#'),
                TextColumn::make('workOrder.order_number')->label('إذن الإنتاج'),
                TextColumn::make('status')->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'pending'  => 'معلّق',
                        'approved' => 'موافق',
                        'rejected' => 'مرفوض',
                        'received' => 'مُستلم',
                        default    => $s,
                    })
                    ->color(fn ($s) => match ($s) {
                        'pending'  => 'warning',
                        'approved' => 'info',
                        'rejected' => 'danger',
                        'received' => 'success',
                        default    => 'gray',
                    }),
                TextColumn::make('created_at')->label('التاريخ')->dateTime('Y-m-d H:i'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'pending' => 'معلّق', 'approved' => 'موافق', 'rejected' => 'مرفوض', 'received' => 'مُستلم',
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFinishedReceipts::route('/'),
            'view'  => Pages\ViewFinishedReceipt::route('/{record}'),
        ];
    }
}
