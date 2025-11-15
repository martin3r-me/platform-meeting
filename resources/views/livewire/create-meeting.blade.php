<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Meeting erstellen" icon="heroicon-o-plus" />
    </x-slot>

    <x-ui-page-container>
        <div class="p-6 max-w-2xl">
        <form wire:submit="save">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Titel *</label>
                    <input type="text" wire:model="title" class="w-full" required />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Beschreibung</label>
                    <textarea wire:model="description" class="w-full" rows="4"></textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Start *</label>
                        <input type="datetime-local" wire:model="start_date" class="w-full" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Ende *</label>
                        <input type="datetime-local" wire:model="end_date" class="w-full" required />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Ort</label>
                    <input type="text" wire:model="location" class="w-full" />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1">Teilnehmer</label>
                    <select wire:model="participant_ids" multiple class="w-full" size="5">
                        @foreach($teamMembers as $member)
                            <option value="{{ $member->id }}">{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="flex items-center gap-2">
                        <input type="checkbox" wire:model="sync_to_calendar" />
                        <span class="text-sm">Zu Microsoft Calendar synchronisieren</span>
                    </label>
                </div>

                <div class="flex gap-2">
                    <x-ui-button type="submit" variant="primary">Erstellen</x-ui-button>
                    <x-ui-button type="button" variant="secondary" wire:click="$dispatch('close-modal')">Abbrechen</x-ui-button>
                </div>
            </div>
        </form>
    </x-ui-page-container>
</x-ui-page>

