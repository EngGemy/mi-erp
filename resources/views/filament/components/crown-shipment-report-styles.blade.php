<style>
    .crown-ship-report {
        direction: rtl;
        display: flex;
        flex-direction: column;
        gap: 1.25rem;
    }

    .crown-ship-report__hero {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
        padding: 1.25rem 1.5rem;
        border-radius: var(--crown-radius);
        border: 1px solid var(--crown-border);
        background: linear-gradient(135deg, var(--crown-sec-bg) 0%, var(--crown-card) 55%);
        box-shadow: 0 1px 3px rgba(31, 41, 55, 0.06);
    }

    .crown-ship-report__hero-main {
        display: flex;
        align-items: center;
        gap: 1rem;
        min-width: 0;
    }

    .crown-ship-report__hero-icon {
        flex-shrink: 0;
        width: 3rem;
        height: 3rem;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--crown-primary);
        color: #fff;
        box-shadow: 0 4px 12px color-mix(in srgb, var(--crown-primary) 35%, transparent);
    }

    .crown-ship-report__hero-icon svg {
        width: 1.5rem;
        height: 1.5rem;
    }

    .crown-ship-report__title {
        font-size: 1.25rem;
        font-weight: 800;
        color: var(--crown-text);
        line-height: 1.3;
        margin: 0;
    }

    .crown-ship-report__subtitle {
        font-size: 0.8125rem;
        color: var(--crown-text-muted);
        margin: 0.25rem 0 0;
    }

    .crown-ship-report__subtitle code {
        font-size: 0.75rem;
        padding: 0.1rem 0.4rem;
        border-radius: 0.25rem;
        background: var(--crown-zebra);
        border: 1px solid var(--crown-border);
        direction: ltr;
        display: inline-block;
    }

    .crown-ship-report__quick {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }

    .crown-ship-report__kpi {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr));
        gap: 0.75rem;
    }

    .crown-ship-report__kpi-card {
        background: var(--crown-card);
        border: 1px solid var(--crown-border);
        border-radius: var(--crown-radius);
        padding: 1rem 1.125rem;
        display: flex;
        flex-direction: column;
        gap: 0.35rem;
        box-shadow: 0 1px 2px rgba(31, 41, 55, 0.04);
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }

    .crown-ship-report__kpi-card:hover {
        border-color: color-mix(in srgb, var(--crown-primary) 30%, var(--crown-border));
        box-shadow: 0 4px 12px rgba(31, 41, 55, 0.06);
    }

    .crown-ship-report__kpi-label {
        font-size: 0.75rem;
        font-weight: 500;
        color: var(--crown-text-muted);
    }

    .crown-ship-report__kpi-value {
        font-family: var(--crown-num-font);
        font-size: 1.625rem;
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        color: var(--crown-text);
        line-height: 1.1;
    }

    .crown-ship-report__kpi-card--primary .crown-ship-report__kpi-value {
        color: var(--crown-primary);
    }

    .crown-ship-report__kpi-card--success .crown-ship-report__kpi-value {
        color: var(--crown-success);
    }

    .crown-ship-report__panel {
        background: var(--crown-card);
        border: 1px solid var(--crown-border);
        border-radius: var(--crown-radius);
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(31, 41, 55, 0.06);
    }

    .crown-ship-report__panel-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        padding: 0.875rem 1.25rem;
        border-bottom: 1px solid var(--crown-border);
        background: var(--crown-zebra);
    }

    .crown-ship-report__panel-title {
        font-size: 0.9375rem;
        font-weight: 700;
        color: var(--crown-text);
        margin: 0;
    }

    .crown-ship-report__panel-hint {
        font-size: 0.75rem;
        color: var(--crown-text-muted);
    }

    .crown-ship-report__table-scroll {
        overflow: auto;
        max-height: min(70vh, 780px);
    }

    .crown-ship-report__table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.8125rem;
    }

    .crown-ship-report__table thead th {
        position: sticky;
        top: 0;
        z-index: 5;
        background: var(--crown-table-head-bg);
        color: var(--crown-table-head-text);
        font-weight: 600;
        font-size: 0.75rem;
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--crown-border);
        white-space: nowrap;
        text-align: right;
    }

    .crown-ship-report__table thead th.col-center {
        text-align: center;
    }

    .crown-ship-report__table tbody td {
        padding: 0.875rem 1rem;
        border-bottom: 1px solid var(--crown-grid);
        vertical-align: middle;
        color: var(--crown-text);
    }

    .crown-ship-report__table tbody tr.ship-row {
        cursor: pointer;
        transition: background 0.12s ease;
    }

    .crown-ship-report__table tbody tr.ship-row:hover td {
        background: var(--crown-sec-bg);
    }

    .crown-ship-report__table tbody tr.ship-row.is-open td {
        background: var(--crown-sec-bg);
        border-bottom-color: transparent;
    }

    .crown-ship-report__table tbody tr.ship-row.is-open td:first-child {
        box-shadow: inset 3px 0 0 var(--crown-primary);
    }

    .crown-ship-report__expand {
        width: 2rem;
        height: 2rem;
        border-radius: 0.5rem;
        border: 1px solid var(--crown-border);
        background: var(--crown-card);
        color: var(--crown-primary);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform 0.2s ease, background 0.15s ease;
    }

    .crown-ship-report__expand:hover {
        background: var(--crown-sec-bg);
    }

    .crown-ship-report__expand.is-open {
        transform: rotate(-90deg);
        background: var(--crown-primary);
        color: #fff;
        border-color: var(--crown-primary);
    }

    .crown-ship-report__name-cell {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 12rem;
    }

    .crown-ship-report__badge {
        flex-shrink: 0;
        width: 2.25rem;
        height: 2.25rem;
        border-radius: 0.5rem;
        background: var(--crown-sec-bg);
        color: var(--crown-primary-dark);
        font-family: var(--crown-num-font);
        font-weight: 800;
        font-size: 0.8125rem;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--crown-border);
    }

    .crown-ship-report__name {
        font-weight: 700;
        font-size: 0.875rem;
        color: var(--crown-text);
    }

    .crown-ship-report__date {
        font-size: 0.6875rem;
        color: var(--crown-text-muted);
        margin-top: 0.125rem;
    }

    .crown-ship-report__meta-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        font-size: 0.75rem;
        color: var(--crown-text-muted);
        max-width: 9rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .crown-ship-report__meta-pill--empty {
        color: var(--crown-text-muted);
        opacity: 0.6;
    }

    .crown-ship-report__qty {
        font-family: var(--crown-num-font);
        font-weight: 800;
        font-variant-numeric: tabular-nums;
        text-align: center;
    }

    .crown-ship-report__contrib {
        min-width: 7.5rem;
    }

    .crown-ship-report__contrib-bar {
        height: 0.375rem;
        border-radius: 9999px;
        background: var(--crown-grid);
        overflow: hidden;
        margin-bottom: 0.25rem;
    }

    .crown-ship-report__contrib-fill {
        height: 100%;
        border-radius: 9999px;
        background: linear-gradient(90deg, var(--crown-primary-light), var(--crown-primary));
        transition: width 0.35s ease;
    }

    .crown-ship-report__contrib-pct {
        font-family: var(--crown-num-font);
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--crown-primary-dark);
        text-align: center;
        display: block;
    }

    .crown-ship-report__detail td {
        padding: 0 !important;
        background: var(--crown-zebra) !important;
        border-bottom: 1px solid var(--crown-border);
    }

    .crown-ship-report__detail-inner {
        padding: 1rem 1.25rem 1.25rem;
        border-top: 1px dashed var(--crown-border);
    }

    .crown-ship-report__detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(10rem, 1fr));
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .crown-ship-report__detail-field {
        background: var(--crown-card);
        border: 1px solid var(--crown-border);
        border-radius: 0.375rem;
        padding: 0.625rem 0.75rem;
    }

    .crown-ship-report__detail-field dt {
        font-size: 0.6875rem;
        color: var(--crown-text-muted);
        margin: 0 0 0.2rem;
        font-weight: 500;
    }

    .crown-ship-report__detail-field dd {
        margin: 0;
        font-size: 0.8125rem;
        font-weight: 600;
        color: var(--crown-text);
    }

    .crown-ship-report__notes {
        font-size: 0.8125rem;
        color: var(--crown-text-muted);
        padding: 0.625rem 0.75rem;
        background: var(--crown-card);
        border: 1px solid var(--crown-border);
        border-radius: 0.375rem;
        margin-bottom: 1rem;
        line-height: 1.5;
    }

    .crown-ship-report__detail-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .crown-ship-report__items-title {
        font-size: 0.8125rem;
        font-weight: 700;
        color: var(--crown-text);
        margin: 0 0 0.5rem;
    }

    .crown-ship-report__items-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.75rem;
        background: var(--crown-card);
        border: 1px solid var(--crown-border);
        border-radius: var(--crown-radius);
        overflow: hidden;
    }

    .crown-ship-report__items-table th {
        background: var(--crown-table-head-bg);
        color: var(--crown-table-head-text);
        padding: 0.5rem 0.75rem;
        font-weight: 600;
        text-align: right;
        border-bottom: 1px solid var(--crown-border);
    }

    .crown-ship-report__items-table td {
        padding: 0.5rem 0.75rem;
        border-bottom: 1px solid var(--crown-grid);
    }

    .crown-ship-report__items-table tbody tr:last-child td {
        border-bottom: none;
    }

    .crown-ship-report__items-table tbody tr:nth-child(even) td {
        background: var(--crown-zebra);
    }

    .crown-ship-report__code {
        font-family: ui-monospace, monospace;
        font-size: 0.6875rem;
        direction: ltr;
        text-align: right;
        color: var(--crown-text-muted);
    }

    .crown-ship-report__empty {
        text-align: center;
        padding: 3rem 1.5rem;
    }

    .crown-ship-report__empty-icon {
        width: 4rem;
        height: 4rem;
        margin: 0 auto 1rem;
        border-radius: 1rem;
        background: var(--crown-sec-bg);
        color: var(--crown-primary);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .crown-ship-report__empty-icon svg {
        width: 2rem;
        height: 2rem;
    }

    .crown-ship-report__empty h3 {
        font-size: 1.125rem;
        font-weight: 700;
        color: var(--crown-text);
        margin: 0 0 0.5rem;
    }

    .crown-ship-report__empty p {
        font-size: 0.875rem;
        color: var(--crown-text-muted);
        margin: 0 0 1.25rem;
        max-width: 22rem;
        margin-inline: auto;
    }

    @media (max-width: 768px) {
        .crown-ship-report__table thead {
            display: none;
        }

        .crown-ship-report__table tbody tr.ship-row {
            display: block;
            border-bottom: 1px solid var(--crown-border);
            padding: 0.75rem 1rem;
        }

        .crown-ship-report__table tbody tr.ship-row td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.35rem 0;
            border: none;
        }

        .crown-ship-report__table tbody tr.ship-row td::before {
            content: attr(data-label);
            font-size: 0.6875rem;
            color: var(--crown-text-muted);
            font-weight: 600;
        }

        .crown-ship-report__table tbody tr.ship-row td:first-child,
        .crown-ship-report__table tbody tr.ship-row td.col-expand-only {
            display: block;
        }

        .crown-ship-report__table tbody tr.ship-row td.col-expand-only::before {
            display: none;
        }
    }
</style>
