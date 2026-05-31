<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\PurchaseOrder;
use App\Models\RawMaterial;
use App\Models\Supplier;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section as FormSection;
use Filament\Schemas\Schema;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'أوامر الشراء';

    protected static ?string $modelLabel = 'أمر شراء';

    protected static ?string $pluralModelLabel = 'أوامر الشراء';

    protected static string|\UnitEnum|null $navigationGroup = 'المشتريات';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            FormSection::make('الأمر')->schema([
                Select::make('supplier_id')
                    ->label('المورد')
                    ->options(fn () => Supplier::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->disabled(fn (?PurchaseOrder $record) => $record && $record->status !== PurchaseOrder::STATUS_DRAFT),
                TextInput::make('po_number')
                    ->label('رقم الأمر')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->default(fn () => app(\App\Services\PurchaseOrderService::class)->generatePoNumber()),
                DatePicker::make('order_date')->label('تاريخ الأمر')->default(now()),
                Textarea::make('notes')->label('ملاحظات')->columnSpanFull(),
            ])->columns(2),

            FormSection::make('الأصناف')->schema([
                Repeater::make('items')
                    ->label('')
                    ->relationship()
                    ->schema([
                        Select::make('raw_material_id')
                            ->label('المادة الخام')
                            ->options(fn () => RawMaterial::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        TextInput::make('qty_ordered')->label('الكمية المطلوبة')->numeric()->required()->minValue(0.01),
                        TextInput::make('qty_received')->label('المُستلم')->numeric()->default(0)->disabled(),
                        TextInput::make('unit_price')->label('سعر الوحدة')->numeric(),
                    ])
                    ->columns(4)
                    ->defaultItems(1)
                    ->addActionLabel('إضافة صنف')
                    ->disabled(fn (?PurchaseOrder $record) => $record && $record->status !== PurchaseOrder::STATUS_DRAFT),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('po_number')->label('رقم الأمر')->searchable(),
                TextColumn::make('supplier.name')->label('المورد'),
                TextColumn::make('status')->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'draft' => 'مسودة',
                        'sent' => 'مُرسَل',
                        'partially_received' => 'استلام جزئي',
                        'received' => 'مُستلم',
                        'cancelled' => 'ملغى',
                        default => $s,
                    })
                    ->color(fn ($s) => match ($s) {
                        'received' => 'success',
                        'partially_received' => 'warning',
                        'sent' => 'info',
                        'cancelled' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('order_date')->label('التاريخ')->date('Y-m-d'),
                TextColumn::make('creator.name')->label('أنشأه')->placeholder('—'),
            ])
            ->actions([
                Action::make('receive')
                    ->label('استلام')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->visible(fn (PurchaseOrder $r) => in_array($r->status, [
                        PurchaseOrder::STATUS_SENT,
                        PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
                    ], true))
                    ->url(fn (PurchaseOrder $r) => Pages\ReceivePurchaseOrder::getUrl(['record' => $r])),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'   => Pages\ListPurchaseOrders::route('/'),
            'create'  => Pages\CreatePurchaseOrder::route('/create'),
            'edit'    => Pages\EditPurchaseOrder::route('/{record}/edit'),
            'receive' => Pages\ReceivePurchaseOrder::route('/{record}/receive'),
        ];
    }
}
