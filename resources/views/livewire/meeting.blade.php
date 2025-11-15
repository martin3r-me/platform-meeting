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
                            <x-ui-button variant="primary" size="sm" wire:click="openCreateAppointmentModal" class="w-full">
                                <span class="flex items-center gap-2">
                                    @svg('heroicon-o-plus-circle', 'w-4 h-4')
                                    Neuer Termin
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

                {{-- Appointments --}}
                @if($appointments->count() > 0)
                    <div>
                        <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider mb-4">Termine</h3>
                        <div class="space-y-2">
                            @foreach($appointments as $appointment)
                                <a 
                                    href="{{ route('meetings.appointments.show', $appointment) }}" 
                                    wire:navigate
                                    class="flex items-center justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted)] transition-colors"
                                >
                                    <div class="flex items-center gap-2">
                                        @svg('heroicon-o-calendar-days', 'w-4 h-4 text-[var(--ui-muted)]')
                                        <span class="text-sm text-[var(--ui-secondary)]">{{ $appointment->user->fullname ?? $appointment->user->name }}</span>
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
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
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
        {{-- Meta-Daten Header --}}
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden mb-6">
            <div class="p-6 lg:p-8">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <div class="flex-1 min-w-0">
                        <h1 class="text-3xl font-bold text-[var(--ui-secondary)] mb-4 tracking-tight leading-tight">{{ $meeting->title }}</h1>
                        
                        {{-- Meta Informationen --}}
                        <div class="space-y-2">
                            {{-- Erste Zeile: Team & Erstellt von --}}
                            <div class="flex flex-wrap items-center gap-6 text-sm text-[var(--ui-muted)]">
                                @if($meeting->team)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-user-group', 'w-4 h-4')
                                        <span>Team: <span class="text-[var(--ui-secondary)]">{{ $meeting->team->name }}</span></span>
                                    </span>
                                @endif
                                @if($meeting->user)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-user-circle', 'w-4 h-4')
                                        <span>Erstellt von: <span class="text-[var(--ui-secondary)]">{{ $meeting->user->fullname ?? $meeting->user->name }}</span></span>
                                    </span>
                                @endif
                            </div>
                            
                            {{-- Zweite Zeile: Datum & Ort --}}
                            <div class="flex flex-wrap items-center gap-6 text-sm text-[var(--ui-muted)]">
                                <span class="flex items-center gap-2">
                                    @svg('heroicon-o-calendar', 'w-4 h-4')
                                    <span>{{ $meeting->start_date->format('d.m.Y H:i') }} - {{ $meeting->end_date->format('H:i') }}</span>
                                </span>
                                @if($meeting->location)
                                    <span class="flex items-center gap-2">
                                        @svg('heroicon-o-map-pin', 'w-4 h-4')
                                        <span>{{ $meeting->location }}</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    {{-- Status Badge --}}
                    <div class="flex flex-col items-end gap-2 flex-shrink-0">
                        <x-ui-badge variant="primary" size="sm">{{ ucfirst($meeting->status) }}</x-ui-badge>
                    </div>
                </div>
            </div>
        </div>

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

            {{-- Appointments Übersicht --}}
            @if($appointments->count() > 0)
                <div>
                    <h3 class="text-lg font-semibold mb-4">Termine</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($appointments as $appointment)
                            <a 
                                href="{{ route('meetings.appointments.show', $appointment) }}" 
                                wire:navigate
                                class="block p-4 rounded-lg border border-[var(--ui-border)]/60 bg-white hover:shadow-md transition-shadow"
                            >
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="font-semibold text-[var(--ui-secondary)]">{{ $appointment->user->fullname ?? $appointment->user->name }}</h4>
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
                                <p class="text-sm text-[var(--ui-muted)]">
                                    @svg('heroicon-o-calendar-days', 'w-4 h-4 inline mr-1')
                                    Termin anzeigen →
                                </p>
                            </a>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="text-center py-12">
                    <p class="text-[var(--ui-muted)] mb-4">Noch keine Termine vorhanden</p>
                    @can('update', $meeting)
                        <x-ui-button variant="primary" size="sm" wire:click="openCreateAppointmentModal">
                            Ersten Termin anlegen
                        </x-ui-button>
                    @endcan
                </div>
            @endif
        </div>
    </x-ui-page-container>
</x-ui-page>

{{-- Create Appointment Modal --}}
@if($showCreateAppointmentModal)
    <x-ui-modal wire:model="showCreateAppointmentModal" title="Neuen Termin anlegen">
        <div class="space-y-4">
            <x-ui-input-select
                name="createAppointment.user_id"
                wire:model="createAppointment.user_id"
                label="Teilnehmer"
                :options="$teamMembers"
                optionValue="id"
                optionLabel="name"
                :nullable="false"
                nullLabel="Teilnehmer auswählen"
                required
            />
            <x-ui-input-datetime
                wire:model="createAppointment.start_date"
                label="Startdatum & Zeit"
                required
            />
            <x-ui-input-datetime
                wire:model="createAppointment.end_date"
                label="Enddatum & Zeit"
                required
            />
        </div>
        
        <x-slot name="footer">
            <div class="flex items-center justify-end gap-2">
                <x-ui-button variant="secondary-outline" size="sm" wire:click="closeCreateAppointmentModal">
                    Abbrechen
                </x-ui-button>
                <x-ui-button variant="primary" size="sm" wire:click="createAppointment">
                    Termin anlegen
                </x-ui-button>
            </div>
        </x-slot>
    </x-ui-modal>
@endif
