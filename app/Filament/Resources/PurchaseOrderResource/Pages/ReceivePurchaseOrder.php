<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\PurchaseOrderService;
use App\Support\CrownAuthorization;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReceivePurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected static ?string $title = 'استلام أمر الشراء';

    public static function canAccess(array $parameters = []): bool
    {
        return CrownAuthorization::canManageInventory() || CrownAuthorization::canManagePurchasing();
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات الأمر')->schema([
                TextEntry::make('po_number')->label('رقم الأمر'),
                TextEntry::make('supplier.name')->label('المورد'),
                TextEntry::make('status')->label('الحالة')->badge(),
            ])->columns(3),
            Section::make('الأصناف')->schema([
                RepeatableEntry::make('items')
                    ->label('')
                    ->schema([
                        TextEntry::make('rawMaterial.name')->label('المادة'),
                        TextEntry::make('rawMaterial.unit')->label('الوحدة'),
                        TextEntry::make('qty_ordered')->label('مطلوب')->numeric(decimalPlaces: 2),
                        TextEntry::make('qty_received')->label('مُستلم')->numeric(decimalPlaces: 2),
                        TextEntry::make('remaining')
                            ->label('متبقي')
                            ->state(fn (PurchaseOrderItem $record) => $record->remainingQty())
                            ->numeric(decimalPlaces: 2),
                    ])
                    ->columns(5),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('receiveLine')
                ->label('تسجيل استلام')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (PurchaseOrder $r) => in_array($r->status, [
                    PurchaseOrder::STATUS_SENT,
                    PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
                ], true) && CrownAuthorization::canManageInventory())
                ->form([
                    \Filament\Forms\Components\Select::make('purchase_order_item_id')
                        ->label('الصنف')
                        ->options(function (PurchaseOrder $record) {
                            return $record->items()
                                ->with('rawMaterial')
                                ->get()
                                ->filter(fn (PurchaseOrderItem $i) => $i->remainingQty() > 0)
                                ->mapWithKeys(fn (PurchaseOrderItem $i) => [
                                    $i->id => sprintf(
                                        '%s — متبقي %s %s',
                                        $i->rawMaterial?->name,
                                        $i->remainingQty(),
                                        $i->rawMaterial?->unit
                                    ),
                                ]);
                        })
                        ->required(),
                    TextInput::make('qty')
                        ->label('كمية الاستلام')
                        ->numeric()
                        ->required()
                        ->minValue(0.01),
                ])
                ->action(function (PurchaseOrder $record, array $data) {
                    $item = PurchaseOrderItem::findOrFail($data['purchase_order_item_id']);
                    try {
                        app(PurchaseOrderService::class)->receiveItem(
                            $item,
                            (float) $data['qty'],
                            auth()->user()
                        );
                        Notification::make()->title('تم الاستلام وتحديث المخزن')->success()->send();
                        $this->redirect(static::getUrl(['record' => $record->fresh()]));
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
