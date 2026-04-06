<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Meeting-Serie erstellen" icon="heroicon-o-arrow-path" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Meetings', 'href' => route('meetings.dashboard'), 'icon' => 'calendar-days'],
            ['label' => 'Serie erstellen'],
        ]" />
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

                        {{-- Wochentag (nur bei weekly/biweekly) --}}
                        @if(in_array($recurrence_type, ['weekly', 'biweekly']))
                            <div>
                                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-2">
                                    Wochentag
                                </label>
                                <div class="flex gap-2">
                                    @php
                                        $dayLabels = [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'];
                                    @endphp
                                    @foreach($dayLabels as $dayValue => $dayLabel)
                                        @php $isSelected = (int) $recurrence_day_of_week === $dayValue; @endphp
                                        <button
                                            type="button"
                                            wire:click="$set('recurrence_day_of_week', {{ $dayValue }})"
                                            class="w-10 h-10 rounded-lg border-2 font-medium text-sm transition-all {{ $isSelected ? 'bg-[var(--ui-primary)] text-[var(--ui-on-primary)] border-[var(--ui-primary)]' : 'bg-white text-[var(--ui-secondary)] border-[var(--ui-border)]/60 hover:border-[var(--ui-primary)]/60' }}"
                                        >
                                            {{ $dayLabel }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Tag im Monat (nur bei monthly/quarterly/yearly) --}}
                        @if(in_array($recurrence_type, ['monthly', 'quarterly', 'yearly']))
                            <div>
                                <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">
                                    Tag im Monat
                                </label>
                                <select
                                    wire:model="recurrence_day_of_month"
                                    class="w-full max-w-[12rem] rounded-md border border-[var(--ui-border)]/60 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)]"
                                >
                                    @for($d = 1; $d <= 28; $d++)
                                        <option value="{{ $d }}">{{ $d }}.</option>
                                    @endfor
                                </select>
                            </div>
                        @endif

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

                        {{-- Ort --}}
                        <x-ui-input-text
                            name="location"
                            label="Ort"
                            wire:model="location"
                            placeholder="z.B. Konferenzraum A"
                            :errorKey="'location'"
                        />

                        {{-- Beginnt ab --}}
                        <div>
                            <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">
                                Beginnt ab <span class="text-[var(--ui-danger)]">*</span>
                            </label>
                            <input
                                type="date"
                                wire:model="next_meeting_date"
                                class="w-full max-w-[16rem] rounded-md border border-[var(--ui-border)]/60 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)]"
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
                                class="w-full max-w-[16rem] rounded-md border border-[var(--ui-border)]/60 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)]"
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
