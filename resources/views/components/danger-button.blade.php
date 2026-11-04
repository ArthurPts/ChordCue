<button {{ $attributes->merge(['type' => 'submit', 'class' => 'h-control px-14 py-8 rounded-sm bg-transparent hover:bg-error-tint border border-error text-error font-medium text-body transition-colors inline-flex items-center justify-center gap-6 disabled:text-ink-disabled disabled:border-subtle disabled:cursor-not-allowed cursor-pointer']) }}>
    {{ $slot }}
</button>
