<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="" />
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        {{-- Header Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div class="flex-1 min-w-0">
                        <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4 tracking-tight leading-tight">{{ $agendaItem->title }}</h1>
                        
                        {{-- Meta Informationen -- schlicht ohne Rahmen --}}
                        <div class="space-y-2">
                            {{-- Erste Zeile: Appointment & Meeting --}}
                            <div class="flex flex-wrap items-center gap-6 text-sm text-[var(--ui-muted)]">
                                @if($agendaItem->appointment)
                                    <a 
                                        href="{{ route('meetings.appointments.show', $agendaItem->appointment) }}" 
                                        wire:navigate
                                        class="flex items-center gap-2 hover:text-[var(--ui-primary)] transition-colors"
                                    >
                                        @svg('heroicon-o-calendar-days', 'w-4 h-4')
                                        <span>Termin: <span class="text-[var(--ui-secondary)]">{{ $agendaItem->appointment->meeting->title }}</span></span>
                                    </a>
                                @endif
                                @if($agendaItem->appointment && $agendaItem->appointment->meeting)
                                    <a 
                                        href="{{ route('meetings.show', $agendaItem->appointment->meeting) }}" 
                                        wire:navigate
                                        class="flex items-center gap-2 hover:text-[var(--ui-primary)] transition-colors"
                                    >
                                        @svg('heroicon-o-video-camera', 'w-4 h-4')
                                        <span>Meeting: <span class="text-[var(--ui-secondary)]">{{ $agendaItem->appointment->meeting->title }}</span></span>
                                    </a>
                                @endif
                            </div>
                            
                            {{-- Zweite Zeile: Personen & Details --}}
                            <div class="flex flex-wrap items-center gap-6 text-sm text-[var(--ui-muted)]">
                                @if($agendaItem->assignedTo)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-user', 'w-4 h-4')
                                        <span>Verantwortlich: <span class="text-[var(--ui-secondary)]">{{ $agendaItem->assignedTo->fullname ?? $agendaItem->assignedTo->name }}</span></span>
                                    </span>
                                @endif
                                @if($agendaItem->duration_minutes)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-clock', 'w-4 h-4')
                                        <span>Dauer: <span class="text-[var(--ui-secondary)] font-medium">{{ $agendaItem->duration_minutes }} Min.</span></span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    {{-- Status Badges -- kleiner --}}
                    <div class="flex flex-col items-end gap-2 flex-shrink-0">
                        @php
                            $statusColors = [
                                'todo' => 'muted',
                                'in_progress' => 'warning',
                                'done' => 'success',
                            ];
                            $statusColor = $statusColors[$agendaItem->status] ?? 'muted';
                        @endphp
                        <x-ui-badge :variant="$statusColor" size="sm">
                            {{ ucfirst(str_replace('_', ' ', $agendaItem->status)) }}
                        </x-ui-badge>
                    </div>
                </div>
            </div>
        </div>
        {{-- Form Section --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                {{-- Grundinformationen --}}
                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4">Grundinformationen</h2>
                    <x-ui-form-grid :cols="2" :gap="6">
                        <div class="col-span-2">
                            <x-ui-input-text
                                name="agendaItem.title"
                                label="Titel"
                                wire:model.live.debounce.1000ms="agendaItem.title"
                                placeholder="Agenda-Punkt Titel eingeben..."
                                required
                                :errorKey="'agendaItem.title'"
                            />
                        </div>
                        <div>
                            <x-ui-input-select
                                name="agendaItem.status"
                                label="Status"
                                :options="[
                                    ['value' => 'todo', 'label' => 'Todo'],
                                    ['value' => 'in_progress', 'label' => 'In Progress'],
                                    ['value' => 'done', 'label' => 'Done'],
                                ]"
                                optionValue="value"
                                optionLabel="label"
                                :nullable="false"
                                wire:model.live="agendaItem.status"
                            />
                        </div>
                        <div>
                            <x-ui-input-text
                                name="agendaItem.duration_minutes"
                                label="Dauer (Minuten)"
                                wire:model.live.debounce.1000ms="agendaItem.duration_minutes"
                                type="number"
                                placeholder="z.B. 15"
                            />
                        </div>
                    </x-ui-form-grid>
                </div>

                {{-- Verantwortung --}}
                <div class="mb-8 pb-8 border-b border-[var(--ui-border)]/60">
                    <h2 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4">Verantwortung</h2>
                    <x-ui-form-grid :cols="2" :gap="6">
                        <div>
                            <x-ui-input-select
                                name="agendaItem.assigned_to_id"
                                label="Verantwortlicher"
                                :options="$teamUsers ?? []"
                                optionValue="id"
                                optionLabel="name"
                                :nullable="true"
                                nullLabel="– Verantwortlichen auswählen –"
                                wire:model.live="agendaItem.assigned_to_id"
                            />
                        </div>
                    </x-ui-form-grid>
                </div>

                {{-- Beschreibung --}}
                <div>
                    <h2 class="text-lg font-semibold text-[var(--ui-secondary)] mb-4">Beschreibung</h2>
                    <x-ui-input-textarea
                        name="agendaItem.description"
                        label=""
                        wire:model.live.debounce.1000ms="agendaItem.description"
                        placeholder="Agenda-Punkt Beschreibung (optional)"
                        rows="6"
                        :errorKey="'agendaItem.description'"
                    />
                </div>
            </div>
        </div>
    </x-ui-page-container>

    <x-slot name="sidebar">
        <x-ui-page-sidebar title="Übersicht" width="w-80" :defaultOpen="true">
            <div class="p-6 space-y-6">
                {{-- Aktionen (Save/Delete) --}}
                <div>
                    <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Aktionen</h3>
                    <div class="space-y-2">
                        @can('update', $agendaItem->appointment)
                            @if($this->isDirty())
                                <x-ui-button variant="primary" size="sm" wire:click="save" class="w-full">
                                    <span class="inline-flex items-center gap-2">
                                        @svg('heroicon-o-check','w-4 h-4')
                                        Speichern
                                    </span>
                                </x-ui-button>
                            @endif
                        @endcan
                        @can('update', $agendaItem->appointment)
                            <x-ui-confirm-button 
                                action="delete" 
                                text="Löschen" 
                                confirmText="Wirklich löschen?" 
                                variant="danger"
                                :icon="@svg('heroicon-o-trash', 'w-4 h-4')->toHtml()"
                                class="w-full"
                            />
                        @endcan
                    </div>
                </div>

                {{-- Quick Links --}}
                <div class="space-y-2">
                    @if($agendaItem->appointment)
                        <x-ui-button variant="secondary-outline" size="sm" :href="route('meetings.appointments.show', $agendaItem->appointment)" wire:navigate class="w-full">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-calendar-days', 'w-4 h-4')
                                Zum Termin
                            </span>
                        </x-ui-button>
                    @endif
                    @if($agendaItem->appointment && $agendaItem->appointment->meeting)
                        <x-ui-button variant="secondary-outline" size="sm" :href="route('meetings.show', $agendaItem->appointment->meeting)" wire:navigate class="w-full">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-video-camera', 'w-4 h-4')
                                Zum Meeting
                            </span>
                        </x-ui-button>
                    @endif
                </div>

                {{-- Status (interaktiv) --}}
                <div class="space-y-2">
                    <div class="w-full text-left flex items-center justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                        <div class="flex items-center gap-2">
                            @php
                                $statusIcons = [
                                    'todo' => 'heroicon-o-circle-stack',
                                    'in_progress' => 'heroicon-o-arrow-path',
                                    'done' => 'heroicon-o-check-circle',
                                ];
                                $statusIcon = $statusIcons[$agendaItem->status] ?? 'heroicon-o-circle-stack';
                                $statusColor = $statusColors[$agendaItem->status] ?? 'muted';
                            @endphp
                            @svg($statusIcon, 'w-4 h-4 text-[var(--ui-' . $statusColor . ')]')
                            <span class="text-sm text-[var(--ui-secondary)]">Status</span>
                        </div>
                        <x-ui-badge :variant="$statusColor" size="xs">
                            {{ ucfirst(str_replace('_', ' ', $agendaItem->status)) }}
                        </x-ui-badge>
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
</x-ui-page>

