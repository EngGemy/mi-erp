@php
    $pct = min(100, max(0, (float) ($getState() ?? 0)));
@endphp

<div style="display:flex;align-items:center;gap:0.5rem;min-width:6rem;direction:rtl;">
    <div style="flex:1;height:0.5rem;border-radius:9999px;background:var(--crown-grid,#e6e8ec);overflow:hidden;">
        <div style="height:100%;width:{{ $pct }}%;border-radius:9999px;background:var(--crown-primary,#e02424);"></div>
    </div>
    <span class="crown-num" style="font-size:0.75rem;font-weight:700;color:var(--crown-primary,#e02424);white-space:nowrap;">{{ number_format($pct, 1) }}%</span>
</div>
