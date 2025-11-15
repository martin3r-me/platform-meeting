<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Meeting erstellen" icon="heroicon-o-plus" />
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <form wire:submit.prevent="save" onsubmit="return false;">
                    <x-ui-form-grid :cols="1" :gap="6">
                        {{-- Titel --}}
                        <x-ui-input-text
                            name="title"
                            label="Titel"
                            wire:model="title"
                            placeholder="Meeting-Titel eingeben..."
                            required
                            :errorKey="'title'"
                        />

                        {{-- Beschreibung --}}
                        <x-ui-input-textarea
                            name="description"
                            label="Beschreibung"
                            wire:model="description"
                            placeholder="Beschreibung des Meetings..."
                            :errorKey="'description'"
                        />

                        {{-- Datum, Zeit & Dauer --}}
                        <div>
                            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">
                                Startzeit & Dauer
                            </label>
                            
                            <div class="space-y-4">
                                {{-- Startdatum/Zeit --}}
                                <div>
                                    <x-ui-input-datetime
                                        name="start_date"
                                        label="Startdatum & Zeit"
                                        :value="$start_date"
                                        required
                                        :errorKey="'start_date'"
                                    />
                                    {{-- Verstecktes Input für Livewire-Bindung --}}
                                    <input type="hidden" wire:model="start_date" id="start_date_wire" />
                                </div>
                                
                                {{-- Dauer-Auswahl --}}
                                <div>
                                    <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-3">
                                        Dauer
                                    </label>
                                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                        @php
                                            $durationOptions = [
                                                15 => '15 Min',
                                                30 => '30 Min',
                                                45 => '45 Min',
                                                60 => '1 Std',
                                                90 => '1,5 Std',
                                                180 => '3 Std',
                                                240 => '4 Std',
                                            ];
                                        @endphp
                                        
                                        @foreach($durationOptions as $minutes => $label)
                                            @php
                                                $isLong = $minutes >= 90;
                                                $isSelected = $duration_minutes == $minutes;
                                            @endphp
                                            <button
                                                type="button"
                                                wire:click="$set('duration_minutes', {{ $minutes }})"
                                                wire:loading.attr="disabled"
                                                class="px-4 py-2.5 rounded-lg border-2 font-medium text-sm transition-all duration-200 hover:scale-105 {{ $isSelected ? 'bg-[var(--ui-primary)] text-[var(--ui-on-primary)] border-[var(--ui-primary)] shadow-md' : 'bg-white text-[var(--ui-secondary)] border-[var(--ui-border)]/60 hover:border-[var(--ui-primary)]/60 hover:bg-[var(--ui-primary-5)]' }}"
                                            >
                                                {{ $label }}
                                            </button>
                                        @endforeach
                                    </div>
                                    
                                    @if($duration_minutes >= 90)
                                        <div class="mt-3 p-3 bg-[var(--ui-warning-5)] border border-[var(--ui-warning)]/30 rounded-lg">
                                            <div class="flex items-start gap-2">
                                                @svg('heroicon-o-information-circle', 'w-5 h-5 text-[var(--ui-warning)] flex-shrink-0 mt-0.5')
                                                <div class="text-sm text-[var(--ui-secondary)]">
                                                    <p class="font-medium mb-1">Lange Dauer gewählt</p>
                                                    <p class="text-[var(--ui-muted)]">
                                                        {{ $duration_minutes >= 180 ? 'Mehr als 3 Stunden' : 'Mehr als 1,5 Stunden' }} - 
                                                        Bist du sicher, dass das Meeting wirklich so lange dauern muss? 
                                                        Vielleicht lässt sich die Agenda straffen oder das Meeting aufteilen?
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                
                                {{-- Endzeit Anzeige (nur Info) --}}
                                @if($start_date && $end_date)
                                    <div class="p-3 bg-[var(--ui-muted-5)] rounded-lg border border-[var(--ui-border)]/60">
                                        <div class="flex items-center gap-2 text-sm">
                                            @svg('heroicon-o-clock', 'w-4 h-4 text-[var(--ui-primary)]')
                                            <span class="text-[var(--ui-secondary)]">
                                                Endet um: 
                                                <span class="font-semibold">
                                                    {{ \Carbon\Carbon::parse($end_date)->format('d.m.Y H:i') }}
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                        
                        @push('scripts')
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                let startDateValue = null;
                                
                                function syncStartDate() {
                                    const startInput = document.getElementById('start_date');
                                    const wireInput = document.getElementById('start_date_wire');
                                    
                                    if (!startInput || !wireInput) return;
                                    
                                    if (startInput.value && startInput.value !== startDateValue) {
                                        startDateValue = startInput.value;
                                        
                                        // Setze beide Inputs
                                        wireInput.value = startInput.value;
                                        
                                        // Trigger Livewire Update
                                        wireInput.dispatchEvent(new Event('input', { bubbles: true }));
                                        wireInput.dispatchEvent(new Event('change', { bubbles: true }));
                                        
                                        // Warte kurz, damit end_date berechnet werden kann
                                        setTimeout(() => {
                                            @this.call('loadRooms');
                                        }, 200);
                                    }
                                }
                                
                                function setupStartDateBinding() {
                                    const startInput = document.getElementById('start_date');
                                    if (!startInput) return;
                                    
                                    // Event-Listener für input und change
                                    startInput.addEventListener('input', syncStartDate);
                                    startInput.addEventListener('change', syncStartDate);
                                    
                                    // MutationObserver für value-Änderungen
                                    const observer = new MutationObserver(function(mutations) {
                                        mutations.forEach(function(mutation) {
                                            if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                                                syncStartDate();
                                            }
                                        });
                                    });
                                    
                                    observer.observe(startInput, {
                                        attributes: true,
                                        attributeFilter: ['value']
                                    });
                                }
                                
                                // Initial setup
                                setTimeout(() => {
                                    setupStartDateBinding();
                                    
                                    // Prüfe ob Startdatum bereits gesetzt ist
                                    syncStartDate();
                                }, 300);
                                
                                // Nach Livewire-Updates
                                document.addEventListener('livewire:init', () => {
                                    Livewire.hook('morph.updated', () => {
                                        setTimeout(() => {
                                            setupStartDateBinding();
                                            syncStartDate();
                                        }, 200);
                                    });
                                });
                            });
                        </script>
                        @endpush

                        {{-- Ort / Raum --}}
                        <div>
                            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">
                                Ort / Raum
                            </label>
                            <div class="space-y-3">
                                {{-- Auswahl: Manuell oder Raum --}}
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input 
                                            type="radio" 
                                            wire:model.live="location_type" 
                                            value="manual"
                                            class="text-[var(--ui-primary)] focus:ring-[var(--ui-primary)]"
                                        />
                                        <span class="text-sm text-[var(--ui-secondary)]">Manuell eingeben</span>
                                    </label>
                                    <label class="flex items-center gap-2 cursor-pointer">
                                        <input 
                                            type="radio" 
                                            wire:model.live="location_type" 
                                            value="room"
                                            class="text-[var(--ui-primary)] focus:ring-[var(--ui-primary)]"
                                        />
                                        <span class="text-sm text-[var(--ui-secondary)]">Raum auswählen</span>
                                    </label>
                                </div>

                                {{-- Manuelle Eingabe --}}
                                @if($location_type === 'manual')
                                    <x-ui-input-text
                                        name="location"
                                        label=""
                                        wire:model="location"
                                        placeholder="z.B. Konferenzraum A oder Teams-Link"
                                        :errorKey="'location'"
                                    />
                                @endif

                                {{-- Raum-Auswahl --}}
                                @if($location_type === 'room')
                                    <div>
                                        @if(empty($rooms) && $start_date)
                                            @if(!$end_date)
                                                <div class="text-sm text-[var(--ui-muted)] p-3 bg-[var(--ui-muted-5)] rounded-md">
                                                    Bitte zuerst Startzeit und Dauer auswählen, um verfügbare Räume zu sehen.
                                                </div>
                                            @else
                                                <div class="text-sm text-[var(--ui-muted)] p-3 bg-[var(--ui-muted-5)] rounded-md">
                                                    Lade Räume...
                                                </div>
                                            @endif
                                        @elseif(empty($rooms) && $start_date && $end_date)
                                            <div class="text-sm text-[var(--ui-warning)] p-3 bg-[var(--ui-warning-5)] rounded-md border border-[var(--ui-warning)]/30">
                                                Keine Räume gefunden oder Fehler beim Laden. Bitte versuche es erneut oder wähle einen Ort manuell.
                                            </div>
                                        @elseif(!empty($rooms))
                                            <div class="space-y-2 max-h-64 overflow-y-auto border border-[var(--ui-border)]/60 rounded-md p-3">
                                                @foreach($rooms as $room)
                                                    <label class="flex items-center gap-3 p-2 rounded-md hover:bg-[var(--ui-muted-5)] cursor-pointer {{ $selected_room_id === $room['email'] ? 'bg-[var(--ui-primary-5)] border border-[var(--ui-primary)]' : '' }}">
                                                        <input 
                                                            type="radio" 
                                                            wire:model.live="selected_room_id" 
                                                            value="{{ $room['email'] }}"
                                                            class="text-[var(--ui-primary)] focus:ring-[var(--ui-primary)]"
                                                        />
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-2">
                                                                <div class="text-sm font-medium text-[var(--ui-secondary)]">
                                                                    {{ $room['name'] }}
                                                                </div>
                                                                @if(isset($room['available']) && !$room['available'])
                                                                    <x-ui-badge variant="danger" size="xs">Belegt</x-ui-badge>
                                                                @elseif(isset($room['available']) && $room['available'])
                                                                    <x-ui-badge variant="success" size="xs">Verfügbar</x-ui-badge>
                                                                @endif
                                                            </div>
                                                            @if($room['capacity'])
                                                                <div class="text-xs text-[var(--ui-muted)]">
                                                                    Kapazität: {{ $room['capacity'] }} Personen
                                                                </div>
                                                            @endif
                                                            @if($room['address'])
                                                                <div class="text-xs text-[var(--ui-muted)]">
                                                                    {{ $room['address'] }}
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </label>
                                                @endforeach
                                            </div>
                                        @endif
                                        @error('selected_room_id')
                                            <p class="mt-1 text-sm text-[var(--ui-danger)]">{{ $message }}</p>
                                        @enderror
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Teilnehmer --}}
                        <div>
                            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">
                                Teilnehmer
                            </label>
                            <div class="space-y-2 max-h-64 overflow-y-auto border border-[var(--ui-border)]/60 rounded-md p-3">
                                @forelse($teamMembers as $member)
                                    <label class="flex items-center gap-3 p-2 rounded-md hover:bg-[var(--ui-muted-5)] cursor-pointer">
                                        <input 
                                            type="checkbox" 
                                            wire:model="participant_ids" 
                                            value="{{ $member['id'] }}"
                                            class="rounded border-[var(--ui-border)] text-[var(--ui-primary)] focus:ring-[var(--ui-primary)]"
                                        />
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-[var(--ui-secondary)]">
                                                {{ $member['name'] }}
                                            </div>
                                            <div class="text-xs text-[var(--ui-muted)]">
                                                {{ $member['email'] }}
                                            </div>
                                        </div>
                                    </label>
                                @empty
                                    <p class="text-sm text-[var(--ui-muted)]">Keine Team-Mitglieder verfügbar</p>
                                @endforelse
                            </div>
                            @error('participant_ids')
                                <p class="mt-1 text-sm text-[var(--ui-danger)]">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Sync Option --}}
                        <div>
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    wire:model="sync_to_calendar"
                                    class="rounded border-[var(--ui-border)] text-[var(--ui-primary)] focus:ring-[var(--ui-primary)]"
                                />
                                <div>
                                    <div class="text-sm font-medium text-[var(--ui-secondary)]">
                                        Zu Microsoft Calendar synchronisieren
                                    </div>
                                    <div class="text-xs text-[var(--ui-muted)]">
                                        Das Meeting wird automatisch in die Kalender aller Teilnehmer eingetragen
                                    </div>
                                </div>
                            </label>
                        </div>

                        {{-- Buttons --}}
                        <div class="flex items-center gap-3 pt-4 border-t border-[var(--ui-border)]/60">
                            <x-ui-button type="button" wire:click="save" variant="primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="save">Meeting erstellen</span>
                                <span wire:loading wire:target="save">Wird erstellt...</span>
                            </x-ui-button>
                            <x-ui-button type="button" variant="secondary" :href="route('meetings.dashboard')" wire:navigate>
                                Abbrechen
                            </x-ui-button>
                        </div>
                        
                        {{-- Verstecktes end_date Input für Validierung --}}
                        <input type="hidden" wire:model="end_date" />
                    </x-ui-form-grid>
                </form>
            </div>
        </div>
    </x-ui-page-container>
</x-ui-page>
