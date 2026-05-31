<?php

namespace App\Filament\Resources\MaterialRequestResource\Pages;

use App\Filament\Resources\MaterialRequestResource;
use App\Models\MaterialRequest;
use App\Services\MaterialRequestService;
use App\Services\StockService;
use App\Support\CrownAuthorization;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewMaterialRequest extends ViewRecord
{
    protected static string $resource = MaterialRequestResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('بيانات الطلب')->schema([
                TextEntry::make('workOrder.order_number')->label('إذن الإنتاج'),
                TextEntry::make('workOrder.project.name')->label('المشروع'),
                TextEntry::make('status')->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($s) => match ($s) {
                        'pending' => 'معلّق', 'approved' => 'موافق', 'rejected' => 'مرفوض', 'issued' => 'مُصرف', default => $s,
                    }),
                TextEntry::make('note')->label('ملاحظة')->placeholder('—'),
                TextEntry::make('rejection_reason')
                    ->label('سبب الرفض')
                    ->visible(fn (MaterialRequest $record) => filled($record->rejection_reason)),
            ])->columns(2),
            Section::make('المواد المطلوبة')->schema([
                RepeatableEntry::make('items')
                    ->label('')
                    ->schema([
                        TextEntry::make('rawMaterial.name')->label('المادة'),
                        TextEntry::make('rawMaterial.unit')->label('الوحدة'),
                        TextEntry::make('qty_requested')->label('مطلوب')->numeric(decimalPlaces: 2),
                        TextEntry::make('qty_issued')->label('مُصرف')->numeric(decimalPlaces: 2),
                        TextEntry::make('available')
                            ->label('الرصيد المتاح')
                            ->state(function ($record) {
                                $wh = app(StockService::class)->getRawWarehouse();

                                return app(StockService::class)->availableQty($wh, $record->rawMaterial);
                            })
                            ->numeric(decimalPlaces: 2)
                            ->color(fn ($state) => $state <= 0 ? 'danger' : 'success'),
                    ])
                    ->columns(5),
            ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approve')
                ->label('موافقة')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn (MaterialRequest $r) => $r->status === MaterialRequest::STATUS_PENDING
                    && CrownAuthorization::canManageInventory())
                ->requiresConfirmation()
                ->action(function (MaterialRequest $record) {
                    app(MaterialRequestService::class)->approve($record, auth()->user());
                    Notification::make()->title('تمت الموافقة')->success()->send();
                }),

            Action::make('reject')
                ->label('رفض')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn (MaterialRequest $r) => $r->status === MaterialRequest::STATUS_PENDING
                    && CrownAuthorization::canManageInventory())
                ->form([Textarea::make('reason')->label('سبب الرفض')->required()])
                ->action(function (MaterialRequest $record, array $data) {
                    app(MaterialRequestService::class)->reject($record, auth()->user(), $data['reason']);
                    Notification::make()->title('تم الرفض')->warning()->send();
                }),

            Action::make('cancel')
                ->label('إلغاء')
                ->color('gray')
                ->visible(fn (MaterialRequest $r) => in_array($r->status, [
                    MaterialRequest::STATUS_PENDING,
                    MaterialRequest::STATUS_APPROVED,
                ], true) && CrownAuthorization::canManageInventory())
                ->requiresConfirmation()
                ->action(function (MaterialRequest $record) {
                    app(MaterialRequestService::class)->cancel($record, auth()->user());
                    Notification::make()->title('تم الإلغاء')->warning()->send();
                    $this->redirect(static::getUrl(['record' => $record->fresh()]));
                }),

            Action::make('issue')
                ->label('صرف من المخزن')
                ->icon('heroicon-o-truck')
                ->color('primary')
                ->visible(fn (MaterialRequest $r) => $r->status === MaterialRequest::STATUS_APPROVED
                    && CrownAuthorization::canManageInventory())
                ->requiresConfirmation()
                ->action(function (MaterialRequest $record) {
                    $result = app(MaterialRequestService::class)->issue($record, auth()->user());

                    if (! $result['ok']) {
                        $msg = $result['message'] ?? 'فشل الصرف';
                        if (! empty($result['shortages'])) {
                            $msg .= ' — '.collect($result['shortages'])
                                ->map(fn ($s) => "{$s['name']}: ناقص {$s['shortage']}")
                                ->implode('؛ ');
                        }
                        Notification::make()->title($msg)->danger()->send();

                        return;
                    }

                    Notification::make()->title('تم الصرف بنجاح')->success()->send();
                    $this->redirect(static::getUrl(['record' => $record]));
                }),
        ];
    }
}
