<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;
use App\Support\CrownAuthorization;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseOrders extends ListRecords
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('fromShortages')
                ->label('إنشاء أمر شراء من النواقص')
                ->icon('heroicon-o-exclamation-triangle')
                ->color('warning')
                ->visible(fn () => CrownAuthorization::canManagePurchasing())
                ->form([
                    Select::make('supplier_id')
                        ->label('المورد')
                        ->options(fn () => Supplier::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->required(),
                ])
                ->action(function (array $data, ListPurchaseOrders $livewire) {
                    try {
                        $po = app(PurchaseOrderService::class)->createFromShortages(
                            (int) $data['supplier_id'],
                            auth()->user()
                        );
                        Notification::make()
                            ->title('تم إنشاء أمر الشراء')
                            ->body($po->po_number)
                            ->success()
                            ->send();
                        $livewire->redirect(PurchaseOrderResource::getUrl('edit', ['record' => $po]));
                    } catch (\Throwable $e) {
                        Notification::make()->title($e->getMessage())->danger()->send();
                    }
                }),

            CreateAction::make()->label('أمر شراء جديد')
                ->visible(fn () => CrownAuthorization::canManagePurchasing()),
        ];
    }
}
