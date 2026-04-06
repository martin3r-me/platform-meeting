<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Meeting-Serie erstellen" icon="heroicon-o-arrow-path" />
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
                            placeholder="z.B. Monatliches Board Meeting"
                            required
                            :errorKey="'title'"
                        />

                        {{-- Beschreibung --}}
                        <x-ui-input-textarea
                            name="description"
                            label="Beschreibung"
                            wire:model="description"
                            placeholder="Beschreibung der Meeting-Serie..."
                            :errorKey="'description'"
                        />

                        {{-- Ort --}}
                        <x-ui-input-text
                            name="location"
                            label="Ort"
                            wire:model="location"
                            placeholder="z.B. Konferenzraum A"
                            :errorKey="'location'"
                        />

                        {{-- Zeiten --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">
                                    Startzeit <span class="text-[var(--ui-danger)]">*</span>
                                </label>
                                <input
                                    type="time"
                                    wire:model="start_time"
                                    class="w-full rounded-md border border-[var(--ui-border)]/60 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)]"
                                    required
                                />
                                @error('start_time')
                                    <p class="mt-1 text-sm text-[var(--ui-danger)]">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">
                                    Endzeit <span class="text-[var(--ui-danger)]">*</span>
                                </label>
                                <input
                                    type="time"
                                    wire:model="end_time"
                                    class="w-full rounded-md border border-[var(--ui-border)]/60 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)]"
                                    required
                                />
                                @error('end_time')
                                    <p class="mt-1 text-sm text-[var(--ui-danger)]">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        {{-- Wiederholung --}}
                        <div>
                            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">
                                Wiederholung <span class="text-[var(--ui-danger)]">*</span>
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2">
                                @php
                                    $recurrenceOptions = [
                                        'weekly' => 'Wöchentlich',
                                        'biweekly' => 'Alle 2 Wochen',
                                        'monthly' => 'Monatlich',
                                        'quarterly' => 'Vierteljährlich',
                                        'yearly' => 'Jährlich',
                                    ];
                                @endphp

                                @foreach($recurrenceOptions as $value => $label)
                                    @php $isSelected = $recurrence_type === $value; @endphp
                                    <button
                                        type="button"
                                        wire:click="$set('recurrence_type', '{{ $value }}')"
                                        class="px-3 py-2 rounded-lg border-2 font-medium text-sm transition-all {{ $isSelected ? 'bg-[var(--ui-primary)] text-[var(--ui-on-primary)] border-[var(--ui-primary)]' : 'bg-white text-[var(--ui-secondary)] border-[var(--ui-border)]/60 hover:border-[var(--ui-primary)]/60' }}"
                                    >
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                            @error('recurrence_type')
                                <p class="mt-1 text-sm text-[var(--ui-danger)]">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Erstes Meeting --}}
                        <div>
                            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">
                                Erstes Meeting am <span class="text-[var(--ui-danger)]">*</span>
                            </label>
                            <input
                                type="date"
                                wire:model="next_meeting_date"
                                class="w-full rounded-md border border-[var(--ui-border)]/60 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)]"
                                required
                            />
                            @error('next_meeting_date')
                                <p class="mt-1 text-sm text-[var(--ui-danger)]">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Ende der Serie --}}
                        <div>
                            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">
                                Serie endet am (optional)
                            </label>
                            <input
                                type="date"
                                wire:model="recurrence_end_date"
                                class="w-full rounded-md border border-[var(--ui-border)]/60 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)]"
                            />
                            @error('recurrence_end_date')
                                <p class="mt-1 text-sm text-[var(--ui-danger)]">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Buttons --}}
                        <div class="flex items-center gap-3 pt-4 border-t border-[var(--ui-border)]/60">
                            <x-ui-button type="button" wire:click="save" variant="primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="save">Serie erstellen</span>
                                <span wire:loading wire:target="save">Wird erstellt...</span>
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
