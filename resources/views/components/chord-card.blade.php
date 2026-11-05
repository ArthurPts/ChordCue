@props([
    'chord',
    'selected' => false,
])

<div
    {{ $attributes->merge([
        'class' => 'group relative flex flex-col bg-surface-1 border rounded-2xl p-3.5 sm:p-4 text-left transition duration-200 select-none cursor-pointer ' .
            ($selected
                ? 'border-accent ring-2 ring-accent/30 shadow-card-hover'
                : 'border-subtle hover:border-accent/50 hover:bg-surface-2/40'
            )
    ]) }}
>
    <!-- Chord Name Header -->
    <div class="px-1 mb-2.5 flex items-baseline justify-between">
        <h3 class="text-base sm:text-lg font-semibold text-ink group-hover:text-accent transition-colors truncate">
            {{ $chord->name }}
        </h3>
        @if (!empty($chord->pronunciation))
            <span class="text-[11px] text-ink-muted hidden group-hover:inline-block transition-opacity truncate max-w-[50%]">
                {{ $chord->pronunciation }}
            </span>
        @endif
    </div>

    <!-- Inner Diagram Box (Light Blue/Periwinkle Container) -->
    <div class="bg-[#9DB9FB] rounded-xl p-3 sm:p-4 flex items-center justify-center aspect-[4/5] overflow-hidden transition duration-200 group-hover:scale-[1.02]">
        <x-chord-diagram :chord="$chord" />
    </div>
</div>

