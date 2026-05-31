<x-filament-panels::page>
    @include('filament.components.crown-shared-styles')

    <style>
        .crown-report-summary {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
            direction: rtl;
        }
        .crown-report-summary .crown-stat {
            background: rgb(var(--gray-50));
            border: 0.5px solid rgb(var(--gray-200));
            border-radius: 0.5rem;
            padding: 0.75rem 1rem;
            min-width: 10rem;
        }
        .dark .crown-report-summary .crown-stat {
            background: rgb(var(--gray-800));
            border-color: rgb(var(--gray-600));
        }
        .crown-report-table-wrap {
            overflow: auto;
            border: 0.5px solid rgb(var(--gray-300));
            border-radius: 0.375rem;
            background: rgb(var(--white));
        }
        .dark .crown-report-table-wrap {
            border-color: rgb(var(--gray-600));
            background: rgb(var(--gray-900));
        }
        .crown-report-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
            direction: rtl;
        }
        .crown-report-table th {
            background: rgb(var(--gray-950));
            color: rgb(var(--gray-50));
            padding: 0.625rem 0.75rem;
            text-align: right;
            white-space: nowrap;
        }
        .dark .crown-report-table th { background: rgb(var(--gray-800)); }
        .crown-report-table td {
            padding: 0.5rem 0.75rem;
            border-bottom: 0.5px solid rgb(var(--gray-200));
        }
        .dark .crown-report-table td { border-color: rgb(var(--gray-700)); }
        .crown-report-table tbody tr:hover { background: rgb(var(--gray-50)); }
        .dark .crown-report-table tbody tr:hover { background: rgb(var(--gray-800)); }
        .crown-detail-row td {
            background: rgb(var(--gray-50));
            padding: 0;
        }
        .dark .crown-detail-row td { background: rgb(var(--gray-950)); }
        .crown-detail-inner {
            padding: 0.75rem 1rem 1rem;
        }
        .crown-detail-table {
            width: 100%;
            font-size: 0.75rem;
            border-collapse: collapse;
        }
        .crown-detail-table th, .crown-detail-table td {
            padding: 0.375rem 0.5rem;
            border: 0.5px solid rgb(var(--gray-200));
            text-align: right;
        }
        .crown-expand-btn {
            background: transparent;
            border: none;
            cursor: pointer;
            color: rgb(var(--primary-600));
            font-size: 0.8125rem;
        }
    </style>

    <div class="crown-report-summary">
        <div class="crown-stat">
            <div class="l" style="font-size:0.6875rem;color:rgb(var(--gray-500));">المشروع</div>
            <div class="v" style="font-weight:600;">{{ $record->name }}</div>
        </div>
        <div class="crown-stat">
            <div class="l" style="font-size:0.6875rem;color:rgb(var(--gray-500));">عدد الحمولات</div>
            <div class="v" style="font-weight:600;">{{ count($shipments) }}</div>
        </div>
        <div class="crown-stat">
            <div class="l" style="font-size:0.6875rem;color:rgb(var(--gray-500));">إجمالي المُسلّم</div>
            <div class="v" style="font-weight:600;">{{ number_format($projectTotalDelivered) }}</div>
        </div>
    </div>

    <div class="crown-report-table-wrap">
        <table class="crown-report-table">
            <thead>
                <tr>
                    <th></th>
                    <th>اسم الحمولة</th>
                    <th>التاريخ</th>
                    <th>السائق</th>
                    <th>السيارة</th>
                    <th>المسؤول</th>
                    <th>موعد الوصول</th>
                    <th style="text-align:center;">عدد الأصناف</th>
                    <th style="text-align:center;">إجمالي الكمية</th>
                    <th style="text-align:center;">نسبة المساهمة</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($shipments as $sh)
                    <tr wire:key="sh-{{ $sh['id'] }}">
                        <td style="width:2rem;text-align:center;">
                            <button type="button" class="crown-expand-btn" wire:click="toggleShipment({{ $sh['id'] }})">
                                {{ $expandedShipmentId === $sh['id'] ? '▼' : '◀' }}
                            </button>
                        </td>
                        <td style="font-weight:600;">{{ $sh['name'] }}</td>
                        <td>{{ $sh['shipped_at'] ?? '—' }}</td>
                        <td>{{ $sh['driver_name'] ?? '—' }}</td>
                        <td>{{ $sh['vehicle_no'] ?? '—' }}</td>
                        <td>{{ $sh['responsible'] ?? '—' }}</td>
                        <td>{{ $sh['arrival_time'] ?? '—' }}</td>
                        <td style="text-align:center;">{{ $sh['items_count'] }}</td>
                        <td style="text-align:center;font-weight:600;">{{ number_format($sh['total_qty']) }}</td>
                        <td style="text-align:center;">{{ $sh['contribution_pct'] }}%</td>
                    </tr>
                    @if ($expandedShipmentId === $sh['id'])
                        <tr class="crown-detail-row" wire:key="sh-detail-{{ $sh['id'] }}">
                            <td colspan="10">
                                <div class="crown-detail-inner">
                                    @if ($sh['notes'])
                                        <p style="font-size:0.75rem;color:rgb(var(--gray-500));margin-bottom:0.5rem;">ملاحظات: {{ $sh['notes'] }}</p>
                                    @endif
                                    @if (count($sh['items']) > 0)
                                        <table class="crown-detail-table" dir="rtl">
                                            <thead>
                                                <tr>
                                                    <th>الكود</th>
                                                    <th>بيان الصنف</th>
                                                    <th style="text-align:center;">الكمية</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($sh['items'] as $item)
                                                    <tr>
                                                        <td style="font-family:monospace;">{{ $item['code'] }}</td>
                                                        <td>{{ $item['name'] }}</td>
                                                        <td style="text-align:center;font-weight:600;">{{ number_format($item['quantity']) }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <p style="font-size:0.8125rem;color:rgb(var(--gray-500));">لا توجد أصناف مسجّلة في هذه الحمولة.</p>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr>
                        <td colspan="10" style="text-align:center;padding:2rem;color:rgb(var(--gray-500));">لا توجد حمولات مسجّلة لهذا المشروع.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
