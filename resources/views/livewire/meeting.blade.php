<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$meeting->title" icon="heroicon-o-video-camera" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Aktionen --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Aktionen</h3>
                    <div class="space-y-2">
                        @can('update', $meeting)
                            <x-ui-button variant="secondary-outline" size="sm" :href="route('meetings.dashboard')" wire:navigate class="w-full">
                                <span class="flex items-center gap-2">
                                    @svg('heroicon-o-home', 'w-4 h-4')
                                    Zum Dashboard
                                </span>
                            </x-ui-button>
                        @endcan
                        @can('delete', $meeting)
                            <x-ui-confirm-button 
                                action="deleteMeeting" 
                                text="Löschen" 
                                confirmText="Wirklich löschen?" 
                                variant="danger"
                                :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()"
                                class="w-full"
                            />
                        @endcan
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
                            <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $meeting->start_date->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-clock', 'w-4 h-4 text-[var(--ui-primary)]')
                                <span class="text-sm text-[var(--ui-secondary)]">Ende</span>
                            </div>
                            <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $meeting->end_date->format('d.m.Y H:i') }}</span>
                        </div>
                        @if($meeting->location)
                            <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-map-pin', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm text-[var(--ui-secondary)]">Ort</span>
                                </div>
                                <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $meeting->location }}</span>
                            </div>
                        @endif
                        <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                            <div class="flex items-center gap-2">
                                @svg('heroicon-o-check-circle', 'w-4 h-4 text-[var(--ui-primary)]')
                                <span class="text-sm text-[var(--ui-secondary)]">Status</span>
                            </div>
                            <x-ui-badge variant="primary" size="sm">{{ ucfirst($meeting->status) }}</x-ui-badge>
                        </div>
                    </div>
                </div>

                {{-- Teilnehmer --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Teilnehmer</h3>
                    <div class="space-y-2">
                        @foreach($meeting->participants as $participant)
                            <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-user', 'w-4 h-4 text-[var(--ui-muted)]')
                                    <span class="text-sm text-[var(--ui-secondary)]">{{ $participant->user->fullname ?? $participant->user->name }}</span>
                                </div>
                                @php
                                    $statusColors = [
                                        'accepted' => 'success',
                                        'declined' => 'danger',
                                        'tentative' => 'warning',
                                        'notResponded' => 'muted',
                                    ];
                                    $statusColor = $statusColors[$participant->response_status] ?? 'muted';
                                @endphp
                                <x-ui-badge :variant="$statusColor" size="xs">
                                    {{ ucfirst(str_replace('_', ' ', $participant->response_status)) }}
                                </x-ui-badge>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-slot name="activity">
        <x-ui-page-sidebar title="Aktivitäten" width="w-80" :defaultOpen="false" storeKey="activityOpen" side="right">
            <div class="p-6">
                <h3 class="text-xs font-semibold uppercase tracking-wider text-[var(--ui-muted)] mb-4">Letzte Aktivitäten</h3>
                <div class="space-y-3">
                    @forelse(($activities ?? []) as $activity)
                        <div class="p-3 rounded-lg border border-[var(--ui-border)]/40 bg-[var(--ui-muted-5)] hover:bg-[var(--ui-muted)] transition-colors">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm font-medium text-[var(--ui-secondary)] leading-snug">
                                        {{ $activity['title'] ?? 'Aktivität' }}
                                    </div>
                                </div>
                                @if(($activity['type'] ?? null) === 'system')
                                    <div class="flex-shrink-0">
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 text-xs text-[var(--ui-muted)]">
                                            @svg('heroicon-o-cog', 'w-3 h-3')
                                            System
                                        </span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex items-center gap-2 text-xs text-[var(--ui-muted)]">
                                @svg('heroicon-o-clock', 'w-3 h-3')
                                <span>{{ $activity['time'] ?? '' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="py-8 text-center">
                            <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-[var(--ui-muted-5)] mb-3">
                                @svg('heroicon-o-clock', 'w-6 h-6 text-[var(--ui-muted)]')
                            </div>
                            <p class="text-sm text-[var(--ui-muted)]">Noch keine Aktivitäten</p>
                            <p class="text-xs text-[var(--ui-muted)] mt-1">Änderungen werden hier angezeigt</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        <div class="p-6">
        {{-- Beschreibung --}}
        @if($meeting->description)
            <div class="mb-6">
                <h3 class="text-sm font-semibold mb-2">Beschreibung</h3>
                <div class="prose prose-sm max-w-none">
                    {!! nl2br(e($meeting->description)) !!}
                </div>
            </div>
        @endif

        {{-- Agenda Kanban Board --}}
        <div>
            <h3 class="text-lg font-semibold mb-4">Agenda</h3>
            
            <x-ui-kanban-container sortable="updateAgendaSlotOrder" sortable-group="updateAgendaItemOrder">
                {{-- Backlog --}}
                @if($backlogItems->count() > 0)
                    <x-ui-kanban-column title="Backlog" :sortable-id="null" :scrollable="true" :muted="true">
                        @foreach($backlogItems as $item)
                            <x-ui-kanban-card :title="$item->title" :sortable-id="$item->id" wire:key="agenda-item-{{ $item->id }}">
                                @if($item->description)
                                    <div class="text-xs text-[var(--ui-muted)] mt-1">
                                        {{ Str::limit($item->description, 100) }}
                                    </div>
                                @endif
                            </x-ui-kanban-card>
                        @endforeach
                    </x-ui-kanban-column>
                @endif

                {{-- Agenda Slots --}}
                @foreach($agendaSlots as $slot)
                    <x-ui-kanban-column :title="$slot->name" :sortable-id="$slot->id" :scrollable="true">
                        <x-slot name="headerActions">
                            @can('update', $meeting)
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
                            <x-ui-kanban-card :title="$item->title" :sortable-id="$item->id" wire:key="agenda-item-{{ $item->id }}">
                                @if($item->description)
                                    <div class="text-xs text-[var(--ui-muted)] mt-1">
                                        {{ Str::limit($item->description, 100) }}
                                    </div>
                                @endif
                                @if($item->duration_minutes)
                                    <div class="text-xs text-[var(--ui-muted)] mt-1">
                                        {{ $item->duration_minutes }} Min.
                                    </div>
                                @endif
                            </x-ui-kanban-card>
                        @endforeach
                    </x-ui-kanban-column>
                @endforeach

                {{-- Done --}}
                @if($doneSlot && $doneItems->count() > 0)
                    <x-ui-kanban-column :title="$doneSlot->name" :sortable-id="null" :scrollable="true" :muted="true">
                        @foreach($doneItems as $item)
                            <x-ui-kanban-card :title="$item->title" :sortable-id="$item->id" wire:key="agenda-item-{{ $item->id }}">
                                @if($item->description)
                                    <div class="text-xs text-[var(--ui-muted)] mt-1">
                                        {{ Str::limit($item->description, 100) }}
                                    </div>
                                @endif
                            </x-ui-kanban-card>
                        @endforeach
                    </x-ui-kanban-column>
                @endif
            </x-ui-kanban-container>
        </div>
    </x-ui-page-container>
</x-ui-page>

