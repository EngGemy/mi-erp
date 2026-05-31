{{-- شريط الحمولة الفعّالة — مشترك بين الحصر والنواقص --}}
@php
    $activeShipment = $this->activeShipment ?? collect($shipments)->firstWhere('id', $activeShipmentId);
    $soft = $soft ?? false;
    $canEditShipments = auth()->user()?->can('Update:Project') ?? false;
@endphp

<div class="crown-ship-bar {{ $soft ? 'crown-ship-bar--soft' : '' }}">
    <div style="display:flex; align-items:center; gap:0.5rem; flex-wrap:wrap;">
        <span class="crown-ship-bar__title">الحمولة الفعّالة</span>
        @if (count($shipments) > 0)
            <select wire:model.live="activeShipmentId" class="crown-ship-select">
                @foreach ($shipments as $sh)
                    <option value="{{ $sh['id'] }}">
                        {{ $sh['name'] }}@if($sh['shipped_at']) — {{ $sh['shipped_at'] }}@endif
                    </option>
                @endforeach
            </select>
        @else
            <span class="crown-ship-empty">لا توجد حمولات — أنشئ حمولة للتسجيل</span>
        @endif
    </div>

    @if ($activeShipment && count($shipments) > 0)
        <div class="crown-ship-meta">
            @if ($activeShipment['driver_name'] ?? null)
                <span>سائق: <strong>{{ $activeShipment['driver_name'] }}</strong></span>
            @endif
            @if ($activeShipment['vehicle_no'] ?? null)
                <span>سيارة: <strong>{{ $activeShipment['vehicle_no'] }}</strong></span>
            @endif
            @if ($activeShipment['responsible'] ?? null)
                <span>مسؤول: <strong>{{ $activeShipment['responsible'] }}</strong></span>
            @endif
            @if ($activeShipment['arrival_time'] ?? null)
                <span>موعد: <strong>{{ $activeShipment['arrival_time'] }}</strong></span>
            @endif
        </div>
    @endif

    @if ($canEditShipments)
        <div class="crown-ship-actions">
            @if (count($shipments) > 0)
                <button type="button" wire:click="openEditShipmentModal" class="crown-btn crown-btn--ghost">
                    بيانات الحمولة
                </button>
            @endif
            <button type="button" wire:click="openCreateShipmentModal" class="crown-btn crown-btn--primary">
                + حمولة جديدة
            </button>
        </div>
    @endif
</div>

@include('filament.components.shipment-modals')
