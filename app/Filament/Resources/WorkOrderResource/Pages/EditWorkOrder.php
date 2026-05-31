<?php

namespace App\Filament\Resources\WorkOrderResource\Pages;

use App\Filament\Resources\WorkOrderResource;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Services\WorkOrderService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditWorkOrder extends EditRecord
{
    protected static string $resource = WorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('issue')
                ->label('إصدار الإذن وطلب صرف الخام')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->visible(fn (WorkOrder $r) => $r->status === WorkOrder::STATUS_DRAFT
                    && auth()->user()?->can('Update:WorkOrder'))
                ->requiresConfirmation()
                ->modalDescription('سيُنشأ طلب صرف خام معلّق ويُرسل إشعار لمدير المخازن.')
                ->action(function (WorkOrder $record) {
                    try {
                        app(WorkOrderService::class)->issue($record, auth()->user());
                        Notification::make()->title('تم الإصدار وإنشاء طلب الصرف')->success()->send();
                        $this->redirect(static::getUrl(['record' => $record]));
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            Action::make('registerProduction')
                ->label('تسجيل إنتاج')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->visible(fn (WorkOrder $r) => in_array($r->status, [WorkOrder::STATUS_ISSUED, WorkOrder::STATUS_IN_PROGRESS], true)
                    && $r->materialRequest?->status === 'issued')
                ->form([
                    TextInput::make('work_order_item_id')
                        ->label('بند الإذن (ID)')
                        ->numeric()
                        ->required()
                        ->helperText('معرّف البند من جدول الأصناف أدناه'),
                    TextInput::make('qty')->label('الكمية المنتجة')->numeric()->required()->minValue(0.01),
                ])
                ->action(function (WorkOrder $record, array $data) {
                    $item = WorkOrderItem::where('work_order_id', $record->id)
                        ->findOrFail($data['work_order_item_id']);

                    try {
                        app(WorkOrderService::class)->registerProduction($item, (float) $data['qty'], auth()->user());
                        Notification::make()->title('تم تسجيل الإنتاج وإنشاء طلب استلام')->success()->send();
                        $this->redirect(static::getUrl(['record' => $record]));
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
