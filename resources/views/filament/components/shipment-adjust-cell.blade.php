@php
    $compact = $compact ?? false;
    $remaining = (float) ($remaining ?? 0);
    $activeQty = (float) ($activeQty ?? 0);
    $isOver = (bool) ($isOver ?? false);
    $canAdd = ! $isOver && $remaining > 0.0001;
    $maxAdd = max(0, $remaining);
@endphp
<div
    class="crown-adj {{ $compact ? 'crown-adj--compact' : '' }}"
    x-data="{
        remaining: {{ $maxAdd }},
        canAdd: @js($canAdd),
        qtyFromInput() {
            const el = this.$refs.qtyInput;
            const raw = el ? el.value : '';
            const n = parseFloat(String(raw).replace(/,/g, ''));
            return Number.isFinite(n) && n > 0 ? n : 0;
        },
        tryAdd() {
            if (!this.canAdd) return;
            const n = this.qtyFromInput();
            if (n <= 0) return;
            $wire.adjust({{ $itemId }}, n);
        },
        trySubtract() {
            const n = this.qtyFromInput();
            if (n <= 0) return;
            $wire.adjust({{ $itemId }}, -n);
        },
    }"
>
    <button
        type="button"
        class="crown-adj-btn crown-adj-btn--minus"
        @click.prevent.stop="trySubtract()"
        wire:loading.attr="disabled"
        wire:target="adjust, fillShortage"
        title="خصم من الحمولة"
        aria-label="خصم"
        @if($activeQty <= 0) disabled style="opacity:0.4;cursor:not-allowed" @endif
    >−</button>
    <input
        type="number"
        class="crown-adj-inp"
        x-ref="qtyInput"
        min="0"
        step="1"
        value="{{ $canAdd ? min(1, (int) max(1, $maxAdd)) : 1 }}"
        aria-label="الكمية"
        inputmode="numeric"
        @keydown.enter.prevent="canAdd ? tryAdd() : trySubtract()"
    >
    @if ($canAdd)
        <button
            type="button"
            class="crown-adj-btn crown-adj-btn--plus"
            @click.prevent.stop="tryAdd()"
            wire:loading.attr="disabled"
            wire:target="adjust, fillShortage"
            title="إضافة بحد أقصى {{ number_format($maxAdd, 0) }}"
            aria-label="إضافة"
        >+</button>
        <button
            type="button"
            class="crown-adj-fill"
            wire:click="fillShortage({{ $itemId }})"
            wire:loading.attr="disabled"
            wire:target="adjust, fillShortage"
            title="إكمال الناقص ({{ number_format($maxAdd, 0) }})"
        >أكمل الناقص</button>
    @elseif ($isOver)
        <span class="text-xs" style="color:var(--crown-warning,#b45309);font-weight:600;white-space:nowrap;">زائد — خصم فقط</span>
    @else
        <span class="text-xs" style="color:var(--crown-success,#15803d);font-weight:600;white-space:nowrap;">مكتمل</span>
    @endif
</div>
