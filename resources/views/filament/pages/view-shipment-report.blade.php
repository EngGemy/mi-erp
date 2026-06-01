<x-filament-panels::page>
    @include('filament.components.crown-theme-global')
    @include('filament.components.crown-buttons-unified')
    @include('filament.components.crown-shipment-report-styles')

    @php
        $shipmentCount = count($shipments);
        $totalItems = collect($shipments)->sum('items_count');
        $avgPerShipment = $shipmentCount > 0 ? round($projectTotalDelivered / $shipmentCount, 0) : 0;
        $canManage = auth()->user()?->can('Update:Project') ?? false;
        $canShortage = auth()->user()?->can('View:ViewShortage') ?? false;
        $editUrl = \App\Filament\Resources\ProjectResource::getUrl('edit', ['record' => $record]);
        $shortageUrl = \App\Filament\Resources\ProjectResource\Pages\ViewShortage::getUrl(['record' => $record]);
    @endphp

    <div class="crown-ship-report">
        {{-- Hero --}}
        <header class="crown-ship-report__hero">
            <div class="crown-ship-report__hero-main">
                <div class="crown-ship-report__hero-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9m9 9H9.375" />
                    </svg>
                </div>
                <div>
                    <h2 class="crown-ship-report__title">تقرير الحمولات والتوريد</h2>
                    <p class="crown-ship-report__subtitle">
                        {{ $record->name }}
                        <code>{{ $record->code }}</code>
                    </p>
                </div>
            </div>
            <div class="crown-ship-report__quick">
                @if ($canShortage)
                    <a href="{{ $shortageUrl }}" class="crown-btn crown-btn--ghost">متابعة النواقص</a>
                @endif
                @if ($canManage)
                    <a href="{{ $editUrl }}" class="crown-btn crown-btn--primary">إدارة الحمولات</a>
                @endif
            </div>
        </header>

        {{-- KPIs --}}
        <div class="crown-ship-report__kpi">
            <div class="crown-ship-report__kpi-card crown-ship-report__kpi-card--primary">
                <span class="crown-ship-report__kpi-label">عدد الحمولات</span>
                <span class="crown-ship-report__kpi-value">{{ number_format($shipmentCount) }}</span>
            </div>
            <div class="crown-ship-report__kpi-card crown-ship-report__kpi-card--success">
                <span class="crown-ship-report__kpi-label">إجمالي المُسلّم</span>
                <span class="crown-ship-report__kpi-value">{{ number_format($projectTotalDelivered) }}</span>
            </div>
            <div class="crown-ship-report__kpi-card">
                <span class="crown-ship-report__kpi-label">أصناف مُسجّلة</span>
                <span class="crown-ship-report__kpi-value">{{ number_format($totalItems) }}</span>
            </div>
            <div class="crown-ship-report__kpi-card">
                <span class="crown-ship-report__kpi-label">متوسط التوريد / حمولة</span>
                <span class="crown-ship-report__kpi-value">{{ number_format($avgPerShipment) }}</span>
            </div>
        </div>

        {{-- Table panel --}}
        <section class="crown-ship-report__panel">
            <div class="crown-ship-report__panel-head">
                <div>
                    <h3 class="crown-ship-report__panel-title">سجل الحمولات</h3>
                    <span class="crown-ship-report__panel-hint">انقر على الصف لعرض الأصناف والتفاصيل</span>
                </div>
            </div>

            @if ($shipmentCount > 0)
                <div class="crown-ship-report__table-scroll">
                    <table class="crown-ship-report__table" dir="rtl">
                        <thead>
                            <tr>
                                <th style="width:3rem;" class="col-center"></th>
                                <th>الحمولة</th>
                                <th>السائق</th>
                                <th>السيارة</th>
                                <th>المسؤول</th>
                                <th>موعد الوصول</th>
                                <th class="col-center">أصناف</th>
                                <th class="col-center">الكمية</th>
                                <th class="col-center">المساهمة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($shipments as $index => $sh)
                                @php
                                    $isOpen = $expandedShipmentId === $sh['id'];
                                    $pct = min(100, max(0, (float) $sh['contribution_pct']));
                                @endphp
                                <tr
                                    wire:key="sh-{{ $sh['id'] }}"
                                    class="ship-row {{ $isOpen ? 'is-open' : '' }}"
                                    wire:click="toggleShipment({{ $sh['id'] }})"
                                    role="button"
                                    tabindex="0"
                                    aria-expanded="{{ $isOpen ? 'true' : 'false' }}"
                                >
                                    <td class="col-center col-expand-only" data-label="">
                                        <button
                                            type="button"
                                            class="crown-ship-report__expand {{ $isOpen ? 'is-open' : '' }}"
                                            wire:click.stop="toggleShipment({{ $sh['id'] }})"
                                            aria-label="{{ $isOpen ? 'طي التفاصيل' : 'عرض التفاصيل' }}"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                                            </svg>
                                        </button>
                                    </td>
                                    <td data-label="الحمولة">
                                        <div class="crown-ship-report__name-cell">
                                            <span class="crown-ship-report__badge">{{ $index + 1 }}</span>
                                            <div>
                                                <div class="crown-ship-report__name">{{ $sh['name'] }}</div>
                                                @if ($sh['shipped_at'])
                                                    <div class="crown-ship-report__date">{{ $sh['shipped_at'] }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td data-label="السائق">
                                        <span class="crown-ship-report__meta-pill {{ empty($sh['driver_name']) ? 'crown-ship-report__meta-pill--empty' : '' }}">
                                            {{ $sh['driver_name'] ?? '—' }}
                                        </span>
                                    </td>
                                    <td data-label="السيارة">
                                        <span class="crown-ship-report__meta-pill {{ empty($sh['vehicle_no']) ? 'crown-ship-report__meta-pill--empty' : '' }}">
                                            {{ $sh['vehicle_no'] ?? '—' }}
                                        </span>
                                    </td>
                                    <td data-label="المسؤول">
                                        <span class="crown-ship-report__meta-pill {{ empty($sh['responsible']) ? 'crown-ship-report__meta-pill--empty' : '' }}">
                                            {{ $sh['responsible'] ?? '—' }}
                                        </span>
                                    </td>
                                    <td data-label="موعد الوصول">
                                        <span class="crown-ship-report__meta-pill {{ empty($sh['arrival_time']) ? 'crown-ship-report__meta-pill--empty' : '' }}">
                                            {{ $sh['arrival_time'] ?? '—' }}
                                        </span>
                                    </td>
                                    <td data-label="أصناف" class="crown-ship-report__qty">{{ $sh['items_count'] }}</td>
                                    <td data-label="الكمية" class="crown-ship-report__qty">{{ number_format($sh['total_qty']) }}</td>
                                    <td data-label="المساهمة">
                                        <div class="crown-ship-report__contrib">
                                            <div class="crown-ship-report__contrib-bar" role="progressbar" aria-valuenow="{{ $pct }}" aria-valuemin="0" aria-valuemax="100">
                                                <div class="crown-ship-report__contrib-fill" style="width: {{ $pct }}%;"></div>
                                            </div>
                                            <span class="crown-ship-report__contrib-pct">{{ $sh['contribution_pct'] }}%</span>
                                        </div>
                                    </td>
                                </tr>
                                @if ($isOpen)
                                    <tr class="crown-ship-report__detail" wire:key="sh-detail-{{ $sh['id'] }}">
                                        <td colspan="9">
                                            <div class="crown-ship-report__detail-inner" wire:click.stop>
                                                <div class="crown-ship-report__detail-grid">
                                                    <dl class="crown-ship-report__detail-field">
                                                        <dt>تاريخ التوريد</dt>
                                                        <dd>{{ $sh['shipped_at'] ?? '—' }}</dd>
                                                    </dl>
                                                    <dl class="crown-ship-report__detail-field">
                                                        <dt>السائق</dt>
                                                        <dd>{{ $sh['driver_name'] ?? '—' }}</dd>
                                                    </dl>
                                                    <dl class="crown-ship-report__detail-field">
                                                        <dt>رقم السيارة</dt>
                                                        <dd>{{ $sh['vehicle_no'] ?? '—' }}</dd>
                                                    </dl>
                                                    <dl class="crown-ship-report__detail-field">
                                                        <dt>المسؤول</dt>
                                                        <dd>{{ $sh['responsible'] ?? '—' }}</dd>
                                                    </dl>
                                                    <dl class="crown-ship-report__detail-field">
                                                        <dt>موعد الوصول</dt>
                                                        <dd>{{ $sh['arrival_time'] ?? '—' }}</dd>
                                                    </dl>
                                                    <dl class="crown-ship-report__detail-field">
                                                        <dt>إجمالي الكمية</dt>
                                                        <dd class="crown-num">{{ number_format($sh['total_qty']) }}</dd>
                                                    </dl>
                                                </div>

                                                @if ($sh['notes'])
                                                    <div class="crown-ship-report__notes">
                                                        <strong>ملاحظات:</strong> {{ $sh['notes'] }}
                                                    </div>
                                                @endif

                                                @if ($canManage)
                                                    <div class="crown-ship-report__detail-actions">
                                                        <a
                                                            href="{{ \App\Filament\Pages\ShipmentEntry::getUrl(['shipment' => $sh['id']]) }}"
                                                            class="crown-btn crown-btn--primary"
                                                        >
                                                            إدخال / تعديل الأصناف
                                                        </a>
                                                    </div>
                                                @endif

                                                <h4 class="crown-ship-report__items-title">
                                                    أصناف الحمولة ({{ count($sh['items']) }})
                                                </h4>

                                                @if (count($sh['items']) > 0)
                                                    <table class="crown-ship-report__items-table" dir="rtl">
                                                        <thead>
                                                            <tr>
                                                                <th>الكود</th>
                                                                <th>بيان الصنف</th>
                                                                <th style="text-align:center;">الكمية</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($sh['items'] as $item)
                                                                <tr wire:key="item-{{ $sh['id'] }}-{{ $item['item_id'] }}">
                                                                    <td class="crown-ship-report__code">{{ $item['code'] }}</td>
                                                                    <td>{{ $item['name'] }}</td>
                                                                    <td class="crown-ship-report__qty">{{ number_format($item['quantity']) }}</td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                @else
                                                    <p style="font-size:0.8125rem;color:var(--crown-text-muted);margin:0;">
                                                        لا توجد أصناف مسجّلة — استخدم «إدخال الأصناف» لإضافة الكميات.
                                                    </p>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="crown-ship-report__empty">
                    <div class="crown-ship-report__empty-icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 0 1-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m3 0h1.125c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9m9 9H9.375" />
                        </svg>
                    </div>
                    <h3>لا توجد حمولات بعد</h3>
                    <p>أنشئ أول حمولة من إدارة المشروع، ثم سجّل الأصناف والكميات المُورَّدة.</p>
                    @if ($canManage)
                        <a href="{{ $editUrl }}" class="crown-btn crown-btn--primary">إضافة حمولة</a>
                    @endif
                </div>
            @endif
        </section>
    </div>
</x-filament-panels::page>
