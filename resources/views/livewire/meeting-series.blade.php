<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$meetingSeries->title" icon="heroicon-o-arrow-path" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Meetings', 'href' => route('meetings.dashboard'), 'icon' => 'calendar-days'],
            ['label' => $meetingSeries->title],
        ]">
            <x-slot name="left">
                @can('update', $meetingSeries)
                    <x-ui-button variant="ghost" size="sm" wire:click="generateMeetings">
                        @svg('heroicon-o-plus-circle', 'w-4 h-4')
                        <span>Meetings generieren</span>
                    </x-ui-button>
                    <x-ui-button variant="ghost" size="sm" wire:click="toggleActive">
                        @svg($meetingSeries->is_active ? 'heroicon-o-pause' : 'heroicon-o-play', 'w-4 h-4')
                        <span>{{ $meetingSeries->is_active ? 'Pausieren' : 'Aktivieren' }}</span>
                    </x-ui-button>
                @endcan
            </x-slot>
            @can('delete', $meetingSeries)
                <x-ui-confirm-button action="deleteSeries" text="Löschen" confirmText="Wirklich?" variant="danger" size="sm" />
            @endcan
        </x-ui-page-actionbar>
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Serie" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Details --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Details</h3>
                    <div class="space-y-3">
                        <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-arrow-path', 'w-4 h-4 text-[var(--ui-primary)]')
                                <span class="text-sm text-[var(--ui-secondary)]">Wiederholung</span>
                            </div>
                            <x-ui-badge variant="primary" size="sm">{{ $meetingSeries->getRecurrencePatternText() }}</x-ui-badge>
                        </div>
                        <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-clock', 'w-4 h-4 text-[var(--ui-primary)]')
                                <span class="text-sm text-[var(--ui-secondary)]">Zeit</span>
                            </div>
                            <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $meetingSeries->start_time }} – {{ $meetingSeries->end_time }}</span>
                        </div>
                        @if($meetingSeries->location)
                            <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-map-pin', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm text-[var(--ui-secondary)]">Ort</span>
                                </div>
                                <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $meetingSeries->location }}</span>
                            </div>
                        @endif
                        <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-check-circle', 'w-4 h-4 text-[var(--ui-primary)]')
                                <span class="text-sm text-[var(--ui-secondary)]">Status</span>
                            </div>
                            <x-ui-badge :variant="$meetingSeries->is_active ? 'success' : 'muted'" size="sm">
                                {{ $meetingSeries->is_active ? 'Aktiv' : 'Pausiert' }}
                            </x-ui-badge>
                        </div>
                        @if($meetingSeries->next_meeting_date)
                            <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-calendar', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm text-[var(--ui-secondary)]">Nächstes Meeting</span>
                                </div>
                                <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $meetingSeries->next_meeting_date->format('d.m.Y') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        @if(session('message'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
                {{ session('message') }}
            </div>
        @endif

        <div class="space-y-6">
            @if($meetingSeries->description)
                <div class="p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                    <p class="text-sm text-[var(--ui-secondary)]">{{ $meetingSeries->description }}</p>
                </div>
            @endif

            <div>
                <h3 class="text-sm font-semibold text-[var(--ui-secondary)] mb-4">Meetings dieser Serie ({{ $meetings->count() }})</h3>
                <div class="space-y-3">
                    @forelse($meetings as $meeting)
                        <a href="{{ route('meetings.show', $meeting) }}"
                           class="block p-4 rounded-lg border border-[var(--ui-border)]/60 bg-white hover:bg-[var(--ui-muted-5)] transition">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="font-medium text-[var(--ui-secondary)] truncate">{{ $meeting->title }}</div>
                                    <div class="text-xs text-[var(--ui-muted)]">
                                        @if($meeting->start_date)
                                            {{ $meeting->start_date->format('d.m.Y H:i') }} – {{ $meeting->end_date->format('H:i') }}
                                        @endif
                                        @if($meeting->location)
                                            &bull; {{ $meeting->location }}
                                        @endif
                                    </div>
                                </div>
                                <x-ui-badge :variant="$meeting->start_date && $meeting->start_date->isPast() ? 'muted' : 'primary'" size="xs">
                                    {{ $meeting->start_date && $meeting->start_date->isPast() ? 'Vergangen' : ucfirst($meeting->status) }}
                                </x-ui-badge>
                            </div>
                        </a>
                    @empty
                        <div class="p-4 text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                            Noch keine Meetings in dieser Serie. Klicke auf "Meetings generieren" um Meetings zu erstellen.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </x-ui-page-container>
</x-ui-page>
