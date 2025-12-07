<x-ui-page>
    <x-slot name="navbar">
        <x-ui-page-navbar title="Meetings" icon="heroicon-o-calendar" />
    </x-slot>

    <div class="lg:flex lg:h-full lg:flex-col">
        <header class="flex items-center justify-between border-b border-[var(--ui-border)] px-6 py-4 lg:flex-none dark:border-white/10 dark:bg-gray-800/50">
            <h1 class="text-base font-semibold text-[var(--ui-secondary)]">
                {{ $monthLabel }}
            </h1>
            <div class="flex items-center gap-3">
                <div class="relative flex items-center rounded-md bg-white shadow-xs outline -outline-offset-1 outline-[var(--ui-border)] md:items-stretch dark:bg-white/10 dark:shadow-none dark:outline-white/5">
                    <button type="button" wire:click="previousMonth" class="flex h-9 w-10 items-center justify-center rounded-l-md pr-1 text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] focus:relative md:w-9 md:pr-0 md:hover:bg-[var(--ui-muted-5)] dark:hover:text-white dark:md:hover:bg-white/10" aria-label="Vorheriger Monat">
                        @svg('heroicon-o-chevron-left','w-4 h-4')
                    </button>
                    <button type="button" wire:click="goToToday" class="hidden px-3.5 text-sm font-semibold text-[var(--ui-secondary)] hover:bg-[var(--ui-muted-5)] focus:relative md:block dark:text-white dark:hover:bg-white/10">Heute</button>
                    <span class="relative -mx-px h-5 w-px bg-[var(--ui-border)] md:hidden dark:bg-white/10"></span>
                    <button type="button" wire:click="nextMonth" class="flex h-9 w-10 items-center justify-center rounded-r-md pl-1 text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] focus:relative md:w-9 md:pl-0 md:hover:bg-[var(--ui-muted-5)] dark:hover:text-white dark:md:hover:bg-white/10" aria-label="Nächster Monat">
                        @svg('heroicon-o-chevron-right','w-4 h-4')
                    </button>
                </div>
                <div class="hidden md:flex md:items-center md:gap-4">
                    <span class="text-sm text-[var(--ui-muted)]">Monatsansicht</span>
                    <div class="h-6 w-px bg-[var(--ui-border)] dark:bg-white/10"></div>
                    <a href="{{ route('meetings.create') }}" class="rounded-md bg-[var(--ui-primary)] px-3 py-2 text-sm font-semibold text-[var(--ui-on-primary)] shadow-xs hover:bg-[var(--ui-primary-80)] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[var(--ui-primary)]">
                        Meeting anlegen
                    </a>
                </div>
                <div class="md:hidden">
                    <button class="-mx-2 flex items-center rounded-full border border-transparent p-2 text-[var(--ui-muted)] hover:text-[var(--ui-secondary)] dark:text-white">
                        @svg('heroicon-o-ellipsis-vertical','w-5 h-5')
                    </button>
                </div>
            </div>
        </header>

        <div class="shadow-sm ring-1 ring-black/5 lg:flex lg:flex-auto lg:flex-col dark:shadow-none dark:ring-white/5">
            <div class="grid grid-cols-7 gap-px border-b border-[var(--ui-border)] bg-[var(--ui-muted-5)] text-center text-xs font-semibold text-[var(--ui-muted)] lg:flex-none dark:border-white/5 dark:bg-white/15 dark:text-gray-300">
                @foreach(['Mo','Di','Mi','Do','Fr','Sa','So'] as $weekday)
                    <div class="flex justify-center bg-white py-2 dark:bg-gray-900">
                        <span>{{ $weekday }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Desktop Grid --}}
            <div class="hidden bg-[var(--ui-muted-5)] text-xs text-[var(--ui-secondary)] lg:grid lg:grid-cols-7 lg:grid-rows-6 lg:gap-px">
                @foreach($days as $day)
                    <div class="group relative bg-white px-3 py-2 text-[var(--ui-muted)] {{ $day['is_current_month'] ? 'data-is-current-month' : '' }} dark:bg-gray-900 dark:text-gray-400">
                        <time class="relative {{ !$day['is_current_month'] ? 'opacity-70' : '' }} {{ $day['is_today'] ? 'flex size-7 items-center justify-center rounded-full bg-[var(--ui-primary)] font-semibold text-[var(--ui-on-primary)]' : '' }}">
                            {{ $day['label'] }}
                        </time>
                        @if(count($day['events']) > 0)
                            <ol class="mt-2 space-y-1">
                                @foreach($day['events']->take(3) as $event)
                                    <li>
                                        <a href="{{ route('meetings.show', $event['meeting_id']) }}" class="group flex items-center gap-2 truncate hover:text-[var(--ui-primary)]">
                                            <p class="flex-auto truncate font-medium text-[var(--ui-secondary)] group-hover:text-[var(--ui-primary)]">{{ $event['title'] }}</p>
                                            <time class="hidden flex-none text-[var(--ui-muted)] group-hover:text-[var(--ui-primary)] xl:block">{{ $event['time'] }}</time>
                                        </a>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- Mobile list --}}
            <div class="relative px-4 py-6 sm:px-6 lg:hidden dark:after:pointer-events-none dark:after:absolute dark:after:inset-x-0 dark:after:top-0 dark:after:h-px dark:after:bg-white/10">
                <ol class="divide-y divide-[var(--ui-border)] overflow-hidden rounded-lg bg-white text-sm shadow-sm outline-1 outline-black/5 dark:divide-white/10 dark:bg-gray-800/50 dark:shadow-none dark:-outline-offset-1 dark:outline-white/10">
                    @forelse($eventList as $event)
                        <li class="group flex p-4 pr-6 focus-within:bg-[var(--ui-muted-5)] hover:bg-[var(--ui-muted-5)] dark:focus-within:bg-white/5 dark:hover:bg-white/5">
                            <div class="flex-auto min-w-0">
                                <p class="font-semibold text-[var(--ui-secondary)] truncate">{{ $event['title'] }}</p>
                                <div class="mt-1 flex items-center text-[var(--ui-muted)] gap-2">
                                    @svg('heroicon-o-clock','w-4 h-4 text-[var(--ui-muted)]')
                                    <span>{{ $event['date'] }} • {{ $event['time'] }} - {{ $event['end'] }}</span>
                                </div>
                                @if($event['location'])
                                    <div class="mt-1 text-xs text-[var(--ui-muted)] truncate">{{ $event['location'] }}</div>
                                @endif
                            </div>
                            <a href="{{ route('meetings.show', $event['meeting_id']) }}" class="ml-4 flex-none self-center rounded-md bg-white px-3 py-2 text-xs font-semibold text-[var(--ui-secondary)] opacity-0 shadow-xs ring-1 ring-[var(--ui-border)] ring-inset group-hover:opacity-100 hover:ring-[var(--ui-primary)] focus:opacity-100 dark:bg-white/10 dark:text-white dark:shadow-none dark:ring-white/5 dark:hover:bg-white/20 dark:hover:ring-white/5">Öffnen</a>
                        </li>
                    @empty
                        <li class="p-4 text-sm text-[var(--ui-muted)]">Keine Termine im ausgewählten Zeitraum.</li>
                    @endforelse
                </ol>
            </div>
        </div>
    </div>
</x-ui-page>

