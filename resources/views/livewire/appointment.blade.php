<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$appointment->meeting->title" icon="heroicon-o-calendar-days" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Termin-Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-4">
                {{-- Aktionen --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Aktionen</h3>
                    <div class="flex items-center gap-2 flex-wrap">
                        @can('update', $appointment)
                            <x-ui-button variant="secondary" size="sm" wire:click="createAgendaSlot">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-square-2-stack','w-4 h-4')
                                    <span class="hidden sm:inline">Spalte</span>
                                </span>
                            </x-ui-button>
                        @endcan
                        @can('update', $appointment)
                            <x-ui-button variant="secondary" size="sm" wire:click="createAgendaItem()">
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-plus','w-4 h-4')
                                    <span class="hidden sm:inline">Agenda Item</span>
                                </span>
                            </x-ui-button>
                        @endcan
                        <x-ui-button variant="secondary-outline" size="sm" :href="route('meetings.show', $appointment->meeting)" wire:navigate>
                            <span class="inline-flex items-center gap-2">
                                @svg('heroicon-o-video-camera','w-4 h-4')
                                <span class="hidden sm:inline">Meeting</span>
                            </span>
                        </x-ui-button>
                        @if($appointment->team)
                            <button 
                                type="button"
                                onclick="window.dispatchEvent(new CustomEvent('open-team-flyout'))"
                                class="px-3 py-2 text-sm font-medium rounded-md border border-[var(--ui-border)]/60 bg-white text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors"
                            >
                                <span class="inline-flex items-center gap-2">
                                    @svg('heroicon-o-user-group','w-4 h-4')
                                    <span class="hidden sm:inline">Team</span>
                                </span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Agenda Statistiken --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Statistiken</h3>
                    <div class="space-y-2">
                        @foreach($agendaStats as $stat)
                            <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-' . $stat['icon'], 'w-4 h-4 text-[var(--ui-' . $stat['variant'] . ')]')
                                    <span class="text-sm text-[var(--ui-secondary)]">{{ $stat['title'] }}</span>
                                </div>
                                <span class="text-sm font-semibold text-[var(--ui-' . $stat['variant'] . ')]">
                                    {{ $stat['count'] }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Termin-Details --}}
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between py-1">
                            <span class="text-[var(--ui-muted)]">Start:</span>
                            <span class="text-[var(--ui-secondary)] font-medium">
                                {{ $appointment->start_date->format('d.m.Y H:i') }}
                            </span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span class="text-[var(--ui-muted)]">Ende:</span>
                            <span class="text-[var(--ui-secondary)] font-medium">
                                {{ $appointment->end_date->format('d.m.Y H:i') }}
                            </span>
                        </div>
                        @if($appointment->meeting->location)
                            <div class="flex justify-between py-1">
                                <span class="text-[var(--ui-muted)]">Ort:</span>
                                <span class="text-[var(--ui-secondary)] font-medium">
                                    {{ $appointment->meeting->location }}
                                </span>
                            </div>
                        @endif
                        <div class="flex justify-between py-1">
                            <span class="text-[var(--ui-muted)]">Teilnehmer:</span>
                            <span class="text-[var(--ui-secondary)] font-medium">
                                {{ $appointment->user->fullname ?? $appointment->user->name }}
                            </span>
                        </div>
                        @if($appointment->team)
                            <div class="flex justify-between py-1">
                                <span class="text-[var(--ui-muted)]">Team:</span>
                                <span class="text-[var(--ui-secondary)] font-medium">
                                    {{ $appointment->team->name }}
                                </span>
                            </div>
                        @endif
                        <div class="flex justify-between py-1">
                            <span class="text-[var(--ui-muted)]">Sync-Status:</span>
                            @php
                                $syncColors = [
                                    'synced' => 'success',
                                    'pending' => 'warning',
                                    'error' => 'danger',
                                ];
                                $syncColor = $syncColors[$appointment->sync_status] ?? 'muted';
                            @endphp
                            <x-ui-badge :variant="$syncColor" size="xs">
                                {{ ucfirst($appointment->sync_status) }}
                            </x-ui-badge>
                        </div>
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="true" storeKey="activityOpen" side="right">
            <div class="p-4 space-y-4">
                <div class="text-sm text-[var(--ui-muted)]">Letzte Aktivitäten</div>
                <div class="space-y-3 text-sm">
                    @foreach(($activities ?? []) as $activity)
                        <div class="p-2 rounded border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                            <div class="font-medium text-[var(--ui-secondary)] truncate">{{ $activity['title'] ?? 'Aktivität' }}</div>
                            <div class="text-[var(--ui-muted)]">{{ $activity['time'] ?? '' }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    {{-- Board-Container: füllt restliche Breite, Spalten scrollen intern --}}
    <x-ui-kanban-container sortable="updateAgendaSlotOrder" sortable-group="updateAgendaItemOrder">
        {{-- Backlog (nicht sortierbar als Gruppe) --}}
        @if($backlogItems->count() > 0)
            <x-ui-kanban-column title="Backlog" :sortable-id="null" :scrollable="true" :muted="true">
                @foreach($backlogItems as $item)
                    @include('meetings::livewire.agenda-item-preview-card', ['agendaItem' => $item])
                @endforeach
            </x-ui-kanban-column>
        @endif

        {{-- Mittlere Spalten (sortierbar) --}}
        @foreach($agendaSlots as $slot)
            <x-ui-kanban-column :title="$slot->name" :sortable-id="$slot->id" :scrollable="true">
                <x-slot name="headerActions">
                    @can('update', $appointment)
                        <button 
                            wire:click="createAgendaItem('{{ $slot->id }}')" 
                            class="text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] transition-colors"
                            title="Neues Agenda Item"
                        >
                            @svg('heroicon-o-plus-circle', 'w-4 h-4')
                        </button>
                    @endcan
                </x-slot>

                @foreach($slot->agendaItems as $item)
                    @include('meetings::livewire.agenda-item-preview-card', ['agendaItem' => $item])
                @endforeach
            </x-ui-kanban-column>
        @endforeach

        {{-- Erledigt (nicht sortierbar als Gruppe) --}}
        @if($doneSlot && $doneItems->count() > 0)
            <x-ui-kanban-column :title="$doneSlot->name" :sortable-id="null" :scrollable="true" :muted="true">
                @foreach($doneItems as $item)
                    @include('meetings::livewire.agenda-item-preview-card', ['agendaItem' => $item])
                @endforeach
            </x-ui-kanban-column>
        @endif
    </x-ui-kanban-container>
</x-ui-page>
