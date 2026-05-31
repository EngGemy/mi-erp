<?php

namespace App\Filament\Concerns;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use App\Services\ShortageService;
use Filament\Notifications\Notification;

/**
 * إدارة الحمولة الفعّالة والتعديل المباشر على shipment_items.
 *
 * @property \App\Models\Project $record
 */
trait ManagesActiveShipment
{
    public array $shipments = [];

    public ?int $activeShipmentId = null;

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public string $newShipmentName = '';

    public ?string $newShipmentDate = null;

    public string $newDriverName = '';

    public string $newVehicleNo = '';

    public string $newResponsible = '';

    public ?string $newArrivalTime = null;

    public string $newNotes = '';

    /** @var array<int, array> item_id => shortage row */
    public array $shortageByItem = [];

    protected function initActiveShipment(): void
    {
        if ($this->activeShipmentId === null) {
            $this->activeShipmentId = $this->record->shipments()->latest('id')->value('id');
        }
    }

    protected function reloadShipmentContext(): void
    {
        $data = app(ShortageService::class)->build($this->record, $this->activeShipmentId);
        $this->shipments = $data['shipments'];
        $this->shortageByItem = collect($data['rows'])->keyBy('item_id')->all();
    }

    public function updatedActiveShipmentId(): void
    {
        $this->reloadShipmentContext();
        if (method_exists($this, 'afterShipmentContextReloaded')) {
            $this->afterShipmentContextReloaded();
        }
    }

    public function getActiveShipmentProperty(): ?array
    {
        return collect($this->shipments)->firstWhere('id', $this->activeShipmentId);
    }

    public function openCreateShipmentModal(): void
    {
        $count = $this->record->shipments()->count();
        $this->newShipmentName = 'حمولة '.($count + 1);
        $this->newShipmentDate = now()->format('Y-m-d');
        $this->newDriverName = '';
        $this->newVehicleNo = '';
        $this->newResponsible = '';
        $this->newArrivalTime = null;
        $this->newNotes = '';
        $this->showCreateModal = true;
        $this->showEditModal = false;
    }

    public function closeCreateShipmentModal(): void
    {
        $this->showCreateModal = false;
    }

    public function openEditShipmentModal(): void
    {
        $sh = Shipment::find($this->activeShipmentId);
        if (! $sh || $sh->project_id !== $this->record->id) {
            Notification::make()->title('اختر حمولة أولاً')->warning()->send();

            return;
        }

        $this->newShipmentName = $sh->name;
        $this->newShipmentDate = optional($sh->shipped_at)->format('Y-m-d');
        $this->newDriverName = $sh->driver_name ?? '';
        $this->newVehicleNo = $sh->vehicle_no ?? '';
        $this->newResponsible = $sh->responsible ?? '';
        $this->newArrivalTime = optional($sh->arrival_time)->format('Y-m-d\TH:i');
        $this->newNotes = $sh->notes ?? '';
        $this->showEditModal = true;
        $this->showCreateModal = false;
    }

    public function closeEditShipmentModal(): void
    {
        $this->showEditModal = false;
    }

    protected function shipmentFormRules(): array
    {
        return [
            'newShipmentName' => 'required|string|max:255',
            'newShipmentDate' => 'nullable|date',
            'newDriverName' => 'nullable|string|max:255',
            'newVehicleNo' => 'nullable|string|max:255',
            'newResponsible' => 'nullable|string|max:255',
            'newArrivalTime' => 'nullable|date',
            'newNotes' => 'nullable|string|max:2000',
        ];
    }

    protected function shipmentPayload(): array
    {
        return [
            'name'         => $this->newShipmentName,
            'shipped_at'   => $this->newShipmentDate ?: now(),
            'driver_name'  => $this->newDriverName ?: null,
            'vehicle_no'   => $this->newVehicleNo ?: null,
            'responsible'  => $this->newResponsible ?: null,
            'arrival_time' => $this->newArrivalTime ?: null,
            'notes'        => $this->newNotes ?: null,
        ];
    }

    public function createShipment(): void
    {
        if (! auth()->user()?->can('Update:Project')) {
            Notification::make()->title('ليس لديك صلاحية إنشاء حمولة')->danger()->send();

            return;
        }

        $this->validate($this->shipmentFormRules(), [], [
            'newShipmentName' => 'اسم الحمولة',
            'newShipmentDate' => 'تاريخ التوريد',
            'newDriverName' => 'السائق',
            'newVehicleNo' => 'رقم السيارة',
            'newResponsible' => 'المسؤول',
            'newArrivalTime' => 'موعد الوصول',
            'newNotes' => 'ملاحظات',
        ]);

        $sh = $this->record->shipments()->create(array_merge($this->shipmentPayload(), [
            'sort' => $this->record->shipments()->count(),
        ]));

        $this->activeShipmentId = $sh->id;
        $this->showCreateModal = false;
        $this->reloadShipmentContext();
        if (method_exists($this, 'afterShipmentContextReloaded')) {
            $this->afterShipmentContextReloaded();
        }

        Notification::make()->title('تم إنشاء '.$sh->name)->success()->send();
    }

