<button {{ $attributes->merge(['type' => 'submit', 'class' => 'h-[44px] px-16 py-10 rounded-lg bg-accent hover:bg-accent-hover text-white font-medium text-sm transition-colors inline-flex items-center justify-center gap-8 disabled:bg-surface-3 disabled:text-ink-disabled disabled:cursor-not-allowed cursor-pointer']) }}>
    {{ $slot }}
</button>
