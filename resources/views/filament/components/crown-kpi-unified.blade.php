<style>
    .crown-kpi-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
        gap: 0.625rem;
    }
    .crown-kpi {
        background: var(--crown-card);
        border: 1px solid var(--crown-border);
        border-radius: var(--crown-radius);
        padding: 0.75rem 1rem;
        box-shadow: 0 1px 2px rgba(31, 41, 55, 0.04);
    }
    .crown-kpi__label {
        font-size: 0.75rem;
        color: var(--crown-text-muted);
        margin-bottom: 0.25rem;
        font-weight: 500;
    }
    .crown-kpi__value {
        font-family: var(--crown-num-font);
        font-size: 1.5rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        color: var(--crown-charcoal);
        line-height: 1.2;
    }
    .dark .crown-kpi__value {
        color: var(--crown-text);
    }
    .crown-kpi--primary .crown-kpi__value { color: var(--crown-primary); }
    .crown-kpi--success .crown-kpi__value { color: var(--crown-success); }
    .crown-kpi--danger .crown-kpi__value { color: var(--crown-danger); }
    .crown-kpi--warning .crown-kpi__value { color: var(--crown-warning); }
    .crown-kpi--neutral .crown-kpi__value { color: var(--crown-charcoal); }

    /* شبكة إحصاءات (نواقص) */
    .crown-stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(9rem, 1fr));
        gap: 0.625rem;
        margin-bottom: 1rem;
    }
    .crown-stat {
        background: var(--crown-card);
        border: 1px solid var(--crown-border);
        border-radius: var(--crown-radius);
        padding: 0.75rem 1rem;
    }
    .crown-stat .l {
        font-size: 0.75rem;
        color: var(--crown-text-muted);
    }
    .crown-stat .v {
        font-family: var(--crown-num-font);
        font-size: 1.375rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        color: var(--crown-charcoal);
        margin-top: 0.15rem;
    }
    .dark .crown-stat .v { color: var(--crown-text); }
    .crown-stat--ok .v { color: var(--crown-success); }
    .crown-stat--bad .v { color: var(--crown-danger); }
    .crown-stat--warn .v { color: var(--crown-warning); }
</style>
