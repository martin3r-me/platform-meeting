<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Meetings" icon="heroicon-o-calendar" />
    </x-slot>

    <x-ui-page-container>
        <div class="p-6 space-y-6">
        {{-- Heute --}}
        @if($todayMeetings->count() > 0)
            <div>
                <h2 class="text-lg font-semibold mb-4">Heute</h2>
                <div class="space-y-2">
                    @foreach($todayMeetings as $meeting)
                        <a href="{{ route('meetings.show', $meeting) }}" class="block p-4 rounded-lg border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors">
                        @php
                            $nextAppointment = $meeting->appointments->first();
                        @endphp
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-medium truncate">{{ $meeting->title }}</h3>
                                @if($nextAppointment)
                                    <p class="text-sm text-[var(--ui-muted)]">
                                        {{ $nextAppointment->start_date->format('H:i') }} - {{ $nextAppointment->end_date->format('H:i') }}
                                    </p>
                                @endif
                            </div>
                            @if($meeting->location)
                                <span class="text-xs text-[var(--ui-muted)] truncate">{{ $meeting->location }}</span>
                            @endif
                        </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Kommende Meetings --}}
        <div>
            <h2 class="text-lg font-semibold mb-4">Kommende Meetings</h2>
            <div class="space-y-2">
                @forelse($upcomingMeetings as $meeting)
                    <a href="{{ route('meetings.show', $meeting) }}" class="block p-4 rounded-lg border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors">
                        @php
                            $nextAppointment = $meeting->appointments->first();
                        @endphp
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-medium truncate">{{ $meeting->title }}</h3>
                                @if($nextAppointment)
                                    <p class="text-sm text-[var(--ui-muted)]">
                                        {{ $nextAppointment->start_date->format('d.m.Y H:i') }} - {{ $nextAppointment->end_date->format('H:i') }}
                                    </p>
                                @endif
                            </div>
                            @if($meeting->location)
                                <span class="text-xs text-[var(--ui-muted)] truncate">{{ $meeting->location }}</span>
                            @endif
                        </div>
                    </a>
                @empty
                    <p class="text-[var(--ui-muted)]">Keine kommenden Meetings</p>
                @endforelse
            </div>
        </div>

        {{-- Meine Meetings --}}
        <div>
            <h2 class="text-lg font-semibold mb-4">Meine Meetings</h2>
            <div class="space-y-2">
                @forelse($myMeetings as $meeting)
                    <a href="{{ route('meetings.show', $meeting) }}" class="block p-4 rounded-lg border border-[var(--ui-border)] hover:bg-[var(--ui-muted-5)] transition-colors">
                        @php
                            $nextAppointment = $meeting->appointments->first();
                        @endphp
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <h3 class="font-medium truncate">{{ $meeting->title }}</h3>
                                @if($nextAppointment)
                                    <p class="text-sm text-[var(--ui-muted)]">
                                        {{ $nextAppointment->start_date->format('d.m.Y H:i') }} - {{ $nextAppointment->end_date->format('H:i') }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <p class="text-[var(--ui-muted)]">Keine Meetings</p>
                @endforelse
            </div>
        </div>
    </x-ui-page-container>
</x-ui-page>

