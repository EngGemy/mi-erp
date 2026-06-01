<x-filament-panels::page>
    @include('filament.components.crown-theme-global')
    @include('filament.components.crown-shared-styles')

    <style>
        .crown-wbs { direction: rtl; }
        .crown-wbs__hero {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
            border-radius: var(--crown-radius);
            background: var(--crown-sec-bg);
            border: 1px solid var(--crown-grid);
        }
        .crown-wbs__hero-title { font-weight: 700; font-size: 1.125rem; color: var(--crown-text); }
        .crown-wbs__hero-pct {
            font-family: var(--crown-num-font);
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--crown-primary);
        }
        .crown-progress {
            flex: 1;
            min-width: 12rem;
            height: 0.625rem;
            border-radius: 9999px;
            background: var(--crown-grid);
            overflow: hidden;
        }
        .crown-progress__fill {
            height: 100%;
            border-radius: 9999px;
            background: var(--crown-primary);
            transition: width 0.25s ease;
        }
        .crown-wbs-section {
            margin-bottom: 1rem;
            border: 1px solid var(--crown-border);
            border-radius: var(--crown-radius);
            overflow: hidden;
            background: var(--crown-card);
        }
        .crown-wbs-section__head {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            background: var(--crown-sec-bg);
            border-bottom: 1px solid var(--crown-grid);
            color: var(--crown-sec-text);
        }
        .crown-wbs-section__name { font-weight: 700; flex: 1; }
        .crown-wbs-section__pct {
            font-family: var(--crown-num-font);
            font-weight: 700;
            color: var(--crown-primary);
            min-width: 3.5rem;
            text-align: end;
        }
        .crown-wbs-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
        .crown-wbs-table thead th {
            background: var(--crown-table-head-bg);
            color: var(--crown-table-head-text);
            padding: 9px 10px;
            border: 1px solid var(--crown-border);
            font-weight: 600;
        }
        .crown-wbs-table tbody td {
            padding: 9px 10px;
            border: 1px solid var(--crown-grid);
        }
        .crown-wbs-table tbody tr:nth-child(even) td { background: var(--crown-zebra); }
        .crown-wbs-table tbody tr:nth-child(odd) td { background: var(--crown-card); }
        .crown-wbs-item__name { font-weight: 600; color: var(--crown-text); }
        .crown-wbs-item__code { font-size: 0.75rem; color: var(--crown-text-muted); margin-inline-start: 0.35rem; }
        .crown-wbs-qty {
            width: 5.5rem;
            height: 2rem;
            border-radius: 0.375rem;
            border: 1px solid var(--crown-border);
            padding: 0 0.5rem;
            text-align: center;
            font-family: var(--crown-num-font);
            background: var(--crown-card);
            color: var(--crown-text);
        }
        .crown-wbs-pct-sm { font-weight: 700; color: var(--crown-success); white-space: nowrap; font-family: var(--crown-num-font); }
        .crown-wbs-req { color: var(--crown-text-muted); font-size: 0.75rem; }
    </style>

    @php $canEditWbs = auth()->user()?->can('Update:Project') ?? false; @endphp

    <div class="crown-wbs" wire:loading.class="opacity-60" wire:target="updateDoneQty">
        <div class="crown-wbs__hero">
            <div>
                <div class="crown-wbs__hero-title">{{ $record->name }}</div>
                <div style="font-size:0.8125rem; color:var(--crown-text-muted);">نسبة إكتمال المشروع (محفوظة)</div>
            </div>
            <div class="crown-progress" style="max-width:20rem;">
                <div class="crown-progress__fill" style="width: {{ min(100, max(0, $projectPct)) }}%;"></div>
            </div>
            <span class="crown-wbs__hero-pct crown-num">{{ number_format($projectPct, 1) }}%</span>
        </div>

        @forelse ($tree as $section)
            <div class="crown-wbs-section" wire:key="sec-{{ $section['section_id'] }}">
                <div class="crown-wbs-section__head">
                    <span class="crown-wbs-section__name">{{ $section['name'] }}</span>
                    <div class="crown-progress" style="max-width:10rem;">
                        <div class="crown-progress__fill" style="width: {{ min(100, $section['pct']) }}%;"></div>
                    </div>
                    <span class="crown-wbs-section__pct crown-num">{{ number_format($section['pct'], 1) }}%</span>
                </div>

                @if (count($section['items']) > 0)
                    <table class="crown-wbs-table">
                        <thead>
                            <tr>
                                <th style="text-align:right;">الصنف</th>
                                <th>المطلوب (H)</th>
                                <th>الإكتمال</th>
                                @foreach ($section['items'][0]['stages'] ?? [] as $st)
                                    <th>{{ $st['name'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($section['items'] as $item)
                                <tr wire:key="item-{{ $item['item_id'] }}">
                                    <td>
                                        <span class="crown-wbs-item__name">{{ $item['name'] }}</span>
                                        <span class="crown-wbs-item__code">{{ $item['code'] }}</span>
                                    </td>
                                    <td class="col-num crown-num">
                                        <span class="col-h">{{ number_format($item['required'], 0) }}</span>
                                        <span class="crown-wbs-req">من الحصر</span>
                                    </td>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:0.5rem;">
                                            <div class="crown-progress" style="width:4rem;">
                                                <div class="crown-progress__fill" style="width:{{ min(100, $item['pct']) }}%;"></div>
                                            </div>
                                            <span class="crown-wbs-pct-sm">{{ number_format($item['pct'], 1) }}%</span>
                                        </div>
                                    </td>
                                    @foreach ($item['stages'] as $stage)
                                        <td wire:key="st-{{ $item['item_id'] }}-{{ $stage['stage_id'] }}" style="text-align:center;">
                                            @if ($canEditWbs)
                                                <input
                                                    type="number"
                                                    step="any"
                                                    min="0"
                                                    class="crown-wbs-qty"
                                                    value="{{ $stage['done_qty'] }}"
                                                    wire:change="updateDoneQty({{ $item['item_id'] }}, {{ $stage['stage_id'] }}, $event.target.value)"
                                                />
                                            @else
                                                <span class="crown-num">{{ number_format($stage['done_qty'], 0) }}</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <p style="padding:1rem; color:var(--crown-text-muted); margin:0;">لا توجد أصناف نشطة في هذا القسم.</p>
                @endif
            </div>
        @empty
            <p style="color:var(--crown-text-muted);">لا توجد أقسام — أضف أقساماً وأصنافاً من تعديل المشروع.</p>
        @endforelse
    </div>
</x-filament-panels::page>
