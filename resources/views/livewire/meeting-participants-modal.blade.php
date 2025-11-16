<x-ui-modal wire:model="modalShow" title="Teilnehmer verwalten">
    @if($meeting)
        <div class="space-y-6">
            {{-- Aktuelle Teilnehmer --}}
            <div>
                <h4 class="text-md font-medium mb-3">Aktuelle Teilnehmer</h4>
                <div class="space-y-2">
                    @foreach($meeting->participants as $participant)
                        <div class="flex items-center justify-between p-3 rounded-lg border border-[var(--ui-border)]/60 bg-[var(--ui-muted-5)]">
                            <div class="flex items-center space-x-3">
                                @if($participant->user->avatar ?? null)
                                    <img src="{{ $participant->user->avatar }}" alt="{{ $participant->user->name }}" class="w-8 h-8 rounded-full object-cover">
                                @else
                                    <div class="w-8 h-8 bg-[var(--ui-primary-5)] text-[var(--ui-primary)] rounded-full flex items-center justify-center text-sm font-medium">
                                        {{ substr($participant->user->name ?? '?', 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <div class="font-medium text-sm text-[var(--ui-secondary)]">
                                        {{ $participant->user->fullname ?? $participant->user->name }}
                                    </div>
                                    <div class="text-xs text-[var(--ui-muted)]">
                                        {{ $participant->user->email }}
                                    </div>
                                </div>
                                @if($participant->role === 'organizer')
                                    <x-ui-badge variant="primary" size="xs">Organisator</x-ui-badge>
                                @endif
                            </div>
                            
                            <div class="flex items-center space-x-2">
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
                                
                                {{-- Teilnehmer entfernen --}}
                                @if($participant->user_id != $meeting->user_id)
                                    <button 
                                        wire:click="removeParticipant({{ $participant->user_id }})"
                                        class="text-red-500 hover:text-red-700 text-sm px-2 py-1 rounded hover:bg-red-50 transition-colors"
                                        title="Entfernen"
                                    >
                                        @svg('heroicon-o-trash', 'w-4 h-4')
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
            
            {{-- Neuen Teilnehmer hinzufügen --}}
            <div class="border-t pt-4">
                <h4 class="text-md font-medium mb-3">Teilnehmer hinzufügen</h4>
                
                @php
                    $availableUsers = $this->getAvailableUsers();
                @endphp
                
                @if($availableUsers->count() > 0)
                    <div class="space-y-2">
                        @foreach($availableUsers as $user)
                            <div class="flex items-center justify-between p-3 rounded-lg border border-[var(--ui-border)]/60 bg-[var(--ui-surface)]">
                                <div class="flex items-center space-x-3">
                                    @if($user->avatar ?? null)
                                        <img src="{{ $user->avatar }}" alt="{{ $user->name }}" class="w-8 h-8 rounded-full object-cover">
                                    @else
                                        <div class="w-8 h-8 bg-[var(--ui-primary-5)] text-[var(--ui-primary)] rounded-full flex items-center justify-center text-sm font-medium">
                                            {{ substr($user->name, 0, 1) }}
                                        </div>
                                    @endif
                                    <div>
                                        <div class="font-medium text-sm text-[var(--ui-secondary)]">{{ $user->name }}</div>
                                        <div class="text-xs text-[var(--ui-muted)]">{{ $user->email }}</div>
                                    </div>
                                </div>
                                <x-ui-button variant="secondary" size="sm" wire:click="addParticipant({{ $user->id }})">
                                    Hinzufügen
                                </x-ui-button>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-[var(--ui-muted)]">Alle Team-Mitglieder sind bereits Teilnehmer des Meetings.</p>
                @endif
            </div>
        </div>
    @endif
    
    <x-slot name="footer">
        <div class="flex items-center justify-end gap-2">
            <x-ui-button variant="secondary-outline" size="sm" wire:click="closeModal">
                Schließen
            </x-ui-button>
        </div>
    </x-slot>
</x-ui-modal>

