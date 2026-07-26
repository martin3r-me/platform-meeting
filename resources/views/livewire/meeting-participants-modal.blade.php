<x-nx-modal wire:model="modalShow" size="md">
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 bg-[color:var(--nx-accent)] text-[color:var(--nx-on-accent)] rounded-lg flex items-center justify-center">
                @svg('heroicon-o-user-group', 'w-5 h-5')
            </div>
            <div>
                <h3 class="text-lg font-semibold text-[color:var(--nx-text)]">Teilnehmer verwalten</h3>
                <p class="text-sm text-[color:var(--nx-muted)]">Teilnehmer hinzufügen oder entfernen</p>
            </div>
        </div>
    </x-slot>

    @if($meeting)
        <div class="space-y-6">
            {{-- Aktuelle Teilnehmer --}}
            <div>
                <h4 class="text-md font-medium mb-3 text-[color:var(--nx-text)]">Aktuelle Teilnehmer</h4>
                <div class="space-y-2">
                    @foreach($meeting->participants as $participant)
                        <div class="flex items-center justify-between p-3 rounded-lg border border-[color:var(--nx-line)] bg-[color:var(--nx-accent-soft)]">
                            <div class="flex items-center space-x-3">
                                @if($participant->user)
                                    @if($participant->user->avatar ?? null)
                                        <img src="{{ $participant->user->avatar }}" alt="{{ $participant->user->name }}" class="w-6 h-6 rounded-full object-cover">
                                    @else
                                        <div class="w-6 h-6 bg-[color:var(--nx-accent)] text-[color:var(--nx-on-accent)] rounded-full flex items-center justify-center text-[11px] font-medium">
                                            {{ substr($participant->user->name ?? '?', 0, 1) }}
                                        </div>
                                    @endif
                                @else
                                    <div class="w-6 h-6 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-300 rounded-full flex items-center justify-center text-[11px] font-medium">
                                        {{ substr($participant->name ?? $participant->email ?? '?', 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="font-medium text-sm text-[color:var(--nx-text)]">
                                        {{ $participant->display_name }}
                                    </div>
                                    <div class="text-xs text-[color:var(--nx-muted)]">
                                        {{ $participant->user->email ?? $participant->email }}
                                    </div>
                                </div>
                                @if($participant->role === 'organizer')
                                    <x-nx-badge variant="accent">Organisator</x-nx-badge>
                                @endif
                                @if($participant->isExternal())
                                    <x-nx-badge variant="info">Extern</x-nx-badge>
                                @endif
                            </div>

                            <div class="flex items-center space-x-2">
                                @if($participant->user_id != $meeting->user_id)
                                    <x-nx-button
                                        variant="ghost"
                                        icon
                                        wire:click="removeParticipant({{ $participant->user_id }})"
                                        class="text-[color:var(--nx-danger)] hover:text-[color:var(--nx-danger)]"
                                        title="Entfernen"
                                    >
                                        @svg('heroicon-o-trash', 'w-4 h-4')
                                    </x-nx-button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Neuen Teilnehmer hinzufügen --}}
            <div class="border-t border-[color:var(--nx-line)] pt-4">
                <h4 class="text-md font-medium mb-3 text-[color:var(--nx-text)]">Teilnehmer hinzufügen</h4>

                @php
                    $availableUsers = $this->getAvailableUsers();
                @endphp

                @if($availableUsers->count() > 0)
                    <div class="space-y-2">
                        @foreach($availableUsers as $user)
                            <div class="flex items-center justify-between p-3 rounded-lg border border-[color:var(--nx-line)] bg-[color:var(--nx-surface)]">
                                <div class="flex items-center space-x-3">
                                    @if($user->avatar ?? null)
                                        <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="w-6 h-6 rounded-full object-cover">
                                    @else
                                        <div class="w-6 h-6 bg-[color:var(--nx-accent)] text-[color:var(--nx-on-accent)] rounded-full flex items-center justify-center text-[11px] font-medium">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-medium text-sm text-[color:var(--nx-text)]">{{ $user->name }}</div>
                                        <div class="text-xs text-[color:var(--nx-muted)]">{{ $user->email }}</div>
                                    </div>
                                </div>
                                <x-nx-button variant="secondary" size="sm" wire:click="addParticipant({{ $user->id }})">
                                    Hinzufügen
                                </x-nx-button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-[color:var(--nx-muted)]">Alle Team-Mitglieder sind bereits Teilnehmer des Meetings.</p>
                @endif
            </div>
        </div>
    @endif

    <x-slot name="footer">
        <x-nx-button variant="secondary" size="sm" wire:click="closeModal">
            Schließen
        </x-nx-button>
    </x-slot>
</x-nx-modal>
