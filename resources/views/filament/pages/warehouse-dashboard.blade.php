<x-filament-panels::page>
    @include('filament.components.crown-theme-global')

    <div class="wh-dash" style="direction:rtl;display:grid;gap:1rem;">
        <div class="crown-kpi-row">
            <div class="crown-kpi crown-kpi--warning">
                <div class="crown-kpi__label">طلبات صرف معلّقة</div>
                <div class="crown-kpi__value crown-num">{{ $pendingMaterialRequests }}</div>
            </div>
            <div class="crown-kpi crown-kpi--primary">
                <div class="crown-kpi__label">طلبات استلام تام معلّقة</div>
                <div class="crown-kpi__value crown-num">{{ $pendingFinishedReceipts }}</div>
            </div>
        </div>

        <div class="crown-table-wrap">
            <div style="padding:0.75rem 1rem;font-weight:700;background:var(--crown-sec-bg);color:var(--crown-sec-text);border-bottom:1px solid var(--crown-grid);">
                أقل الأصناف رصيداً
            </div>
            <table class="crown-table">
                <thead>
                    <tr><th class="col-text">المخزن</th><th class="col-text">الصنف</th><th>الرصيد</th></tr>
                </thead>
                <tbody>
                    @forelse ($lowStock as $row)
                        <tr class="item-row {{ $loop->even ? 'item-row--zebra' : '' }}">
                            <td class="col-item">{{ $row['warehouse'] }}</td>
                            <td class="col-item">{{ $row['item'] }}</td>
                            <td class="col-num crown-num col-remaining--open">{{ number_format($row['qty'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" style="color:var(--crown-text-muted);text-align:center;">لا توجد أرصدة منخفضة</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="crown-table-wrap">
            <div style="padding:0.75rem 1rem;font-weight:700;background:var(--crown-sec-bg);color:var(--crown-sec-text);border-bottom:1px solid var(--crown-grid);">
                آخر حركات المخزون
            </div>
            <table class="crown-table">
                <thead>
                    <tr>
                        <th>التاريخ</th><th class="col-text">المخزن</th><th>النوع</th>
                        <th class="col-text">الصنف</th><th>الكمية</th><th class="col-text">المستخدم</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($recentMovements as $m)
                        <tr class="item-row {{ $loop->even ? 'item-row--zebra' : '' }}">
                            <td class="col-num crown-num">{{ $m['at'] }}</td>
                            <td class="col-item">{{ $m['warehouse'] }}</td>
                            <td class="col-num">{{ $m['type'] }}</td>
                            <td class="col-item">{{ $m['item'] }}</td>
                            <td class="col-num crown-num">{{ number_format($m['qty'], 2) }}</td>
                            <td class="col-item">{{ $m['user'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-filament-panels::page>
