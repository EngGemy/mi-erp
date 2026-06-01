<style>
    /* جداول Crown الموحّدة (حصر، نواقص، WBS، مخزون، …) */
    .crown-table-wrap {
        border: 1px solid var(--crown-border);
        border-radius: var(--crown-radius);
        overflow: hidden;
        background: var(--crown-card);
        box-shadow: 0 1px 3px rgba(31, 41, 55, 0.06);
    }
    .crown-table-scroll {
        overflow: auto;
        max-height: min(68vh, 720px);
    }
    .crown-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.8125rem;
        color: var(--crown-text);
    }
    .crown-table thead th {
        position: sticky;
        top: 0;
        z-index: 10;
        background: var(--crown-table-head-bg);
        color: var(--crown-table-head-text);
        font-weight: 600;
        font-size: 0.75rem;
        padding: 9px 10px;
        border: 1px solid var(--crown-border);
        white-space: nowrap;
        vertical-align: middle;
        text-align: center;
    }
    .crown-table thead th.col-text {
        text-align: right;
    }
    .crown-table tbody td {
        padding: 9px 10px;
        border: 1px solid var(--crown-grid);
        vertical-align: middle;
    }
    .crown-table tbody tr.item-row td {
        background: var(--crown-card);
    }
    .crown-table tbody tr.item-row.item-row--zebra td {
        background: var(--crown-zebra);
    }
    .crown-table tbody tr.item-row:hover td {
        background: var(--crown-sec-bg);
    }
    .crown-table tr.sec-header td {
        background: var(--crown-sec-bg);
        color: var(--crown-sec-text);
        font-weight: 700;
        font-size: 0.8125rem;
        padding: 9px 12px;
        border: 1px solid var(--crown-grid);
        text-align: right;
    }
    .crown-table tfoot td {
        position: sticky;
        bottom: 0;
        background: var(--crown-table-foot-bg);
        color: var(--crown-table-foot-text);
        font-weight: 700;
        padding: 9px 12px;
        border-top: 2px solid var(--crown-border);
    }
    .crown-table .col-item {
        min-width: 11rem;
        text-align: right;
    }
    .crown-table .col-num {
        text-align: center;
        font-family: var(--crown-num-font);
        font-variant-numeric: tabular-nums;
        white-space: nowrap;
    }
    .crown-table .col-h,
    .crown-table .col-total-strong {
        font-weight: 800;
        font-size: 0.875rem;
        color: var(--crown-primary-dark);
        background: rgba(224, 36, 36, 0.06);
    }
    .dark .crown-table .col-h,
    .dark .crown-table .col-total-strong {
        color: #fca5a5;
        background: rgba(224, 36, 36, 0.12);
    }
    .crown-table .col-remaining--open {
        font-weight: 700;
        color: var(--crown-danger);
    }
    .crown-table .col-remaining--done {
        font-weight: 700;
        color: var(--crown-success);
    }
    .crown-table .col-delivered {
        font-weight: 600;
        color: var(--crown-success);
    }
    .crown-table .col-item__name {
        font-weight: 600;
        color: var(--crown-text);
    }
    .crown-table .col-item__code {
        display: block;
        font-size: 0.6875rem;
        color: var(--crown-text-muted);
        font-family: ui-monospace, monospace;
        direction: ltr;
        text-align: right;
        margin-top: 0.15rem;
    }
</style>
