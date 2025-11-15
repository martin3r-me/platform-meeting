<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$appointment->meeting->title" icon="heroicon-o-calendar-days" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Aktionen --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Aktionen</h3>
                    <div class="space-y-2">
                        <x-ui-button variant="secondary-outline" size="sm" :href="route('meetings.show', $appointment->meeting)" wire:navigate class="w-full">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-video-camera', 'w-4 h-4')
                                Zum Meeting
                            </span>
                        </x-ui-button>
                        @if($appointment->team)
                            <button 
                                type="button"
                                onclick="window.dispatchEvent(new CustomEvent('open-team-flyout'))"
                                class="w-full px-3 py-2 text-sm font-medium rounded-md border border-[var(--ui-border)]/60 bg-white text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors"
                            >
                                <span class="flex items-center gap-2">
                                    @svg('heroicon-o-user-group', 'w-4 h-4')
                                    Team verwalten
                                </span>
                            </button>
                        @endif
                    </div>
                </div>

                {{-- Agenda Statistiken --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Agenda Statistiken</h3>
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

                {{-- Details --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Details</h3>
                    <div class="space-y-3">
                        <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-calendar', 'w-4 h-4 text-[var(--ui-primary)]')
                                <span class="text-sm text-[var(--ui-secondary)]">Start</span>
                            </div>
                            <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $appointment->start_date->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-clock', 'w-4 h-4 text-[var(--ui-primary)]')
                                <span class="text-sm text-[var(--ui-secondary)]">Ende</span>
                            </div>
                            <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $appointment->end_date->format('d.m.Y H:i') }}</span>
                        </div>
                        @if($appointment->meeting->location)
                            <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-map-pin', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm text-[var(--ui-secondary)]">Ort</span>
                                </div>
                                <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $appointment->meeting->location }}</span>
                            </div>
                        @endif
                        <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-user', 'w-4 h-4 text-[var(--ui-primary)]')
                                <span class="text-sm text-[var(--ui-secondary)]">Teilnehmer</span>
                            </div>
                            <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $appointment->user->fullname ?? $appointment->user->name }}</span>
                        </div>
                        @if($appointment->team)
                            <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-user-group', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm text-[var(--ui-secondary)]">Team</span>
                                </div>
                                <span class="text-sm font-semibold text-[var(--ui-secondary)]">
                                    {{ $appointment->team->name }}
                                </span>
                            </div>
                        @endif
                        <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-check-circle', 'w-4 h-4 text-[var(--ui-primary)]')
                                <span class="text-sm text-[var(--ui-secondary)]">Sync-Status</span>
                            </div>
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

    <x-ui-page-container>
        {{-- Meta-Daten Header --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden mb-6">
            <div class="p-6 lg:p-8">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div class="flex-1 min-w-0">
                        <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4 tracking-tight leading-tight">{{ $appointment->meeting->title }}</h1>
                        
                        {{-- Meta Informationen --}}
                        <div class="space-y-2">
                            {{-- Erste Zeile: Team & Teilnehmer --}}
                            <div class="flex flex-wrap items-center gap-6 text-sm text-[var(--ui-muted)]">
                                @if($appointment->team)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-user-group', 'w-4 h-4')
                                        <span>Team: <span class="text-[var(--ui-secondary)]">{{ $appointment->team->name }}</span></span>
                                    </span>
                                @elseif($appointment->meeting->team)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-user-group', 'w-4 h-4')
                                        <span>Team: <span class="text-[var(--ui-secondary)]">{{ $appointment->meeting->team->name }}</span></span>
                                    </span>
                                @endif
                                <span class="flex items-center gap-2">
                                    @svg('heroicon-o-user', 'w-4 h-4')
                                    <span>Teilnehmer: <span class="text-[var(--ui-secondary)]">{{ $appointment->user->fullname ?? $appointment->user->name }}</span></span>
                                </span>
                            </div>
                            
                            {{-- Meeting Link --}}
                            <div class="flex flex-wrap items-center gap-6 text-sm text-[var(--ui-muted)]">
                                <a 
                                    href="{{ route('meetings.show', $appointment->meeting) }}" 
                                    wire:navigate
                                    class="flex items-center gap-2 hover:text-[var(--ui-primary)] transition-colors"
                                >
                                    @svg('heroicon-o-video-camera', 'w-4 h-4')
                                    <span>Meeting: <span class="text-[var(--ui-secondary)]">{{ $appointment->meeting->title }}</span></span>
                                </a>
                            </div>
                            
                            {{-- Zweite Zeile: Datum & Ort --}}
                            <div class="flex flex-wrap items-center gap-6 text-sm text-[var(--ui-muted)]">
                                <span class="flex items-center gap-2">
                                    @svg('heroicon-o-calendar', 'w-4 h-4')
                                    <span>{{ $appointment->start_date->format('d.m.Y H:i') }} - {{ $appointment->end_date->format('H:i') }}</span>
                                </span>
                                @if($appointment->meeting->location)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-map-pin', 'w-4 h-4')
                                        <span>{{ $appointment->meeting->location }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-6">
            {{-- Beschreibung --}}
            @if($appointment->meeting->description)
                <div class="mb-6">
                    <h3 class="text-sm font-semibold mb-2">Beschreibung</h3>
                    <div class="prose prose-sm max-w-none">
                        {!! nl2br(e($appointment->meeting->description)) !!}
                    </div>
                </div>
            @endif

            {{-- Agenda Kanban Board --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Agenda</h3>
                    @can('update', $appointment)
                        <div class="flex items-center gap-2">
                            <x-ui-button variant="secondary-outline" size="sm" wire:click="createAgendaSlot">
                                <span class="flex items-center gap-2">
                                    @svg('heroicon-o-plus-circle', 'w-4 h-4')
                                    Neue Spalte
                                </span>
                            </x-ui-button>
                        </div>
                    @endcan
                </div>
                
                <x-ui-kanban-container sortable="updateAgendaSlotOrder" sortable-group="updateAgendaItemOrder">
                    {{-- Backlog --}}
                    @if($backlogItems->count() > 0)
                        <x-ui-kanban-column title="Backlog" :sortable-id="null" :scrollable="true" :muted="true">
                            @foreach($backlogItems as $item)
                                @include('meetings::livewire.agenda-item-preview-card', ['agendaItem' => $item])
                            @endforeach
                        </x-ui-kanban-column>
                    @endif

                    {{-- Agenda Slots --}}
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

                    {{-- Done --}}
                    @if($doneSlot && $doneItems->count() > 0)
                        <x-ui-kanban-column :title="$doneSlot->name" :sortable-id="null" :scrollable="true" :muted="true">
                            @foreach($doneItems as $item)
                                @include('meetings::livewire.agenda-item-preview-card', ['agendaItem' => $item])
                            @endforeach
                        </x-ui-kanban-column>
                    @endif
                </x-ui-kanban-container>
            </div>
        </div>
    </x-ui-page-container>
</x-ui-page>
