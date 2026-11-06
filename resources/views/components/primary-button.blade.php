<button {{ $attributes->merge(['type' => 'submit', 'class' => 'h-[40px] px-20 rounded-sm bg-accent hover:bg-accent-hover text-accent-on font-bold text-xs sm:text-sm transition-all inline-flex items-center justify-center gap-8 disabled:bg-surface-3 disabled:text-ink-disabled disabled:cursor-not-allowed cursor-pointer shadow-sm']) }}>
    {{ $slot }}
</button>
