<style>
    .crown-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        height: 2.125rem;
        padding: 0 0.875rem;
        border-radius: 0.375rem;
        font-size: 0.8125rem;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid transparent;
        white-space: nowrap;
        transition: background 0.15s, border-color 0.15s;
    }
    .crown-btn--primary {
        background: var(--crown-primary);
        color: #fff;
        border-color: var(--crown-primary);
    }
    .crown-btn--primary:hover {
        background: var(--crown-primary-dark);
        border-color: var(--crown-primary-dark);
    }
    .crown-btn--secondary {
        background: var(--crown-card);
        color: var(--crown-text);
        border-color: var(--crown-border);
    }
    .crown-btn--secondary:hover {
        background: var(--crown-zebra);
    }
    .crown-btn--success {
        background: var(--crown-success);
        color: #fff;
        border-color: var(--crown-success);
    }
    .crown-btn--danger {
        background: var(--crown-primary-dark);
        color: #fff;
        border-color: var(--crown-primary-dark);
    }
    .crown-btn--ghost {
        background: transparent;
        color: var(--crown-text);
        border-color: var(--crown-border);
    }
    .crown-toggle-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        height: 2rem;
        padding: 0 0.75rem;
        border-radius: 0.375rem;
        font-size: 0.75rem;
        font-weight: 600;
        cursor: pointer;
        border: 1px solid var(--crown-border);
        background: var(--crown-card);
        color: var(--crown-text);
    }
    .crown-toggle-btn--on {
        background: var(--crown-primary);
        border-color: var(--crown-primary);
        color: #fff;
    }
</style>