    public function updateActiveShipment(): void
    {
        if (! auth()->user()?->can('Update:Project')) {
            Notification::make()->title('ليس لديك صلاحية تعديل الحمولة')->danger()->send();

            return;
        }

        $this->validate($this->shipmentFormRules(), [], [
            'newShipmentName' => 'اسم الحمولة',
            'newShipmentDate' => 'تاريخ التوريد',
            'newDriverName' => 'السائق',
            'newVehicleNo' => 'رقم السيارة',
            'newResponsible' => 'المسؤول',
            'newArrivalTime' => 'موعد الوصول',
            'newNotes' => 'ملاحظات',
        ]);

        $shipment = Shipment::find($this->activeShipmentId);
        if (! $shipment || $shipment->project_id !== $this->record->id) {
            Notification::make()->title('حمولة غير صالحة')->danger()->send();

            return;
        }

        $shipment->update($this->shipmentPayload());
        $this->showEditModal = false;
        $this->reloadShipmentContext();
        if (method_exists($this, 'afterShipmentContextReloaded')) {
            $this->afterShipmentContextReloaded();
        }

        Notification::make()->title('تم تحديث بيانات الحمولة')->success()->send();
    }

    public function adjust(int $itemId, float $delta): void
    {
        if (! auth()->user()?->can('Update:Project')) {
            Notification::make()->title('ليس لديك صلاحية تعديل الحمولات')->danger()->send();

            return;
        }

        if ($delta == 0) {
            return;
        }

        if (! $this->activeShipmentId) {
            Notification::make()->title('اختر حمولة أو أنشئ حمولة جديدة')->warning()->send();

            return;
        }

        $shipment = Shipment::find($this->activeShipmentId);
        if (! $shipment || $shipment->project_id !== $this->record->id) {
            Notification::make()->title('اختر حمولة صحيحة أولاً')->danger()->send();

            return;
        }

        $row = $this->shortageByItem[$itemId] ?? null;
        if (! $row) {
            Notification::make()->title('الصنف غير موجود في سياق التوريد')->danger()->send();

            return;
        }

        $remaining = (float) ($row['remaining'] ?? 0);
        $activeQty = (float) ($row['active_qty'] ?? 0);

        if ($delta > 0) {
            if (($row['is_over'] ?? false) || $remaining <= 0.0001) {
                Notification::make()
                    ->title(($row['is_over'] ?? false) ? 'تسليم زائد' : 'مكتمل التوريد')
                    ->body('لا يمكن إضافة كمية. يمكنك الخصم من الحمولة الحالية فقط.')
                    ->warning()
                    ->send();

                return;
            }

            if ($delta > $remaining + 0.0001) {
                Notification::make()
                    ->title('كمية تتجاوز المتفق عليه')
                    ->body(sprintf(
                        'الحد الأقصى للإضافة: %s (المتبقي من المطلوب).',
                        number_format($remaining, 0)
                    ))
                    ->danger()
                    ->send();

                return;
            }
        }

        if ($delta < 0 && $activeQty + $delta < -0.0001) {
            Notification::make()
                ->title('لا يمكن خصم أكثر من كمية الحمولة الحالية')
                ->body(sprintf('الكمية في هذه الحمولة: %s', number_format($activeQty, 0)))
                ->warning()
                ->send();

            return;
        }

        $si = ShipmentItem::firstOrNew([
            'shipment_id' => $shipment->id,
            'item_id'     => $itemId,
        ]);

        $newQty = max(0, $activeQty + $delta);
        $si->quantity = $newQty;
        $si->save();

        $this->reloadShipmentContext();
        if (method_exists($this, 'afterShipmentContextReloaded')) {
            $this->afterShipmentContextReloaded();
        }
    }

    public function fillShortage(int $itemId): void
    {
        $row = $this->shortageByItem[$itemId] ?? null;
        $remaining = (float) ($row['remaining'] ?? 0);

        if (! $row || $remaining <= 0.0001) {
            Notification::make()
                ->title('لا يوجد ناقص')
                ->body('هذا الصنف مُسلّم بالكامل — لا يمكن الإضافة.')
                ->warning()
                ->send();

            return;
        }

        $this->adjust($itemId, $remaining);
    }
}
