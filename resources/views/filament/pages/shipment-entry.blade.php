<x-filament-panels::page>
    <style>
        .crown-entry-wrap {
            direction: rtl;
            border: 0.5px solid rgb(var(--gray-200));
            border-radius: 0.5rem;
            padding: 1.25rem;
            background: rgb(var(--gray-50));
        }
        .dark .crown-entry-wrap {
            background: rgb(var(--gray-900));
            border-color: rgb(var(--gray-700));
        }
        .crown-entry-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1rem;
            padding-bottom: 1rem;
            border-bottom: 0.5px solid rgb(var(--gray-200));
        }
        .dark .crown-entry-meta { border-color: rgb(var(--gray-700)); }
        .crown-entry-meta span {
            font-size: 0.8125rem;
            color: rgb(var(--gray-600));
        }
        .dark .crown-entry-meta span { color: rgb(var(--gray-400)); }
        .crown-entry-meta strong { color: rgb(var(--gray-950)); }
        .dark .crown-entry-meta strong { color: rgb(var(--gray-50)); }
    </style>

    <div class="crown-entry-wrap">
        <div class="crown-entry-meta">
            <span>الحمولة: <strong>{{ $this->shipment->name }}</strong></span>
            <span>التاريخ: <strong>{{ $this->shipment->shipped_at?->format('Y-m-d') ?? '—' }}</strong></span>
            <span>المشروع: <strong>{{ $this->shipment->project->name }}</strong></span>
        </div>

        <form wire:submit="save">
            {{ $this->form }}

            <div style="margin-top: 1.25rem; display: flex; gap: 0.5rem; flex-wrap: wrap;">
                <x-filament::button type="submit" icon="heroicon-o-check">
                    حفظ دفعة واحدة
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>
