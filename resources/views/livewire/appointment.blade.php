<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Termin" icon="heroicon-o-calendar-days" />
    </x-slot>

    <div class="p-6">
        <div class="max-w-2xl space-y-4">
            <div>
                <h2 class="text-2xl font-bold">{{ $appointment->meeting->title }}</h2>
                <p class="text-[var(--ui-muted)]">
                    {{ $appointment->meeting->start_date->format('d.m.Y H:i') }} - 
                    {{ $appointment->meeting->end_date->format('H:i') }}
                </p>
            </div>

            @if($appointment->meeting->description)
                <div>
                    <h3 class="font-semibold mb-2">Beschreibung</h3>
                    <div class="prose prose-sm max-w-none">
                        {!! nl2br(e($appointment->meeting->description)) !!}
                    </div>
                </div>
            @endif

            <div>
                <h3 class="font-semibold mb-2">Sync-Status</h3>
                <div class="flex items-center gap-2">
                    <span class="text-sm">{{ $appointment->sync_status }}</span>
                    @if($appointment->last_synced_at)
                        <span class="text-xs text-[var(--ui-muted)]">
                            Zuletzt synchronisiert: {{ $appointment->last_synced_at->format('d.m.Y H:i') }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-ui-page>

