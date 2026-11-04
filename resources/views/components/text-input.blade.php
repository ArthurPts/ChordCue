@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'h-[44px] bg-surface-3 px-14 rounded-lg border border-subtle text-ink placeholder:text-ink-tertiary placeholder:text-sm focus:border-accent focus:outline-none focus:ring-1 focus:ring-accent text-sm font-regular transition-colors disabled:text-ink-disabled disabled:cursor-not-allowed']) }}>
