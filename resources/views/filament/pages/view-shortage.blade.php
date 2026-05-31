<x-filament-panels::page>
@php
    $statusMap = [
        'complete' => ['مكتمل',  'var(--crown-sec-bg)', 'var(--crown-success)'],
        'partial'  => ['جزئي',   'rgba(180,83,9,.12)', 'var(--crown-warning)'],
        'open'     => ['لم يبدأ', 'var(--crown-sec-bg)', 'var(--crown-danger)'],
        'over'     => ['زائد',    'rgba(59,130,246,.1)', '#2563eb'],
        'none'     => ['بلا مرجع','var(--crown-zebra)', 'var(--crown-text-muted)'],
    ];
    $barColor = [
        'complete' => 'var(--crown-success)',
        'partial'  => 'var(--crown-warning)',
        'open'     => 'var(--crown-danger)',
        'over'     => '#2563eb',
        'none'     => 'var(--crown-text-muted)',
    ];
    $colspan = 8
        + ($showShipmentCols ? count($shipments) : 0)
        + ($showActiveShipmentQty ? 1 : 0);
@endphp

@include('filament.components.crown-theme-global')
@include('filament.components.crown-shared-styles')

<style>
    .crown-bar {
        background: var(--crown-grid);
        height: 0.375rem;
        border-radius: 0.25rem;
        overflow: hidden;
        min-width: 5.5rem;
    }
    .crown-badge {
        font-size: 0.6875rem;
        padding: 0.125rem 0.5rem;
        border-radius: 0.25rem;
        white-space: nowrap;
        font-weight: 600;
    }
    .crown-loading {
        font-size: 0.75rem;
        color: var(--crown-primary);
        margin-bottom: 0.5rem;
    }
</style>

<div wire:loading.delay.shortest class="crown-loading"
     wire:target="adjust, fillShortage, createShipment, updateActiveShipment, updatedActiveShipmentId">
    جارٍ التحديث...
</div>

@include('filament.components.shipment-bar')

<div class="crown-stat-grid">
    <div class="crown-stat"><div class="l">المطلوب الكلي</div><div class="v crown-num">{{ number_format($totals['required']) }}</div></div>
    <div class="crown-stat crown-stat--ok"><div class="l">المُسلّم</div><div class="v crown-num">{{ number_format($totals['delivered']) }}</div></div>
    <div class="crown-stat crown-stat--bad"><div class="l">الناقص</div><div class="v crown-num">{{ number_format($totals['shortage']) }}</div></div>
    <div class="crown-stat"><div class="l">نسبة الإنجاز</div><div class="v crown-num">{{ $totals['pct'] }}%</div></div>
</div>

<div class="crown-table-wrap">
    <div class="crown-table-scroll">
        <table class="crown-table" dir="rtl">
            <thead>
                <tr>
                    <th class="col-text">الكود</th>
                    <th class="col-text">بيان الصنف</th>
                    <th>المطلوب</th>
                    @if ($showShipmentCols)
                        @foreach ($shipments as $sh)
                            <th title="{{ $sh['shipped_at'] }}">{{ $sh['name'] }}</th>
                        @endforeach
                    @endif
                    @if ($showActiveShipmentQty)
                        <th>في الحمولة الحالية</th>
                    @endif
                    <th>المُسلّم</th>
                    <th>الناقص</th>
                    <th>الإنجاز</th>
                    <th>الحالة</th>
                    <th style="min-width: 11rem;">إضافة للحمولة</th>
                </tr>
            </thead>
            <tbody>
                @php $lastSection = null; $rowIdx = 0; @endphp
                @foreach ($rows as $r)
                    @if ($r['section'] && $r['section'] !== $lastSection)
                        @php $lastSection = $r['section']; @endphp
                        <tr class="sec-header"><td colspan="{{ $colspan }}">{{ $r['section'] }}</td></tr>
                    @endif
                    @php
                        [$label, $bg, $fg] = $statusMap[$r['status']] ?? $statusMap['none'];
                        $pct = min(100, max(0, $r['pct']));
                        $rowIdx++;
                    @endphp
                    <tr class="item-row {{ $rowIdx % 2 === 0 ? 'item-row--zebra' : '' }}" wire:key="short-{{ $r['item_id'] }}">
                        <td class="col-item"><span class="col-item__code">{{ $r['code'] }}</span></td>
                        <td class="col-item">
                            <span class="col-item__name">{{ $r['name'] }}</span>
                            @if ($r['is_override'])<span title="تجاوز يدوي" style="color:var(--crown-warning);">✎</span>@endif
                        </td>
                        <td class="col-num crown-num">{{ number_format($r['required']) }}</td>
                        @if ($showShipmentCols)
                            @foreach ($shipments as $sh)
                                <td class="col-num crown-num" style="color:var(--crown-text-muted);">
                                    {{ isset($r['matrix'][$sh['id']]) ? number_format($r['matrix'][$sh['id']]) : '—' }}
                                </td>
                            @endforeach
                        @endif
                        @if ($showActiveShipmentQty)
                            <td class="col-num crown-num" style="font-weight:700;color:var(--crown-primary);">
                                {{ number_format($r['active_qty']) }}
                            </td>
                        @endif
                        <td class="col-num col-delivered crown-num">{{ number_format($r['delivered']) }}</td>
                        <td class="col-num crown-num {{ $r['shortage'] > 0 ? 'col-remaining--open' : ($r['shortage'] < 0 ? '' : 'col-remaining--done') }}">
                            {{ $r['shortage'] < 0 ? '('.number_format(abs($r['shortage'])).')' : number_format($r['shortage']) }}
                        </td>
                        <td class="col-num">
                            <div class="crown-bar">
                                <div style="width: {{ $pct }}%; height: 100%; background: {{ $barColor[$r['status']] }};"></div>
                            </div>
                        </td>
                        <td class="col-num">
                            <span class="crown-badge" style="background: {{ $bg }}; color: {{ $fg }};">{{ $label }}</span>
                        </td>
                        <td class="col-num">
                            @include('filament.components.shipment-adjust-cell', [
                                'itemId' => $r['item_id'],
                                'remaining' => max(0, (float) ($r['remaining'] ?? 0)),
                                'activeQty' => (float) ($r['active_qty'] ?? 0),
                                'isOver' => (bool) ($r['is_over'] ?? false),
                            ])
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">الإجمالي ({{ count($rows) }} صنف)</td>
                    <td class="col-num crown-num">{{ number_format($totals['required']) }}</td>
                    @if ($showShipmentCols)<td colspan="{{ count($shipments) }}"></td>@endif
                    @if ($showActiveShipmentQty)<td></td>@endif
                    <td class="col-num crown-num">{{ number_format($totals['delivered']) }}</td>
                    <td class="col-num crown-num">{{ number_format($totals['shortage']) }}</td>
                    <td colspan="3" class="col-num crown-num">{{ $totals['pct'] }}%</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
</x-filament-panels::page>
