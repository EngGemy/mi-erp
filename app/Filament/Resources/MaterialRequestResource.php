<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialRequestResource\Pages;
use App\Models\MaterialRequest;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MaterialRequestResource extends Resource
{
    protected static ?string $model = MaterialRequest::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-down-tray';

    protected static ?string $navigationLabel = 'طلبات صرف الخام';

    protected static ?string $modelLabel = 'طلب صرف';

    protected static ?string $pluralModelLabel = 'طلبات صرف الخام';

    protected static string|\UnitEnum|null $navigationGroup = 'المخزون';

    protected static ?int $navigationSort = 10;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')->label('#')->sortable(),
                TextColumn::make('workOrder.order_number')->label('إذن الإنتاج')->searchable(),
                TextColumn::make('workOrder.project.name')->label('المشروع')->wrap(),
                TextColumn::make('status')->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'pending'  => 'معلّق',
                        'approved' => 'موافق عليه',
                        'rejected' => 'مرفوض',
                        'issued'   => 'تم الصرف',
                        default    => $s,
                    })
                    ->color(fn ($s) => match ($s) {
                        'pending'  => 'warning',
                        'approved' => 'info',
                        'rejected' => 'danger',
                        'issued'   => 'success',
                        default    => 'gray',
                    }),
                TextColumn::make('requester.name')->label('طالب')->placeholder('—'),
                TextColumn::make('created_at')->label('التاريخ')->dateTime('Y-m-d H:i'),
            ])
            ->filters([
                SelectFilter::make('status')->label('الحالة')->options([
                    'pending'  => 'معلّق',
                    'approved' => 'موافق',
                    'rejected' => 'مرفوض',
                    'issued'   => 'تم الصرف',
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMaterialRequests::route('/'),
            'view'  => Pages\ViewMaterialRequest::route('/{record}'),
        ];
    }
}
