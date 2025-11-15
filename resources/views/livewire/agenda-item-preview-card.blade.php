@props(['agendaItem'])

<x-ui-kanban-card 
    :title="$agendaItem->title" 
    :sortable-id="$agendaItem->id" 
    :href="route('meetings.agenda-items.show', $agendaItem)"
>
    <!-- Meta: Appointment • Verantwortlicher • Dauer -->
    <div class="flex items-center justify-between mb-2 gap-2">
        <div class="flex items-center gap-2 text-xs text-[var(--ui-secondary)] min-w-0">
            @if($agendaItem->appointment)
                <span class="inline-flex items-center gap-1 min-w-0">
                    @svg('heroicon-o-calendar-days','w-3.5 h-3.5')
                    <span class="truncate max-w-[9rem] font-medium">{{ $agendaItem->appointment->meeting->title }}</span>
                </span>
            @endif

            @php
                $assignedTo = $agendaItem->assignedTo ?? null;
                $initials = $assignedTo ? mb_strtoupper(mb_substr($assignedTo->name ?? $assignedTo->email ?? 'U', 0, 1)) : null;
            @endphp
            @if($assignedTo)
                <span class="text-[var(--ui-muted)]">•</span>
                <span class="inline-flex items-center gap-1 min-w-0">
                    @if($assignedTo->avatar)
                        <img src="{{ $assignedTo->avatar }}" alt="{{ $assignedTo->name ?? $assignedTo->email }}" class="w-4 h-4 rounded-full object-cover">
                    @else
                        <span class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-[var(--ui-muted-5)] border border-[var(--ui-border)]/60 text-[10px] text-[var(--ui-secondary)]">{{ $initials }}</span>
                    @endif
                    <span class="truncate max-w-[7rem]">{{ $assignedTo->name ?? $assignedTo->email }}</span>
                </span>
            @endif
        </div>

        <div class="flex items-center gap-1 flex-shrink-0">
            @if($agendaItem->duration_minutes)
                <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] bg-[var(--ui-primary-5)] text-[color:var(--ui-primary)]">
                    @svg('heroicon-o-clock','w-3 h-3')
                    {{ $agendaItem->duration_minutes }} Min.
                </span>
            @endif
        </div>
    </div>

    <!-- Description (truncated) -->
    @if($agendaItem->description)
        <div class="text-xs text-[var(--ui-muted)] mb-2 line-clamp-2">
            {{ Str::limit($agendaItem->description, 80) }}
        </div>
    @endif

    <!-- Status -->
    <div class="text-xs text-[var(--ui-muted)] flex items-center gap-2">
        @php
            $statusColors = [
                'todo' => 'muted',
                'in_progress' => 'warning',
                'done' => 'success',
            ];
            $statusColor = $statusColors[$agendaItem->status] ?? 'muted';
        @endphp
        <x-ui-badge :variant="$statusColor" size="xs">
            {{ ucfirst(str_replace('_', ' ', $agendaItem->status)) }}
        </x-ui-badge>
    </div>
</x-ui-kanban-card>

