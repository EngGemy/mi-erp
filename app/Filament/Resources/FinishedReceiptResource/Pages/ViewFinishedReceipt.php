<?php

namespace App\Filament\Resources\FinishedReceiptResource\Pages;

use App\Filament\Resources\FinishedReceiptResource;
use App\Models\FinishedReceipt;
use App\Services\FinishedReceiptService;
use App\Support\CrownAuthorization;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewFinishedReceipt extends ViewRecord
{
    protected static string $resource = FinishedReceiptResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()->schema([
                TextEntry::make('workOrder.order_number')->label('إذن الإنتاج'),
                TextEntry::make('workOrder.project.name')->label('المشروع'),
                TextEntry::make('status')->label('الحالة')->badge(),
                TextEntry::make('note')->label('ملاحظة'),
            ])->columns(2),
            Section::make('الأصناف')->schema([
                RepeatableEntry::make('items')->schema([
                    TextEntry::make('catalogItem.name')->label('الصنف'),
                    TextEntry::make('qty')->label('الكمية')->numeric(decimalPlaces: 0),
                ])->columns(2),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('موافقة')
                ->color('success')
                ->visible(fn (FinishedReceipt $r) => $r->status === FinishedReceipt::STATUS_PENDING
                    && CrownAuthorization::canManageInventory())
                ->requiresConfirmation()
                ->action(function (FinishedReceipt $record) {
                    app(FinishedReceiptService::class)->approve($record, auth()->user());
                    Notification::make()->title('تمت الموافقة')->success()->send();
                }),

            Action::make('reject')
                ->label('رفض')
                ->color('danger')
                ->visible(fn (FinishedReceipt $r) => $r->status === FinishedReceipt::STATUS_PENDING
                    && CrownAuthorization::canManageInventory())
                ->form([Textarea::make('reason')->label('سبب الرفض')->required()])
                ->action(function (FinishedReceipt $record, array $data) {
                    app(FinishedReceiptService::class)->reject($record, auth()->user(), $data['reason']);
                    Notification::make()->title('تم الرفض')->warning()->send();
                }),

            Action::make('cancel')
                ->label('إلغاء')
                ->color('gray')
                ->visible(fn (FinishedReceipt $r) => in_array($r->status, [
                    FinishedReceipt::STATUS_PENDING,
                    FinishedReceipt::STATUS_APPROVED,
                ], true) && CrownAuthorization::canManageInventory())
                ->requiresConfirmation()
                ->action(function (FinishedReceipt $record) {
                    app(FinishedReceiptService::class)->cancel($record, auth()->user());
                    Notification::make()->title('تم الإلغاء')->warning()->send();
                    $this->redirect(static::getUrl(['record' => $record->fresh()]));
                }),

            Action::make('receive')
                ->label('إدخال للمخزن التام')
                ->color('primary')
                ->visible(fn (FinishedReceipt $r) => $r->status === FinishedReceipt::STATUS_APPROVED
                    && CrownAuthorization::canManageInventory())
                ->requiresConfirmation()
                ->action(function (FinishedReceipt $record) {
                    try {
                        app(FinishedReceiptService::class)->receive($record, auth()->user());
                        Notification::make()->title('تم الاستلام وتحديث WBS')->success()->send();
                        $this->redirect(static::getUrl(['record' => $record]));
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }
}
