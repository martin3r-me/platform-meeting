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
                                    @if($meeting->isTeamsCall())
                                        @svg('heroicon-o-video-camera', 'w-4 h-4 text-[var(--ui-primary)]')
                                    @elseif($meeting->isOnlineMeeting())
                                        @svg('heroicon-o-link', 'w-4 h-4 text-[var(--ui-primary)]')
                                    @else
                                        @svg('heroicon-o-map-pin', 'w-4 h-4 text-[var(--ui-primary)]')
                                    @endif
                                    <span class="text-sm text-[var(--ui-secondary)]">Ort</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $meeting->location }}</span>
                                    @if($meeting->isTeamsCall())
                                        <x-ui-badge variant="primary" size="xs">Teams</x-ui-badge>
                                    @elseif($meeting->isOnlineMeeting())
                                        <x-ui-badge variant="info" size="xs">Online</x-ui-badge>
                                    @elseif($meeting->isRoom())
                                        <x-ui-badge variant="secondary" size="xs">Raum</x-ui-badge>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if($meeting->isRecurring())
                            <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-arrow-path', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <div class="flex flex-col">
                                        <span class="text-sm text-[var(--ui-secondary)]">Serientermin</span>
                                        @if($meeting->getRecurrencePatternText())
                                            <span class="text-xs text-[var(--ui-muted)]">{{ $meeting->getRecurrencePatternText() }}</span>
                                        @endif
                                    </div>
                                </div>
                                <x-ui-badge variant="warning" size="xs">Wiederkehrend</x-ui-badge>
                            </div>
                        @endif
                        @if($meeting->getTeamsJoinUrl())
                            <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-video-camera', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm text-[var(--ui-secondary)]">Teams Meeting</span>
                                </div>
                                <a 
                                    href="{{ $meeting->getTeamsJoinUrl() }}" 
                                    target="_blank" 
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors"
                                >
                                    @svg('heroicon-o-arrow-top-right-on-square', 'w-3 h-3')
                                    Beitreten
                                </a>
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
                                    <span class="text-sm text-[var(--ui-secondary)]">{{ $participant->display_name }}</span>
                                    @if($participant->isExternal())
                                        <x-ui-badge variant="muted" size="xs">Extern</x-ui-badge>
                                    @endif
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
                        <li class="relative flex gap-x-4 py-4 hover:bg-gray-50 dark:hover:bg-gray-900/50 rounded-lg transition-colors group">
                            {{-- Avatar --}}
                            @if($appointment->user->avatar ?? null)
                                <img src="{{ $appointment->user->avatar }}" alt="{{ $appointment->user->name }}" class="size-8 flex-none rounded-full dark:outline dark:-outline-offset-1 dark:outline-white/10" />
                            @else
                                <div class="size-8 flex-none rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center text-xs font-medium text-gray-600 dark:text-gray-300 dark:outline dark:-outline-offset-1 dark:outline-white/10">
                                    {{ strtoupper(substr($appointment->user->name ?? '?', 0, 1)) }}
                                </div>
                            @endif

                            <div class="flex-auto min-w-0">
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="font-semibold text-gray-900 dark:text-white truncate">
                                        {{ $appointment->user->fullname ?? $appointment->user->name }}
                                    </h3>
                                    @if($appointment->meeting->isRecurring())
                                        <div class="flex flex-col items-end gap-0.5 flex-shrink-0">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300" title="Serientermin">
                                                @svg('heroicon-o-arrow-path', 'w-3 h-3')
                                                Serie
                                            </span>
                                            @if($appointment->meeting->getRecurrencePatternText())
                                                <span class="text-xs text-gray-500 dark:text-gray-400">{{ $appointment->meeting->getRecurrencePatternText() }}</span>
                                            @endif
                                        </div>
                                    @endif
                                    @if($appointment->meeting->getTeamsJoinUrl())
                                        <a 
                                            href="{{ $appointment->meeting->getTeamsJoinUrl() }}" 
                                            target="_blank" 
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 transition-colors flex-shrink-0"
                                            title="Teams Meeting beitreten"
                                        >
                                            @svg('heroicon-o-video-camera', 'w-3 h-3')
                                            <span class="hidden sm:inline">Teams</span>
                                        </a>
                                    @endif
                                </div>
                                <dl class="flex flex-col gap-1.5 text-sm text-gray-500 dark:text-gray-400">
                                    {{-- Datum & Zeit --}}
                                    <div class="flex items-center gap-2">
                                        <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-4 text-gray-400 dark:text-gray-500 flex-shrink-0">
                                            <path d="M5.75 2a.75.75 0 0 1 .75.75V4h7V2.75a.75.75 0 0 1 1.5 0V4h.25A2.75 2.75 0 0 1 18 6.75v8.5A2.75 2.75 0 0 1 15.25 18H4.75A2.75 2.75 0 0 1 2 15.25v-8.5A2.75 2.75 0 0 1 4.75 4H5V2.75A.75.75 0 0 1 5.75 2Zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75Z" clip-rule="evenodd" fill-rule="evenodd" />
                                        </svg>
                                        <time datetime="{{ $appointment->start_date->toIso8601String() }}" class="font-medium">
                                            {{ $appointment->start_date->locale('de')->isoFormat('D. MMMM YYYY [um] HH:mm') }} Uhr
                                        </time>
                                    </div>
                                    {{-- Ort --}}
                                    @if($appointment->location ?? $appointment->meeting->location)
                                        @php
                                            $location = $appointment->location ?? $appointment->meeting->location;
                                            $locationType = $appointment->meeting->getLocationType();
                                        @endphp
                                        <div class="flex items-center gap-2">
                                            @if($locationType === 'teams')
                                                <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-4 text-blue-500 dark:text-blue-400 flex-shrink-0">
                                                    <path d="M3.25 4A2.25 2.25 0 0 0 1 6.25v7.5A2.25 2.25 0 0 0 3.25 16h7.5A2.25 2.25 0 0 0 13 13.75v-7.5A2.25 2.25 0 0 0 10.75 4h-7.5ZM2.5 6.25c0-.414.336-.75.75-.75h7.5c.414 0 .75.336.75.75v7.5a.75.75 0 0 1-.75.75h-7.5a.75.75 0 0 1-.75-.75v-7.5ZM18.25 7.5a.75.75 0 0 0-1.5 0v5.75a.75.75 0 0 1-.75.75H9.31l1.47 1.47a.75.75 0 1 0 1.06-1.06l-2.75-2.75a.75.75 0 0 0-1.06 0l-2.75 2.75a.75.75 0 1 0 1.06 1.06l1.47-1.47h6.44A2.25 2.25 0 0 0 18.25 13.25V7.5Z" />
                                                </svg>
                                            @elseif($locationType === 'online')
                                                <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-4 text-green-500 dark:text-green-400 flex-shrink-0">
                                                    <path d="M10.75 10.818v2.614A3.13 3.13 0 0 0 11.888 13c.482-.315.612-.648.612-.875 0-.227-.13-.56-.612-.875a3.13 3.13 0 0 0-1.138-.432ZM8.33 8.62c.053.055.115.11.184.164.208.16.46.284.736.363V6.603a2.45 2.45 0 0 0-.35.13c-.14.065-.27.143-.386.233-.377.292-.514.627-.514.909 0 .184.058.39.202.592.037.051.08.102.128.152Z" />
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Zm-8-6a.75.75 0 0 1 .75.75v.316a3.78 3.78 0 0 1 .653.38c.58.319.808.785.808 1.27 0 .514-.29.902-.808 1.27a3.78 3.78 0 0 1-.653.38V9.25A.75.75 0 0 1 10 8.5v-.316a3.78 3.78 0 0 1-.653-.38C8.79 7.46 8.562 6.994 8.562 6.5c0-.514.29-.902.808-1.27a3.78 3.78 0 0 1 .653-.38V4.25A.75.75 0 0 1 10 3.5V4Z" clip-rule="evenodd" />
                                                </svg>
                                            @else
                                                <svg viewBox="0 0 20 20" fill="currentColor" data-slot="icon" aria-hidden="true" class="size-4 text-gray-400 dark:text-gray-500 flex-shrink-0">
                                                    <path d="m9.69 18.933.003.001C9.89 19.02 10 19 10 19s.11.02.308-.066l.002-.001.006-.003.018-.008a5.741 5.741 0 0 0 .281-.14c.186-.096.446-.24.757-.433.62-.384 1.445-.966 2.274-1.765C15.302 14.988 17 12.493 17 9A7 7 0 1 0 3 9c0 3.492 1.698 5.988 3.355 7.584a13.731 13.731 0 0 0 2.273 1.765 11.842 11.842 0 0 0 .976.544l.062.029.018.008.006.003ZM10 11.25a2.25 2.25 0 1 0 0-4.5 2.25 2.25 0 0 0 0 4.5Z" clip-rule="evenodd" fill-rule="evenodd" />
                                                </svg>
                                            @endif
                                            <span class="truncate">{{ $location }}</span>
                                            @if($locationType === 'teams')
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 flex-shrink-0">
                                                    Teams
                                                </span>
                                            @elseif($locationType === 'online')
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300 flex-shrink-0">
                                                    Online
                                                </span>
                                            @elseif($locationType === 'room')
                                                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300 flex-shrink-0">
                                                    Raum
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </dl>
                            </div>

                            {{-- Aktionen direkt sichtbar --}}
                            <div class="flex items-center gap-2 flex-shrink-0 opacity-0 group-hover:opacity-100 transition-opacity">
                                <a 
                                    href="{{ route('meetings.appointments.show', $appointment) }}" 
                                    wire:navigate
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700 transition-colors"
                                    title="Termin anzeigen"
                                >
                                    @svg('heroicon-o-eye', 'w-4 h-4')
                                    <span class="hidden sm:inline">Anzeigen</span>
                                </a>
                                @can('update', $meeting)
                                    <button 
                                        wire:click="deleteAppointment({{ $appointment->id }})"
                                        wire:confirm="Möchten Sie diesen Termin wirklich löschen?"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-md bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900/30 dark:text-red-300 dark:hover:bg-red-900/50 transition-colors"
                                        title="Termin löschen"
                                    >
                                        @svg('heroicon-o-trash', 'w-4 h-4')
                                        <span class="hidden sm:inline">Löschen</span>
                                    </button>
                                @endcan
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
            <div class="space-y-6">
                {{-- Teilnehmer (Multi-Select mit Checkboxes) --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        Teilnehmer <span class="text-red-500">*</span>
                    </label>
                    <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                        @forelse($meetingParticipants as $participant)
                            <label class="flex items-center gap-2 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 p-2 rounded">
                                <input 
                                    type="checkbox" 
                                    wire:model="createAppointment.user_ids"
                                    value="{{ $participant->id }}"
                                    class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700"
                                />
                                <span class="text-sm text-gray-900 dark:text-white">
                                    {{ $participant->fullname ?? $participant->name }}
                                </span>
                            </label>
                        @empty
                            <p class="text-sm text-gray-500 dark:text-gray-400">Keine Teilnehmer verfügbar</p>
                        @endforelse
                    </div>
                    @error('createAppointment.user_ids')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Startdatum & Zeit --}}
                <div>
                    <x-ui-input-datetime
                        name="createAppointment.start_date"
                        wire:model.live="createAppointment.start_date"
                        label="Startdatum & Zeit"
                        required
                    />
                    @error('createAppointment.start_date')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Dauer --}}
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2">
                        Dauer <span class="text-red-500">*</span>
                    </label>
                    <div class="grid grid-cols-4 gap-2">
                        @foreach([15, 30, 45, 60, 90, 180, 240] as $minutes)
                            @php
                                $hours = $minutes >= 60 ? round($minutes / 60, 1) : null;
                                $label = $hours ? ($hours == 1 ? '1 Stunde' : $hours . ' Stunden') : $minutes . ' Min.';
                                $isLong = $minutes >= 180;
                            @endphp
                            <button
                                type="button"
                                wire:click="$set('createAppointment.duration_minutes', {{ $minutes }})"
                                class="px-3 py-2 text-sm font-medium rounded-md border transition-colors
                                    {{ $createAppointment['duration_minutes'] == $minutes 
                                        ? 'bg-indigo-600 text-white border-indigo-600 dark:bg-indigo-500 dark:border-indigo-500' 
                                        : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700' 
                                    }}
                                    {{ $isLong ? 'col-span-2' : '' }}
                                "
                            >
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                    @if($createAppointment['duration_minutes'] >= 180)
                        <div class="mt-2 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                            <p class="text-sm text-yellow-800 dark:text-yellow-200">
                                ⚠️ Das ist eine sehr lange Dauer. Ist das wirklich notwendig?
                            </p>
                        </div>
                    @endif
                    @error('createAppointment.duration_minutes')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            
            <x-slot name="footer">
                <div class="flex items-center justify-end gap-2">
                    <x-ui-button variant="secondary-outline" size="sm" wire:click="closeCreateAppointmentModal">
                        Abbrechen
                    </x-ui-button>
                    <x-ui-button variant="primary" size="sm" wire:click="createAppointment" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="createAppointment">Termin anlegen</span>
                        <span wire:loading wire:target="createAppointment">Wird erstellt...</span>
                    </x-ui-button>
                </div>
            </x-slot>
        </x-ui-modal>
    @endif
</x-ui-page>
