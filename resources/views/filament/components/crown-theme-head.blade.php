<script>document.documentElement.setAttribute('dir','rtl');</script>
@include('filament.components.crown-theme-variables')
@include('filament.components.crown-theme-global')
<style>
    html, body { direction: rtl !important; }
    *, *::before, *::after {
        font-family: var(--crown-font, "Cairo", ui-sans-serif, system-ui, sans-serif) !important;
    }
    svg { direction: ltr; }
    input[type="number"], code, pre, .crown-num {
        font-family: var(--crown-num-font, "Cairo", Tahoma, sans-serif) !important;
        font-variant-numeric: tabular-nums;
    }
    input[type="number"], code, pre { direction: ltr; text-align: right; }
</style>
