<x-filament-panels::page>
    @php
        $formulaMap = $record->items->mapWithKeys(fn ($i) => [$i->code => $i->formula]);
        $grouped = collect($rows)->groupBy('section');
        $grandTotal = collect($rows)->sum('total');
        $isPreview = $this->hasUnsavedChanges;
        $dt = $deliveryTotals;
        $colCount = 7 + 3 + ($showShipmentCols ? count($shipments) : 0) + 1 + ($showFormulas ? 1 : 0);
    @endphp

    @include('filament.components.crown-theme-global')
    @include('filament.components.crown-shared-styles')
    @include('filament.components.crown-bom-styles')

    <div class="crown-bom-page">
        <div wire:loading.delay.shortest
             wire:target="previewVariable, previewUnitsMultiplier, confirmSave, discardChanges, adjust, fillShortage, createShipment, updateActiveShipment, updatedActiveShipmentId, toggleShipmentCols"
             class="crown-bom-page__loading">
            جارٍ إعادة الحساب...
        </div>

        @include('filament.components.shipment-bar', ['soft' => true])

        @php $canEditProject = auth()->user()?->can('Update:Project') ?? false; @endphp

        @if ($isPreview && $canEditProject)
            <div class="crown-preview-bar">
                <span>لديك تغييرات غير محفوظة — المعاينة دون حفظ في قاعدة البيانات</span>
                <div class="crown-ship-actions" style="margin-inline-start: auto;">
                    <button type="button" wire:click="discardChanges" class="crown-btn crown-btn--secondary">تراجع</button>
                    <button type="button" wire:click="confirmSave" class="crown-btn crown-btn--primary">تأكيد وحفظ</button>
                </div>
            </div>
        @endif

        @if (count($errors) > 0)
            <div class="crown-bom-err">
                <strong>أخطاء في المعادلات ({{ count($errors) }}):</strong>
                <ul style="margin: 0.5rem 0 0; padding-inline-start: 1.25rem;">
                    @foreach ($errors as $e)
                        <li>{{ $e['name'] }} — {{ $e['error'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <section class="crown-params">
            <div class="crown-params__head">
                <div>
                    <div class="crown-params__title">معاملات الحصر</div>
                    <div class="crown-params__hint">عدّل القيمة ثم اضغط خارج الحقل — يُحدَّث الجدول فوراً</div>
                </div>
                @if ($isPreview)
                    <span class="crown-preview-badge">وضع المعاينة</span>
                @endif
            </div>
            <div class="crown-params__grid">
                @foreach ($record->variables->sortBy('sort') as $v)
                    @php $changed = abs(($varValues[$v->id] ?? 0) - ($savedVarValues[$v->id] ?? 0)) > 0.000001; @endphp
                    <div class="crown-param {{ $changed ? 'crown-param--changed' : '' }}" wire:key="var-{{ $v->id }}">
                        <label class="crown-param__label" for="var-{{ $v->id }}">{{ $v->label }}</label>
                        <input
                            id="var-{{ $v->id }}"
                            type="number"
                            step="any"
                            class="crown-param__input"
                            @disabled(! $canEditProject)
                            wire:model="varValues.{{ $v->id }}"
                            wire:change="previewVariable({{ $v->id }}, $event.target.value)"
                        />
                    </div>
                @endforeach
                <div class="crown-param crown-param--accent {{ abs($unitsMultiplier - $savedUnitsMultiplier) > 0.000001 ? 'crown-param--changed' : '' }}">
                    <label class="crown-param__label" for="units-mult">مضاعف العنابر (H)</label>
                    <input
                        id="units-mult"
                        type="number"
                        step="any"
                        min="0.01"
                        class="crown-param__input"
                        @disabled(! $canEditProject)
                        wire:model="unitsMultiplier"
                        wire:change="previewUnitsMultiplier($event.target.value)"
                    />
                </div>
            </div>
        </section>

        <div class="crown-kpi-row">
            <div class="crown-kpi">
                <div class="crown-kpi__label">عدد الأصناف</div>
                <div class="crown-kpi__value">{{ count($rows) }}</div>
            </div>
            <div class="crown-kpi crown-kpi--primary">
                <div class="crown-kpi__label">إجمالي العنابر (H)</div>
                <div class="crown-kpi__value crown-num">{{ number_format($grandTotal, 0) }}</div>
            </div>
            <div class="crown-kpi crown-kpi--success">
                <div class="crown-kpi__label">المُسلّم فعلياً</div>
                <div class="crown-kpi__value crown-num">{{ number_format($dt['delivered'] ?? 0, 0) }}</div>
            </div>
            <div class="crown-kpi crown-kpi--danger">
                <div class="crown-kpi__label">المتبقي</div>
                <div class="crown-kpi__value crown-num">{{ number_format($dt['remaining'] ?? 0, 0) }}</div>
            </div>
            <div class="crown-kpi crown-kpi--neutral">
                <div class="crown-kpi__label">نسبة التوريد</div>
                <div class="crown-kpi__value crown-num">{{ $dt['pct'] ?? 0 }}%</div>
            </div>
        </div>

        <div class="crown-table-toolbar">
            <span class="crown-table-toolbar__left">
                أعمدة D–H + التوريد (نواقص كراون) — مرّر أفقياً لرؤية الحمولات
            </span>
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                <button type="button" wire:click="toggleShipmentCols"
                        class="crown-toggle-btn {{ $showShipmentCols ? 'crown-toggle-btn--on' : '' }}">
                    {{ $showShipmentCols ? 'إخفاء أعمدة الحمولات' : 'إظهار أعمدة الحمولات' }}
                </button>
                <button type="button" wire:click="toggleFormulas"
                        class="crown-toggle-btn {{ $showFormulas ? 'crown-toggle-btn--on' : '' }}">
                    {{ $showFormulas ? 'إخفاء المعادلات' : 'إظهار المعادلات' }}
                </button>
            </div>
        </div>

        <div class="crown-table-wrap">
            <div class="crown-table-scroll">
                <table class="crown-table crown-bom-table {{ $isPreview ? 'crown-bom-table--preview' : '' }}">
                    <thead>
                        <tr>
                            <th class="col-item col-text">بيان الصنف</th>
                            <th class="col-num">طول<br><span class="col-letter">م</span></th>
                            <th class="col-num">صافي<br><span class="col-letter">D</span></th>
                            <th class="col-num">زيادة<br><span class="col-letter">E</span></th>
                            <th class="col-num">بالزيادة<br><span class="col-letter">F</span></th>
                            <th class="col-num">نسبة %<br><span class="col-letter">G</span></th>
                            <th class="col-num">اجمالي العنابر<br><span class="col-letter">H</span></th>
                            <th class="col-num col-delivered">المُسلّم<br>فعلياً</th>
                            <th class="col-num col-remaining">المتبقي</th>
                            <th class="col-num col-delivery-pct">نسبة التوريد %</th>
                            @if ($showShipmentCols)
                                @foreach ($shipments as $sh)
                                    <th class="col-num col-shipment" title="{{ $sh['shipped_at'] ?? '' }}">
                                        {{ $sh['name'] }}
                                        @if (! empty($sh['shipped_at']))
                                            <span class="col-shipment__date">{{ $sh['shipped_at'] }}</span>
                                        @endif
                                    </th>
                                @endforeach
                            @endif
                            <th class="col-num" style="min-width:7.5rem;">توريد</th>
                            @if ($showFormulas)
                                <th class="col-formula">المعادلة</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($grouped as $sectionName => $sectionRows)
                            <tr class="sec-header">
                                <td colspan="{{ $colCount }}">
                                    {{ $sectionName ?: 'بدون قسم' }}
                                    <span style="font-weight:400; opacity:0.75; margin-inline-start:0.5rem;">
                                        ({{ count($sectionRows) }} صنف)
                                    </span>
                                </td>
                            </tr>
                            @foreach ($sectionRows as $r)
                                @php
                                    $hasError = ! empty($r['error']);
                                    $shortRow = $shortageByItem[$r['id']] ?? null;
                                    $delivered = $shortRow['delivered'] ?? 0;
                                    $remaining = $shortRow['remaining'] ?? 0;
                                    $isOver = $shortRow['is_over'] ?? false;
                                    $pct = min(100, max(0, $shortRow['pct'] ?? 0));
                                @endphp
                                <tr class="item-row {{ $loop->even ? 'item-row--zebra' : '' }}" wire:key="bom-{{ $r['code'] }}">
                                    <td class="col-item">
                                        @if ($hasError)
                                            <span style="color: rgb(var(--danger-600)); font-size: 0.6875rem;">{{ $r['error'] }}</span>
                                        @endif
                                        <span class="col-item__name">{{ $r['name'] }}</span>
                                        <span class="col-item__code">{{ $r['code'] }}</span>
                                    </td>
                                    <td class="col-num">{{ $r['length'] ?? '—' }}</td>
                                    <td class="col-num">{{ number_format($r['net'], 0) }}</td>
                                    <td class="col-num" style="color: rgb(var(--gray-500));">{{ number_format($r['scrap'], 0) }}</td>
                                    <td class="col-num" style="font-weight:600;">{{ number_format($r['gross'], 0) }}</td>
                                    <td class="col-num" style="color: rgb(var(--gray-500));">{{ number_format($r['scrap_pct'], 1) }}%</td>
                                    <td class="col-num col-h crown-num">{{ number_format($r['total'], 0) }}</td>
                                    <td class="col-num col-delivered">{{ number_format($delivered, 0) }}</td>
                                    <td class="col-num col-remaining crown-num {{ $isOver ? 'col-remaining--over' : ($remaining > 0 ? 'col-remaining--open' : 'col-remaining--done') }}">
                                        @if ($isOver)
                                            <span title="تسليم زائد">زائد {{ number_format($shortRow['over_qty'] ?? 0, 0) }}</span>
                                        @else
                                            {{ number_format($remaining, 0) }}
                                        @endif
                                    </td>
                                    <td class="col-num col-delivery-pct">
                                        <div class="crown-delivery-bar">
                                            <div class="crown-delivery-bar__fill" style="width:{{ $pct }}%;"></div>
                                        </div>
                                        <span class="crown-delivery-bar__pct">{{ number_format($pct, 1) }}%</span>
                                    </td>
                                    @if ($showShipmentCols)
                                        @foreach ($shipments as $sh)
                                            <td class="col-num col-shipment-cell">
                                                {{ isset($shortRow['matrix'][$sh['id']]) ? number_format($shortRow['matrix'][$sh['id']], 0) : '—' }}
                                            </td>
                                        @endforeach
                                    @endif
                                    <td class="col-num">
                                        @if ($canEditProject)
                                            @include('filament.components.shipment-adjust-cell', [
                                                'itemId' => $r['id'],
                                                'compact' => true,
                                                'remaining' => $remaining,
                                                'activeQty' => (float) ($shortRow['active_qty'] ?? 0),
                                                'isOver' => $isOver,
                                            ])
                                        @else
                                            <span style="color:rgb(var(--gray-400));">—</span>
                                        @endif
                                    </td>
                                    @if ($showFormulas)
                                        <td class="col-formula" title="{{ $formulaMap[$r['code']] ?? '' }}">
                                            {{ $formulaMap[$r['code']] ?? '—' }}
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="6" style="text-align: right;">
                                الإجمالي — {{ count($rows) }} صنف
                            </td>
                            <td class="col-num col-h" style="text-align: center;">{{ number_format($grandTotal, 0) }}</td>
                            <td class="col-num col-delivered" style="text-align: center;">{{ number_format($dt['delivered'] ?? 0, 0) }}</td>
                            <td class="col-num col-remaining {{ ($dt['remaining'] ?? 0) > 0 ? 'col-remaining--open' : 'col-remaining--done' }}" style="text-align: center;">
                                {{ number_format($dt['remaining'] ?? 0, 0) }}
                            </td>
                            <td class="col-num col-delivery-pct" style="text-align: center;">
                                <div class="crown-delivery-bar" style="max-width:6rem;margin:0 auto;">
                                    <div class="crown-delivery-bar__fill" style="width:{{ min(100, $dt['pct'] ?? 0) }}%;"></div>
                                </div>
                                {{ $dt['pct'] ?? 0 }}%
                            </td>
                            @if ($showShipmentCols)
                                <td colspan="{{ count($shipments) }}"></td>
                            @endif
                            <td colspan="{{ $showFormulas ? 2 : 1 }}"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
