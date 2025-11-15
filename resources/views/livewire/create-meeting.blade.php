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
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <x-ui-input-datetime
                                name="start_date"
                                label="Start"
                                :value="$start_date"
                                wire:model="start_date"
                                required
                                :errorKey="'start_date'"
                            />

                            <x-ui-input-datetime
                                name="end_date"
                                label="Ende"
                                :value="$end_date"
                                wire:model="end_date"
                                required
                                :errorKey="'end_date'"
                            />
                        </div>

                        {{-- Ort --}}
                        <x-ui-input-text
                            name="location"
                            label="Ort"
                            wire:model="location"
                            placeholder="z.B. Konferenzraum A oder Teams-Link"
                            :errorKey="'location'"
                        />

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
