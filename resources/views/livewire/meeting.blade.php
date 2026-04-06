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
                        <x-ui-button variant="secondary-outline" size="sm" :href="route('meetings.dashboard')" wire:navigate class="w-full">
                            <span class="flex items-center gap-2">
                                @svg('heroicon-o-home', 'w-4 h-4')
                                Zum Dashboard
                            </span>
                        </x-ui-button>
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
                        @if($meeting->start_date)
                            <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-calendar', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm text-[var(--ui-secondary)]">Start</span>
                                </div>
                                <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $meeting->start_date->format('d.m.Y H:i') }}</span>
                            </div>
                        @endif
                        @if($meeting->end_date)
                            <div class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-clock', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm text-[var(--ui-secondary)]">Ende</span>
                                </div>
                                <span class="text-sm font-semibold text-[var(--ui-secondary)]">{{ $meeting->end_date->format('d.m.Y H:i') }}</span>
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
                        @if($meeting->isRecurring() && $meeting->series)
                            <a href="{{ route('meetings.series.show', $meeting->series) }}" class="flex items-start justify-between py-2 px-3 rounded-lg bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/40 hover:bg-[var(--ui-muted)] transition-colors">
                                <div class="flex items-center gap-2">
                                    @svg('heroicon-o-arrow-path', 'w-4 h-4 text-[var(--ui-primary)]')
                                    <span class="text-sm text-[var(--ui-secondary)]">Serie</span>
                                </div>
                                <x-ui-badge variant="warning" size="xs">{{ $meeting->series->getRecurrencePatternText() }}</x-ui-badge>
                            </a>
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
                        </div>
                    @endforelse
                </div>
            </div>
        </x-ui-page-sidebar>
    </x-slot>

    <x-ui-page-container>
        <div class="space-y-8">
            {{-- Beschreibung --}}
            @if($meeting->description)
                <div class="p-4 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                    <p class="text-sm text-[var(--ui-secondary)]">{{ $meeting->description }}</p>
                </div>
            @endif

            {{-- Agenda --}}
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-semibold text-[var(--ui-secondary)]">Agenda</h2>
                    @can('update', $meeting)
                        <div class="flex items-center gap-2">
                            @php
                                $typeOptions = [
                                    'topic' => ['label' => 'Thema', 'icon' => 'heroicon-o-chat-bubble-left-right'],
                                    'decision' => ['label' => 'Entscheidung', 'icon' => 'heroicon-o-check-badge'],
                                    'action_item' => ['label' => 'Aufgabe', 'icon' => 'heroicon-o-clipboard-document-check'],
                                    'info' => ['label' => 'Info', 'icon' => 'heroicon-o-information-circle'],
                                ];
                            @endphp
                            @foreach($typeOptions as $type => $opts)
                                <button
                                    wire:click="createAgendaItem('{{ $type }}')"
                                    class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium rounded-md border border-[var(--ui-border)]/60 text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] transition-colors"
                                    title="{{ $opts['label'] }} hinzufügen"
                                >
                                    @svg($opts['icon'], 'w-3.5 h-3.5')
                                    {{ $opts['label'] }}
                                </button>
                            @endforeach
                        </div>
                    @endcan
                </div>

                <div class="space-y-3">
                    @forelse($agendaItems as $item)
                        @php
                            $typeColors = [
                                'topic' => 'border-l-blue-400',
                                'decision' => 'border-l-amber-400',
                                'action_item' => 'border-l-green-400',
                                'info' => 'border-l-gray-400',
                            ];
                            $typeLabels = [
                                'topic' => 'Thema',
                                'decision' => 'Entscheidung',
                                'action_item' => 'Aufgabe',
                                'info' => 'Info',
                            ];
                            $borderClass = $typeColors[$item->type] ?? 'border-l-gray-400';
                        @endphp

                        @if($editingAgendaItemId === $item->id)
                            {{-- Editing mode --}}
                            <div class="p-4 rounded-lg border border-[var(--ui-primary)]/40 bg-[var(--ui-primary-5)] border-l-4 {{ $borderClass }}">
                                <div class="space-y-3">
                                    <input
                                        type="text"
                                        wire:model="editingAgendaItem.title"
                                        class="w-full rounded-md border border-[var(--ui-border)]/60 px-3 py-2 text-sm"
                                        placeholder="Titel"
                                    />
                                    <textarea
                                        wire:model="editingAgendaItem.description"
                                        class="w-full rounded-md border border-[var(--ui-border)]/60 px-3 py-2 text-sm"
                                        rows="2"
                                        placeholder="Beschreibung (optional)"
                                    ></textarea>
                                    <div class="grid grid-cols-3 gap-3">
                                        <select wire:model="editingAgendaItem.type" class="rounded-md border border-[var(--ui-border)]/60 px-3 py-2 text-sm">
                                            <option value="topic">Thema</option>
                                            <option value="decision">Entscheidung</option>
                                            <option value="action_item">Aufgabe</option>
                                            <option value="info">Info</option>
                                        </select>
                                        <input
                                            type="number"
                                            wire:model="editingAgendaItem.duration_minutes"
                                            class="rounded-md border border-[var(--ui-border)]/60 px-3 py-2 text-sm"
                                            placeholder="Min."
                                            min="1"
                                        />
                                        <select wire:model="editingAgendaItem.assigned_to_id" class="rounded-md border border-[var(--ui-border)]/60 px-3 py-2 text-sm">
                                            <option value="">-- Zuweisen --</option>
                                            @foreach($teamMembers as $member)
                                                <option value="{{ $member->id }}">{{ $member->fullname ?? $member->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <x-ui-button variant="primary" size="sm" wire:click="saveAgendaItem">Speichern</x-ui-button>
                                        <x-ui-button variant="secondary-outline" size="sm" wire:click="cancelEditAgendaItem">Abbrechen</x-ui-button>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Display mode --}}
                            <div class="p-4 rounded-lg border border-[var(--ui-border)]/60 bg-white border-l-4 {{ $borderClass }} group hover:bg-[var(--ui-muted-5)] transition-colors">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-1">
                                            <span class="font-medium text-[var(--ui-secondary)]">{{ $item->title }}</span>
                                            <x-ui-badge variant="muted" size="xs">{{ $typeLabels[$item->type] ?? $item->type }}</x-ui-badge>
                                            @if($item->duration_minutes)
                                                <span class="text-xs text-[var(--ui-muted)]">{{ $item->duration_minutes }} Min.</span>
                                            @endif
                                        </div>
                                        @if($item->description)
                                            <p class="text-sm text-[var(--ui-muted)] mt-1">{{ $item->description }}</p>
                                        @endif
                                        @if($item->assignedTo)
                                            <div class="flex items-center gap-1 mt-1 text-xs text-[var(--ui-muted)]">
                                                @svg('heroicon-o-user', 'w-3 h-3')
                                                {{ $item->assignedTo->fullname ?? $item->assignedTo->name }}
                                            </div>
                                        @endif
                                    </div>
                                    @can('update', $meeting)
                                        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <button wire:click="editAgendaItem({{ $item->id }})" class="p-1 text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]" title="Bearbeiten">
                                                @svg('heroicon-o-pencil', 'w-4 h-4')
                                            </button>
                                            <button wire:click="deleteAgendaItem({{ $item->id }})" wire:confirm="Agenda-Punkt wirklich löschen?" class="p-1 text-[var(--ui-muted)] hover:text-red-500" title="Löschen">
                                                @svg('heroicon-o-trash', 'w-4 h-4')
                                            </button>
                                        </div>
                                    @endcan
                                </div>
                            </div>
                        @endif
                    @empty
                        <div class="p-4 text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                            Noch keine Agenda-Punkte. Füge Themen, Entscheidungen oder Aufgaben hinzu.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Notizen --}}
            <div>
                <h2 class="text-base font-semibold text-[var(--ui-secondary)] mb-4">Notizen / Protokoll</h2>

                {{-- Neue Notiz --}}
                <div class="mb-4">
                    <div class="flex gap-3">
                        <textarea
                            wire:model="newNoteContent"
                            class="flex-1 rounded-md border border-[var(--ui-border)]/60 px-3 py-2 text-sm"
                            rows="3"
                            placeholder="Notiz hinzufügen..."
                        ></textarea>
                    </div>
                    @if($newNoteContent)
                        <div class="flex items-center gap-2 mt-2">
                            <x-ui-button variant="primary" size="sm" wire:click="saveNote">Notiz speichern</x-ui-button>
                        </div>
                    @endif
                    @error('newNoteContent')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Bestehende Notizen --}}
                <div class="space-y-3">
                    @forelse($notes as $note)
                        <div class="p-4 rounded-lg border border-[var(--ui-border)]/60 bg-white">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="text-sm font-medium text-[var(--ui-secondary)]">{{ $note->user->fullname ?? $note->user->name }}</span>
                                        <span class="text-xs text-[var(--ui-muted)]">{{ $note->created_at->diffForHumans() }}</span>
                                        @if($note->is_published)
                                            <x-ui-badge variant="success" size="xs">Veröffentlicht</x-ui-badge>
                                        @endif
                                    </div>
                                    <div class="text-sm text-[var(--ui-secondary)] whitespace-pre-wrap">{{ $note->content }}</div>
                                </div>
                                <div class="flex items-center gap-1">
                                    @can('update', $meeting)
                                        <button wire:click="toggleNotePublished({{ $note->id }})" class="p-1 text-[var(--ui-muted)] hover:text-[var(--ui-secondary)]" title="{{ $note->is_published ? 'Zurückziehen' : 'Veröffentlichen' }}">
                                            @svg($note->is_published ? 'heroicon-o-eye-slash' : 'heroicon-o-eye', 'w-4 h-4')
                                        </button>
                                    @endcan
                                    @if($note->user_id === auth()->id() || $meeting->user_id === auth()->id())
                                        <button wire:click="deleteNote({{ $note->id }})" wire:confirm="Notiz wirklich löschen?" class="p-1 text-[var(--ui-muted)] hover:text-red-500" title="Löschen">
                                            @svg('heroicon-o-trash', 'w-4 h-4')
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-4 text-sm text-[var(--ui-muted)] bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                            Noch keine Notizen. Füge Protokoll-Einträge oder Notizen hinzu.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </x-ui-page-container>

    {{-- Meeting Participants Modal --}}
    <livewire:meetings.meeting-participants-modal/>
</x-ui-page>
