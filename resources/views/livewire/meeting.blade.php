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
                        @php
                            $organizerAppointment = $meeting->appointments()->where('user_id', $meeting->user_id)->first();
                        @endphp
                        @if($organizerAppointment)
                            <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-calendar', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm text-[var(--ui-secondary)]">Start</span>
                                </div>
                                <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $organizerAppointment->start_date->format('d.m.Y H:i') }}</span>
                            </div>
                            <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-clock', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm text-[var(--ui-secondary)]">Ende</span>
                                </div>
                                <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $organizerAppointment->end_date->format('d.m.Y H:i') }}</span>
                            </div>
                        @endif
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
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-[var(--ui-secondary)] uppercase tracking-wider">Teilnehmer</h3>
                        @can('update', $meeting)
                            <button 
                                type="button"
                                @click="$dispatch('open-modal-meeting-participants', { meetingId: {{ $meeting->id }} })"
                                class="text-xs text-[var(--ui-primary)] hover:text-[var(--ui-secondary)] transition-colors"
                                title="Teilnehmer verwalten"
                            >
                                @svg('heroicon-o-user-plus', 'w-4 h-4')
                            </button>
                        @endcan
                    </div>
                    <div class="space-y-2">
                        @foreach($meeting->participants as $participant)
                            <div class="flex items-center justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-user', 'w-4 h-4 text-[var(--ui-muted)]')
                                    <span class="text-sm text-[var(--ui-secondary)]">{{ $participant->user->fullname ?? $participant->user->name }}</span>
                                    @if($participant->role === 'organizer')
                                        <x-ui-badge variant="primary" size="xs">Organisator</x-ui-badge>
                                    @endif
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

    <x-ui-page-container padding="p-0" spacing="">
        <div class="p-5">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Anstehende Termine</h2>

            <div class="lg:grid lg:grid-cols-12 lg:gap-x-16">
                {{-- Linke Spalte: Termine-Liste --}}
                <ol class="mt-4 divide-y divide-gray-100 text-sm/6 lg:col-span-7 xl:col-span-8 dark:divide-white/10">
                    @forelse($appointments->sortBy('start_date') as $appointment)
                        <li class="relative flex gap-x-6 py-6 xl:static">
                            {{-- Avatar --}}
                            @if($appointment->user->avatar ?? null)
                                <img src="{{ $appointment->user->avatar }}" alt="{{ $appointment->user->name }}" class="size-14 flex-none rounded-full dark:outline dark:-outline-offset-1 dark:outline-white/10" />
                            @else
                                <div class="size-14 flex-none rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-lg font-medium text-gray-600 dark:text-gray-300 dark:outline dark:-outline-offset-1 dark:outline-white/10">
                                    {{ strtoupper(substr($appointment->user->name ?? '?', 0, 1)) }}
                                </div>
                            @endif

                            <div class="flex-auto">
                                <h3 class="pr-10 font-semibold text-gray-900 xl:pr-0 dark:text-white">
                                    {{ $appointment->user->fullname ?? $appointment->user->name }}
                                </h3>
                                <dl class="mt-2 flex flex-col text-gray-500 xl:flex-row dark:text-gray-400">
                                    {{-- Datum & Zeit --}}
                                    <div class="flex items-start gap-x-3">
                                        <dt class="mt-0.5">
                                            <span class="sr-only">Datum</span>
                                            <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5 text-gray-400 dark:text-gray-500">
                                                <path d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" fill-rule="evenodd" />
                                            </svg>
                                        </dt>
                                        <dd>
                                            <time datetime="{{ $appointment->start_date->toIso8601String() }}">
                                                {{ $appointment->start_date->locale('de')->isoFormat('D. MMMM YYYY [um] HH:mm') }} Uhr
                                            </time>
                                        </dd>
                                    </div>
                                    {{-- Ort --}}
                                    @if($appointment->location ?? $appointment->meeting->location)
                                        <div class="mt-2 flex items-start gap-x-3 xl:mt-0 xl:ml-3.5 xl:border-l xl:border-gray-400/50 xl:pl-3.5 dark:xl:border-gray-500/50">
                                            <dt class="mt-0.5">
                                                <span class="sr-only">Ort</span>
                                                <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5 text-gray-400 dark:text-gray-500">
                                                    <path d="m9.69 18.933.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 1 0 3 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 0 0 2.273 1.765 11.842 11.842 0 0 0 .976.544l.062.029.018.008.006.003ZM10 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" clip-rule="evenodd" fill-rule="evenodd" />
                                                </svg>
                                            </dt>
                                            <dd>{{ $appointment->location ?? $appointment->meeting->location }}</dd>
                                        </div>
                                    @endif
                                </dl>
                            </div>

                            {{-- Dropdown Menu --}}
                            <div class="absolute top-6 right-0 xl:relative xl:top-auto xl:right-auto xl:self-center">
                                <div class="relative" x-data="{ open: false }">
                                    <button 
                                        @click="open = !open"
                                        class="relative flex items-center rounded-full text-gray-500 hover:text-gray-600 dark:text-gray-400 dark:hover:text-white"
                                    >
                                        <span class="absolute -inset-2"></span>
                                        <span class="sr-only">Optionen öffnen</span>
                                        <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
                                            <path d="M3 10a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM8.5 10a1.5 1.5 0 1 1 3 0 1.5 1.5 0 0 1-3 0ZM15.5 8.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" />
                                        </svg>
                                    </button>
                                    <div 
                                        x-show="open"
                                        @click.away="open = false"
                                        x-transition
                                        class="absolute right-0 z-10 mt-2 w-36 origin-top-right rounded-md bg-white shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none dark:bg-gray-800 dark:ring-white/10"
                                    >
                                        <div class="py-1">
                                            <a 
                                                href="{{ route('meetings.appointments.show', $appointment) }}" 
                                                wire:navigate
                                                class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:bg-gray-100 focus:text-gray-900 focus:outline-hidden dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white dark:focus:bg-white/5 dark:focus:text-white"
                                            >
                                                Anzeigen
                                            </a>
                                            @can('update', $meeting)
                                                <button 
                                                    wire:click="deleteAppointment({{ $appointment->id }})"
                                                    class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900 focus:bg-gray-100 focus:text-gray-900 focus:outline-hidden dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-white dark:focus:bg-white/5 dark:focus:text-white"
                                                >
                                                    Löschen
                                                </button>
                                            @endcan
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="py-12 text-center">
                            <p class="text-gray-500 dark:text-gray-400">Noch keine Termine vorhanden</p>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">Wählen Sie ein Datum im Kalender aus, um einen Termin hinzuzufügen</p>
                        </li>
                    @endforelse
                </ol>

                {{-- Rechte Spalte: Kalender --}}
                <div class="mt-10 text-center lg:col-start-8 lg:col-end-13 lg:row-start-1 lg:mt-9 xl:col-start-9">
                    {{-- Monats-Navigation --}}
                    <div class="flex items-center text-gray-900 dark:text-white">
                        <button 
                            type="button" 
                            wire:click="previousMonth"
                            class="-m-1.5 flex flex-none items-center justify-center p-1.5 text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-white"
                        >
                            <span class="sr-only">Vorheriger Monat</span>
                            <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
                                <path d="M11.78 5.22a.75.75 0 0 1 0 1.06L8.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06l-4.25-4.25a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Z" clip-rule="evenodd" fill-rule="evenodd" />
                            </svg>
                        </button>
                        <div class="flex-auto text-sm font-semibold">{{ $this->calendarMonthName }}</div>
                        <button 
                            type="button" 
                            wire:click="nextMonth"
                            class="-m-1.5 flex flex-none items-center justify-center p-1.5 text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-white"
                        >
                            <span class="sr-only">Nächster Monat</span>
                            <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-5">
                                <path d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" fill-rule="evenodd" />
                            </svg>
                        </button>
                    </div>

                    {{-- Wochentage Header --}}
                    <div class="mt-6 grid grid-cols-7 text-xs/6 text-gray-500 dark:text-gray-400">
                        <div>M</div>
                        <div>T</div>
                        <div>W</div>
                        <div>T</div>
                        <div>F</div>
                        <div>S</div>
                        <div>S</div>
                    </div>

                    {{-- Kalender Grid --}}
                    <div class="isolate mt-2 grid grid-cols-7 gap-px rounded-lg bg-gray-200 text-sm shadow-sm ring-1 ring-gray-200 dark:bg-white/15 dark:shadow-none dark:ring-white/15">
                        @foreach($this->calendarDays as $day)
                            @php
                                $isSelected = $selectedDate === $day['date'];
                                $buttonClasses = 'py-1.5 first:rounded-tl-lg last:rounded-br-lg hover:bg-gray-100 focus:z-10 nth-36:rounded-bl-lg nth-7:rounded-tr-lg dark:hover:bg-gray-900/25';
                                
                                if (!$day['isCurrentMonth']) {
                                    $buttonClasses .= ' bg-gray-50 text-gray-400 dark:bg-gray-900/75 dark:text-gray-500';
                                } else {
                                    $buttonClasses .= ' bg-white dark:bg-gray-900/90';
                                    
                                    if ($isSelected) {
                                        $buttonClasses .= ' font-semibold text-white dark:text-gray-900';
                                    } elseif ($day['isToday']) {
                                        $buttonClasses .= ' font-semibold text-indigo-600 dark:text-indigo-400';
                                    } else {
                                        $buttonClasses .= ' text-gray-900 dark:text-white';
                                    }
                                }
                                
                                $timeClasses = 'mx-auto flex size-7 items-center justify-center rounded-full';
                                if ($isSelected) {
                                    if ($day['isToday']) {
                                        $timeClasses .= ' bg-indigo-600 dark:bg-indigo-500';
                                    } else {
                                        $timeClasses .= ' bg-gray-900 dark:bg-white';
                                    }
                                }
                            @endphp
                            <button 
                                type="button"
                                wire:click="selectDate('{{ $day['date'] }}')"
                                class="{{ $buttonClasses }}"
                            >
                                <time 
                                    datetime="{{ $day['date'] }}" 
                                    class="{{ $timeClasses }}"
                                >
                                    {{ $day['day'] }}
                                </time>
                            </button>
                        @endforeach
                    </div>

                    {{-- Add Event Button --}}
                    @can('update', $meeting)
                        <button 
                            type="button" 
                            wire:click="openCreateAppointmentModal"
                            class="mt-8 w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 dark:bg-indigo-500 dark:shadow-none dark:hover:bg-indigo-400 dark:focus-visible:outline-indigo-500"
                        >
                            Termin hinzufügen
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </x-ui-page-container>

    {{-- Meeting Participants Modal --}}
    <livewire:meetings.meeting-participants-modal/>

    {{-- Create Appointment Modal --}}
    @if($showCreateAppointmentModal)
        <x-ui-modal wire:model="showCreateAppointmentModal" title="Neuen Termin anlegen">
            <div class="space-y-4">
                <x-ui-input-select
                    name="createAppointment.user_id"
                    wire:model="createAppointment.user_id"
                    label="Teilnehmer"
                    :options="$meetingParticipants"
                    optionValue="id"
                    optionLabel="name"
                    :nullable="false"
                    nullLabel="Teilnehmer auswählen"
                    required
                />
                <x-ui-input-datetime
                    name="createAppointment.start_date"
                    wire:model="createAppointment.start_date"
                    label="Startdatum & Zeit"
                    required
                />
                <x-ui-input-datetime
                    name="createAppointment.end_date"
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
</x-ui-page>
