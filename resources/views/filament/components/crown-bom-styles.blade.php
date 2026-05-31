<style>
    .crown-bom-page {
        direction: rtl;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .crown-bom-page__loading {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.875rem;
        border-radius: var(--crown-radius);
        background: var(--crown-sec-bg);
        border: 1px solid var(--crown-grid);
        color: var(--crown-sec-text);
        font-size: 0.8125rem;
        font-weight: 600;
    }
    .crown-bom-page__loading::before {
        content: '';
        width: 1rem;
        height: 1rem;
        border: 2px solid var(--crown-grid);
        border-top-color: var(--crown-primary);
        border-radius: 50%;
        animation: crown-spin 0.7s linear infinite;
    }
    @keyframes crown-spin { to { transform: rotate(360deg); } }

    .crown-params {
        background: var(--crown-card);
        border: 1px solid var(--crown-border);
        border-radius: var(--crown-radius);
        padding: 1rem 1.125rem;
        box-shadow: 0 1px 2px rgba(31, 41, 55, 0.04);
    }
    .crown-params__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-bottom: 0.875rem;
        flex-wrap: wrap;
    }
    .crown-params__title {
        font-size: 0.9375rem;
        font-weight: 700;
        color: var(--crown-text);
    }
    .crown-params__hint {
        font-size: 0.75rem;
        color: var(--crown-text-muted);
    }
    .crown-params__grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(9.5rem, 1fr));
        gap: 0.625rem;
    }
    .crown-param {
        background: var(--crown-zebra);
        border: 1px solid var(--crown-border);
        border-radius: var(--crown-radius);
        padding: 0.5rem 0.75rem;
    }
    .crown-param:focus-within {
        border-color: var(--crown-primary);
        box-shadow: 0 0 0 2px rgba(224, 36, 36, 0.15);
    }
    .crown-param--accent {
        background: var(--crown-sec-bg);
        border-color: rgba(224, 36, 36, 0.25);
    }
    .crown-param--changed {
        border-color: var(--crown-warning);
        background: rgba(180, 83, 9, 0.08);
    }
    .crown-param__label {
        display: block;
        font-size: 0.6875rem;
        font-weight: 500;
        color: var(--crown-text-muted);
        margin-bottom: 0.35rem;
    }
    .crown-param__input {
        width: 100%;
        height: 2.125rem;
        border: none;
        background: transparent;
        font-family: var(--crown-num-font);
        font-size: 1.125rem;
        font-weight: 700;
        text-align: center;
        color: var(--crown-text);
        font-variant-numeric: tabular-nums;
    }
    .crown-param__input:focus { outline: none; }

    .crown-table-toolbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .crown-table-toolbar__left {
        font-size: 0.8125rem;
        color: var(--crown-text-muted);
    }

    .crown-bom-table thead th .col-letter {
        display: inline-block;
        font-size: 0.625rem;
        font-weight: 700;
        padding: 0.1rem 0.35rem;
        border-radius: 0.2rem;
        background: var(--crown-charcoal-soft);
        color: #fff;
        margin-bottom: 0.2rem;
    }

    .crown-delivery-bar {
        background: var(--crown-grid);
        height: 0.375rem;
        border-radius: 0.25rem;
        overflow: hidden;
        min-width: 4rem;
        margin-bottom: 0.2rem;
    }
    .crown-delivery-bar__fill {
        height: 100%;
        background: var(--crown-primary);
        border-radius: 0.25rem;
    }
    .crown-delivery-bar__pct {
        font-size: 0.6875rem;
        color: var(--crown-text-muted);
        font-family: var(--crown-num-font);
    }
    .crown-bom-table .col-shipment {
        min-width: 4.5rem;
        font-size: 0.6875rem;
    }
    .crown-bom-table .col-shipment__date {
        display: block;
        font-size: 0.625rem;
        opacity: 0.8;
    }
    .crown-bom-table .col-formula {
        max-width: 12rem;
        font-size: 0.6875rem;
        font-family: ui-monospace, monospace;
        color: var(--crown-text-muted);
        direction: ltr;
        text-align: left;
        word-break: break-all;
    }
    .crown-bom-table--preview tbody tr.item-row td {
        background: rgba(180, 83, 9, 0.08) !important;
    }
    .crown-bom-err {
        border: 1px solid rgba(224, 36, 36, 0.35);
        background: var(--crown-sec-bg);
        color: var(--crown-primary-dark);
        padding: 0.75rem 1rem;
        border-radius: var(--crown-radius);
        font-size: 0.8125rem;
    }
    .crown-preview-bar {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
        padding: 0.625rem 1rem;
        border-radius: var(--crown-radius);
        background: rgba(180, 83, 9, 0.1);
        border: 1px solid rgba(180, 83, 9, 0.35);
        color: var(--crown-warning);
    }
    .crown-preview-badge {
        display: inline-block;
        font-size: 0.625rem;
        padding: 0.125rem 0.375rem;
        border-radius: 0.25rem;
        background: var(--crown-warning);
        color: #fff;
        margin-inline-start: 0.375rem;
    }
    .crown-ship-bar--soft {
        background: var(--crown-card);
        border: 1px solid var(--crown-border);
        color: var(--crown-text);
    }
</style>
