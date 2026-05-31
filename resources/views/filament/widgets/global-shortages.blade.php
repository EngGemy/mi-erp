<x-filament-widgets::widget>
    @include('filament.components.crown-theme-global')
    <x-filament::section heading="النواقص عبر المشاريع">
        <p class="text-sm mb-2" style="color:var(--crown-text);">
            إجمالي أسطر النقص: <strong class="crown-num">{{ $totalLines }}</strong>
            — كمية مجمّعة: <strong class="crown-num" style="color:var(--crown-danger);">{{ number_format($totalQty, 2) }}</strong>
        </p>
        <ul class="text-sm space-y-1" style="color:var(--crown-text);">
            @forelse ($topItems as $item)
                <li>{{ $item['name'] }} ({{ $item['code'] }}): <span class="crown-num" style="color:var(--crown-danger);font-weight:700;">{{ number_format($item['shortage'], 2) }}</span></li>
            @empty
                <li style="color:var(--crown-text-muted);">لا توجد نواقص.</li>
            @endforelse
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
