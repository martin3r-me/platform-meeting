<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar :title="$meeting->title" icon="heroicon-o-video-camera" />
    </x-slot>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Meeting-Info" width="w-80" :defaultOpen="true">
            <div class="p-4 space-y-4">
                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Details</h3>
                    <div class="space-y-2">
                        <div>
                            <span class="text-xs text-[var(--ui-muted)]">Start</span>
                            <p class="text-sm">{{ $meeting->start_date->format('d.m.Y H:i') }}</p>
                        </div>
                        <div>
                            <span class="text-xs text-[var(--ui-muted)]">Ende</span>
                            <p class="text-sm">{{ $meeting->end_date->format('d.m.Y H:i') }}</p>
                        </div>
                        @if($meeting->location)
                            <div>
                                <span class="text-xs text-[var(--ui-muted)]">Ort</span>
                                <p class="text-sm">{{ $meeting->location }}</p>
                            </div>
                        @endif
                        <div>
                            <span class="text-xs text-[var(--ui-muted)]">Status</span>
                            <p class="text-sm">{{ $meeting->status }}</p>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-[var(--ui-muted)] mb-3">Teilnehmer</h3>
                    <div class="space-y-2">
                        @foreach($meeting->participants as $participant)
                            <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)]">
                                <span class="text-sm">{{ $participant->user->name }}</span>
                                <span class="text-xs text-[var(--ui-muted)]">{{ $participant->response_status }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </x-slot>
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

