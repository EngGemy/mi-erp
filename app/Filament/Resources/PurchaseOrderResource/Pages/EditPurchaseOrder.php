<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use App\Support\CrownAuthorization;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label('إرسال للمورد')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn (PurchaseOrder $r) => $r->status === PurchaseOrder::STATUS_DRAFT
                    && CrownAuthorization::canManagePurchasing())
                ->requiresConfirmation()
                ->action(function (PurchaseOrder $record) {
                    try {
                        app(PurchaseOrderService::class)->markSent($record, auth()->user());
                        Notification::make()->title('تم إرسال الأمر — أُشعر مدير المخازن')->success()->send();
                        $this->redirect(static::getUrl(['record' => $record]));
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('receive')
                ->label('صفحة الاستلام')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn (PurchaseOrder $r) => in_array($r->status, [
                    PurchaseOrder::STATUS_SENT,
                    PurchaseOrder::STATUS_PARTIALLY_RECEIVED,
                ], true))
                ->url(fn (PurchaseOrder $r) => ReceivePurchaseOrder::getUrl(['record' => $r])),

            Action::make('cancel')
                ->label('إلغاء الأمر')
                ->color('danger')
                ->visible(fn (PurchaseOrder $r) => ! in_array($r->status, [
                    PurchaseOrder::STATUS_RECEIVED,
                    PurchaseOrder::STATUS_CANCELLED,
                ], true) && CrownAuthorization::canManagePurchasing())
                ->requiresConfirmation()
                ->action(function (PurchaseOrder $record) {
                    app(PurchaseOrderService::class)->cancel($record);
                    Notification::make()->title('تم الإلغاء')->warning()->send();
                }),
        ];
    }
}
