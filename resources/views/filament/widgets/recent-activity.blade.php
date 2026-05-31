<x-filament-widgets::widget>
    @include('filament.components.crown-theme-global')
    <div class="grid gap-4 md:grid-cols-2">
        @if (count($movements))
            <x-filament::section heading="آخر حركات المخزون">
                <ul class="text-sm space-y-1" style="color:var(--crown-text);">
                    @foreach ($movements as $m)
                        <li>
                            <span style="color:var(--crown-text-muted);">{{ $m['at'] }}</span>
                            — <strong>{{ $m['type'] }}</strong> {{ $m['item'] }}
                            (<span class="crown-num">{{ $m['qty'] }}</span>)
                        </li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endif
        @if (count($workOrders))
            <x-filament::section heading="آخر أذونات الإنتاج">
                <ul class="text-sm space-y-1" style="color:var(--crown-text);">
                    @foreach ($workOrders as $wo)
                        <li>
                            <span style="color:var(--crown-text-muted);">{{ $wo['at'] }}</span>
                            — {{ $wo['order_number'] }} / {{ $wo['project'] }}
                            (<span style="color:var(--crown-charcoal);font-weight:600;">{{ $wo['status'] }}</span>)
                        </li>
                    @endforeach
                </ul>
            </x-filament::section>
        @endif
    </div>
</x-filament-widgets::widget>
