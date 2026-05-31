<x-filament-widgets::widget>
    @include('filament.components.crown-theme-global')
    <x-filament::section heading="تنبيه إعادة الطلب (أقل رصيد خام)">
        <ul class="text-sm space-y-1" style="color:var(--crown-text);">
            @forelse ($items as $item)
                <li>
                    {{ $item['name'] }}:
                    <span class="crown-num" style="font-weight:800;color:var(--crown-warning);">{{ number_format($item['qty'], 2) }}</span>
                    {{ $item['unit'] }}
                </li>
            @empty
                <li style="color:var(--crown-text-muted);">لا توجد أرصدة.</li>
            @endforelse
        </ul>
    </x-filament::section>
</x-filament-widgets::widget>
