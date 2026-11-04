<button {{ $attributes->merge(['type' => 'button', 'class' => 'h-control px-14 py-8 rounded-sm bg-transparent hover:bg-surface-2 border border-subtle text-ink font-medium text-body transition-colors inline-flex items-center justify-center gap-6 disabled:text-ink-disabled disabled:cursor-not-allowed cursor-pointer']) }}>
    {{ $slot }}
</button>
