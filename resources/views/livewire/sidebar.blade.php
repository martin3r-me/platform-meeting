{{-- Meetings Sidebar --}}
<div>
    {{-- Modul Header --}}
    <div x-show="!collapsed" class="p-3 text-sm italic text-[var(--ui-secondary)] uppercase border-b border-[var(--ui-border)] mb-2">
        Meetings
    </div>

    {{-- Abschnitt: Allgemein --}}
    <x-ui-sidebar-list label="Allgemein">
        <x-ui-sidebar-item :href="route('meetings.dashboard')">
            @svg('heroicon-o-home', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Dashboard</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('meetings.create')">
            @svg('heroicon-o-plus-circle', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Meeting erstellen</span>
        </x-ui-sidebar-item>
        <x-ui-sidebar-item :href="route('meetings.series.create')">
            @svg('heroicon-o-arrow-path', 'w-4 h-4 text-[var(--ui-secondary)]')
            <span class="ml-2 text-sm">Serie erstellen</span>
        </x-ui-sidebar-item>
    </x-ui-sidebar-list>

    {{-- Collapsed: Icons-only --}}
    <div x-show="collapsed" class="px-2 py-2 border-b border-[var(--ui-border)]">
        <div class="flex flex-col gap-2">
            <a href="{{ route('meetings.dashboard') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-home', 'w-5 h-5')
            </a>
            <a href="{{ route('meetings.create') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-plus-circle', 'w-5 h-5')
            </a>
            <a href="{{ route('meetings.series.create') }}" wire:navigate class="flex items-center justify-center p-2 rounded-md text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)]">
                @svg('heroicon-o-arrow-path', 'w-5 h-5')
            </a>
        </div>
    </div>

    {{-- Abschnitt: Serien --}}
    @if($series->isNotEmpty())
        <div class="mt-2" x-show="!collapsed">
            <x-ui-sidebar-list label="Serien">
                @foreach($series as $s)
                    <x-ui-sidebar-item :href="route('meetings.series.show', ['meetingSeries' => $s])">
                        @svg('heroicon-o-arrow-path', 'w-5 h-5 flex-shrink-0 text-[var(--ui-secondary)]')
                        <div class="flex-1 min-w-0 ml-2">
                            <div class="truncate text-sm font-medium">{{ $s->title }}</div>
                            <div class="text-xs text-[var(--ui-muted)]">{{ $s->getRecurrencePatternText() }}</div>
                        </div>
                    </x-ui-sidebar-item>
                @endforeach
            </x-ui-sidebar-list>
        </div>
    @endif

    {{-- Abschnitt: Kommende Meetings --}}
    <div>
        <div class="mt-2" x-show="!collapsed">
            @if($meetings->isNotEmpty())
                <x-ui-sidebar-list label="Kommende Meetings">
                    @foreach($meetings as $meeting)
                        <x-ui-sidebar-item :href="route('meetings.show', ['meeting' => $meeting])">
                            @svg('heroicon-o-video-camera', 'w-5 h-5 flex-shrink-0 text-[var(--ui-secondary)]')
                            <div class="flex-1 min-w-0 ml-2">
                                <div class="truncate text-sm font-medium">{{ $meeting->title }}</div>
                                <div class="text-xs text-[var(--ui-muted)]">
                                    @if($meeting->start_date)
                                        {{ $meeting->start_date->format('d.m.Y H:i') }}
                                    @endif
                                </div>
                            </div>
                        </x-ui-sidebar-item>
                    @endforeach
                </x-ui-sidebar-list>
            @else
                <div class="px-3 py-1 text-xs text-[var(--ui-muted)]">
                    Keine kommenden Meetings
                </div>
            @endif
        </div>
    </div>
</div>
