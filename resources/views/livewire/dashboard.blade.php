<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Meetings" icon="heroicon-o-calendar-days" />
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-xl font-semibold text-[var(--ui-secondary)]">Meetings</h1>
                    <p class="text-sm text-[var(--ui-muted)]">Kommende Termine als Liste</p>
                </div>
            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-[var(--ui-secondary)]">Heute</h3>
                    <div class="space-y-3">
                        @forelse($todayAppointments as $appointment)
                            @php $meeting = $appointment->meeting; @endphp
                            <a href="{{ route('meetings.show', $meeting) }}"
                               class="block p-4 rounded-lg border border-[var(--ui-border)]/60 bg-white hover:bg-[var(--ui-muted-5)] transition">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="font-medium text-[var(--ui-secondary)] truncate">{{ $meeting->title }}</div>
                                        <div class="text-xs text-[var(--ui-muted)]">
                                            {{ $appointment->start_date->format('H:i') }} – {{ $appointment->end_date->format('H:i') }}
                                            @if($meeting->location)
                                                • {{ Str::limit($meeting->location, 50) }}
                                            @endif
                                        </div>
                                    </div>
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full bg-[var(--ui-primary-5)] text-[var(--ui-primary)]">
                                        Heute
                                    </span>
                                </div>
                            </a>
                        @empty
                            <div class="p-4 text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                                Keine Termine heute.
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-sm font-semibold text-[var(--ui-secondary)]">Meine Meetings (nächste 45 Tage)</h3>
                    <div class="space-y-3">
                        @forelse($myAppointments as $appointment)
                            @php $meeting = $appointment->meeting; @endphp
                            <a href="{{ route('meetings.show', $meeting) }}"
                               class="block p-4 rounded-lg border border-[var(--ui-border)]/60 bg-white hover:bg-[var(--ui-muted-5)] transition">
                                <div class="flex items-center justify-between gap-3">
                                    <div class="min-w-0">
                                        <div class="font-medium text-[var(--ui-secondary)] truncate">{{ $meeting->title }}</div>
                                        <div class="text-xs text-[var(--ui-muted)]">
                                            {{ $appointment->start_date->format('d.m.Y H:i') }} – {{ $appointment->end_date->format('H:i') }}
                                            @if($meeting->location)
                                                • {{ Str::limit($meeting->location, 50) }}
                                            @endif
                                        </div>
                                    </div>
                                    <span class="text-xs font-semibold px-2 py-1 rounded-full bg-[var(--ui-muted-5)] text-[var(--ui-secondary)]">
                                        Teilnehmer
                                    </span>
                                </div>
                            </a>
                        @empty
                            <div class="p-4 text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                                Keine Meetings für dich geplant.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="text-sm font-semibold text-[var(--ui-secondary)]">Demnächst (Team, 45 Tage)</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
                    @forelse($upcomingAppointments as $appointment)
                        @php $meeting = $appointment->meeting; @endphp
                        <a href="{{ route('meetings.show', $meeting) }}"
                           class="flex flex-col gap-2 p-4 rounded-lg border border-[var(--ui-border)]/60 bg-white hover:bg-[var(--ui-muted-5)] transition">
                            <div class="flex items-center justify-between gap-2">
                                <div class="text-xs font-semibold text-[var(--ui-primary)]">
                                    {{ $appointment->start_date->format('d.m.Y') }}
                                </div>
                                @if($meeting->location)
                                    <div class="text-[10px] text-[var(--ui-muted)] truncate">
                                        {{ Str::limit($meeting->location, 40) }}
                                    </div>
                                @endif
                            </div>
                            <div class="font-medium text-[var(--ui-secondary)] truncate">{{ $meeting->title }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">
                                {{ $appointment->start_date->format('H:i') }} – {{ $appointment->end_date->format('H:i') }}
                            </div>
                        </a>
                    @empty
                        <div class="p-4 text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                            Keine kommenden Termine im Zeitraum.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </x-ui-page-container>
</x-ui-page>

