<x-filament-widgets::widget>
    @include('filament.components.crown-theme-global')
    <x-filament::section heading="تقدّم المشاريع">
        <div class="space-y-3">
            @forelse ($projects as $project)
                <div>
                    <div class="flex justify-between text-sm mb-1" style="color:var(--crown-text);">
                        <span>{{ $project['name'] }} ({{ $project['code'] }})</span>
                        <span class="crown-num" style="font-weight:700;color:var(--crown-primary);">{{ number_format($project['progress'], 1) }}%</span>
                    </div>
                    <div class="crown-progress" style="width:100%;">
                        <div class="crown-progress__fill" style="width: {{ min(100, max(0, $project['progress'])) }}%;"></div>
                    </div>
                </div>
            @empty
                <p class="text-sm" style="color:var(--crown-text-muted);">لا توجد مشاريع.</p>
            @endforelse
        </div>
    </x-filament::section>
    <style>
        .crown-progress { height:0.5rem;border-radius:9999px;background:var(--crown-grid);overflow:hidden; }
        .crown-progress__fill { height:100%;background:var(--crown-primary);border-radius:9999px; }
    </style>
</x-filament-widgets::widget>
