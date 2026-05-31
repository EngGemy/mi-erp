<style>
    .crown-ship-bar {
        position: sticky;
        top: 0;
        z-index: 30;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
        padding: 0.75rem 1rem;
        margin-bottom: 1rem;
        border-radius: var(--crown-radius);
        background: var(--crown-charcoal);
        color: #fff;
        direction: rtl;
    }
    .crown-ship-bar__title { font-weight: 700; }
    .crown-ship-select {
        min-width: 11rem;
        height: 2rem;
        border-radius: 0.375rem;
        padding: 0 0.625rem;
        border: 1px solid var(--crown-charcoal-soft);
        background: var(--crown-card);
        color: var(--crown-text);
    }
    .crown-ship-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        font-size: 0.75rem;
        opacity: 0.92;
    }
    .crown-ship-empty { font-size: 0.8125rem; color: #fca5a5; }
    .crown-ship-actions {
        display: flex;
        gap: 0.5rem;
        margin-inline-start: auto;
    }
    .crown-ship-bar--soft .crown-btn--ghost {
        color: var(--crown-text);
        border-color: var(--crown-border);
        background: var(--crown-zebra);
    }
    .crown-adj {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        justify-content: center;
    }
    .crown-adj-btn {
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 9999px;
        border: 1px solid var(--crown-border);
        background: var(--crown-card);
        cursor: pointer;
        font-weight: 700;
        font-size: 0.875rem;
        color: var(--crown-text-muted);
    }
    .crown-adj-btn--plus {
        color: var(--crown-success);
        border-color: rgba(21, 128, 61, 0.4);
    }
    .crown-adj-btn:hover {
        background: var(--crown-charcoal);
        color: #fff;
        border-color: var(--crown-charcoal);
    }
    .crown-adj-btn--plus:hover {
        background: var(--crown-success);
        border-color: var(--crown-success);
    }
    .crown-adj-inp {
        width: 3.25rem;
        height: 1.75rem;
        text-align: center;
        border: 1px solid var(--crown-border);
        border-radius: 0.375rem;
        font-family: var(--crown-num-font);
        font-size: 0.8125rem;
        background: var(--crown-card);
        color: var(--crown-text);
    }
    .crown-adj-fill {
        height: 1.75rem;
        padding: 0 0.5rem;
        border-radius: 0.375rem;
        border: none;
        background: var(--crown-primary);
        color: #fff;
        cursor: pointer;
        font-size: 0.6875rem;
    }
    .crown-adj-fill:hover { background: var(--crown-primary-dark); }
    .crown-adj--compact {
        padding: 0.2rem;
        background: var(--crown-zebra);
        border: 1px solid var(--crown-border);
        border-radius: var(--crown-radius);
    }
    .crown-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 50;
        background: rgba(43, 45, 51, 0.45);
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
    }
    .crown-modal {
        background: var(--crown-card);
        border-radius: var(--crown-radius);
        padding: 1.25rem;
        width: 100%;
        max-width: 24rem;
        direction: rtl;
        border: 1px solid var(--crown-border);
        box-shadow: 0 8px 24px rgba(31, 41, 55, 0.15);
    }
    .crown-modal__title { font-weight: 700; margin-bottom: 1rem; color: var(--crown-text); }
    .crown-modal label {
        display: block;
        font-size: 0.75rem;
        color: var(--crown-text-muted);
        margin-bottom: 0.25rem;
    }
    .crown-modal input {
        width: 100%;
        height: 2.25rem;
        border: 1px solid var(--crown-border);
        border-radius: 0.375rem;
        padding: 0 0.5rem;
        margin-bottom: 0.75rem;
        background: var(--crown-card);
        color: var(--crown-text);
    }
    .crown-modal__actions {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
    }
</style>
