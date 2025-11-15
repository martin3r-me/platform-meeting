<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Meeting erstellen" icon="heroicon-o-plus" />
    </x-slot>

    <x-ui-page-container spacing="space-y-6">
        <div class="bg-white rounded-xl border border-[var(--ui-border)]/60 shadow-sm overflow-hidden">
            <div class="p-6 lg:p-8">
                <form wire:submit="save">
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

                        {{-- Datum & Zeit --}}
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6" 
                             x-data="{
                                init() {
                                    // Warte bis das DOM vollständig geladen ist
                                    this.$nextTick(() => {
                                        this.setupInputListeners();
                                    });
                                    
                                    // Auch nach Livewire-Updates
                                    Livewire.hook('morph.updated', () => {
                                        setTimeout(() => this.setupInputListeners(), 50);
                                    });
                                },
                                setupInputListeners() {
                                    const startInput = document.getElementById('start_date');
                                    const endInput = document.getElementById('end_date');
                                    
                                    if (startInput && !startInput.hasAttribute('data-wire-bound')) {
                                        startInput.setAttribute('data-wire-bound', 'true');
                                        startInput.addEventListener('input', (e) => {
                                            @this.set('start_date', e.target.value);
                                        });
                                        startInput.addEventListener('change', (e) => {
                                            @this.set('start_date', e.target.value);
                                        });
                                    }
                                    
                                    if (endInput && !endInput.hasAttribute('data-wire-bound')) {
                                        endInput.setAttribute('data-wire-bound', 'true');
                                        endInput.addEventListener('input', (e) => {
                                            @this.set('end_date', e.target.value);
                                        });
                                        endInput.addEventListener('change', (e) => {
                                            @this.set('end_date', e.target.value);
                                        });
                                    }
                                }
                             }">
                            <x-ui-input-datetime
                                name="start_date"
                                label="Start"
                                :value="$start_date"
                                required
                                :errorKey="'start_date'"
                            />

                            <x-ui-input-datetime
                                name="end_date"
                                label="Ende"
                                :value="$end_date"
                                required
                                :errorKey="'end_date'"
                            />
                        </div>

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
                                        @if(empty($rooms) && $start_date && $end_date)
                                            <div class="text-sm text-[var(--ui-muted)] p-3 bg-[var(--ui-muted-5)] rounded-md">
                                                Lade Räume...
                                            </div>
                                        @elseif(empty($rooms))
                                            <div class="text-sm text-[var(--ui-muted)] p-3 bg-[var(--ui-muted-5)] rounded-md">
                                                Bitte zuerst Start- und Endzeit auswählen, um verfügbare Räume zu sehen.
                                            </div>
                                        @else
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
                            <x-ui-button type="submit" variant="primary">
                                Meeting erstellen
                            </x-ui-button>
                            <x-ui-button type="button" variant="secondary" :href="route('meetings.dashboard')" wire:navigate>
                                Abbrechen
                            </x-ui-button>
                        </div>
                    </x-ui-form-grid>
                </form>
            </div>
        </div>
    </x-ui-page-container>
</x-ui-page>
