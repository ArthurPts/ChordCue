@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'h-[40px] bg-surface-2 px-14 rounded-sm border border-subtle text-ink placeholder:text-ink-tertiary focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent text-xs sm:text-sm font-regular transition-colors disabled:text-ink-disabled disabled:cursor-not-allowed']) }}>
