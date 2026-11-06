@props([
    'chord',
    'selected' => false,
])

<div
    @click="selectChord({
        id: {{ $chord->id }},
        name: '{{ addslashes($chord->name) }}',
        pronunciation: '{{ addslashes($chord->pronunciation ?? '') }}',
        diagram_url: '{{ addslashes($chord->diagram_url) }}'
    })"
    :class="selectedChord && selectedChord.id === {{ $chord->id }} ? 'border-accent ring-1 ring-accent bg-surface-2/40 shadow-sm' : 'border-subtle hover:border-accent/40 hover:bg-surface-2/20'"
    class="group relative flex flex-col bg-surface-1 border rounded-lg p-10 sm:p-12 text-left transition-all duration-150 select-none cursor-pointer"
>
    <!-- Chord Name Header -->
    <div class="mb-8 flex items-baseline justify-between gap-4">
        <h3 class="text-base font-bold text-ink group-hover:text-accent transition-colors truncate font-mono">
            {{ $chord->name }}
        </h3>
        @if (!empty($chord->pronunciation))
            <span class="text-[11px] text-ink-secondary truncate max-w-[55%] font-sans" title="{{ $chord->pronunciation }}">
                {{ $chord->pronunciation }}
            </span>
        @endif
    </div>

    <!-- Direct Diagram Image (No Inner Outline/Box) -->
    <div class="flex items-center justify-center aspect-[4/5] overflow-hidden">
        <x-chord-diagram :chord="$chord" />
    </div>
</div>

