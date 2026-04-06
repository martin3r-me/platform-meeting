<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Meeting erstellen" icon="heroicon-o-plus" />
    </x-slot>

    <x-slot name="actionbar">
        <x-ui-page-actionbar :breadcrumbs="[
            ['label' => 'Meetings', 'href' => route('meetings.dashboard'), 'icon' => 'calendar-days'],
            ['label' => 'Meeting erstellen'],
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
                                <div>
                                    <label class="block text-sm font-medium text-[var(--ui-secondary)] mb-1">
                                        Startdatum & Zeit <span class="text-[var(--ui-danger)]">*</span>
                                    </label>
                                    <input
                                        type="datetime-local"
                                        wire:model.live="start_date"
                                        class="w-full rounded-md border border-[var(--ui-border)]/60 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-[var(--ui-primary)]/20 focus:border-[var(--ui-primary)]"
                                        required
                                    />
                                    @error('start_date')
                                        <p class="mt-1 text-sm text-[var(--ui-danger)]">{{ $message }}</p>
                                    @enderror
                                </div>

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
                                            @php $isSelected = $duration_minutes == $minutes; @endphp
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
                                </div>

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

                        {{-- Ort --}}
                        <x-ui-input-text
                            name="location"
                            label="Ort"
                            wire:model="location"
                            placeholder="z.B. Konferenzraum A, Zoom-Link, etc."
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

                        <input type="hidden" wire:model="end_date" />
                    </x-ui-form-grid>
                </form>
            </div>
        </div>
    </x-ui-page-container>
</x-ui-page>
