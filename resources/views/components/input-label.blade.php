@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-body font-medium text-ink-secondary mb-6']) }}>
    {{ $value ?? $slot }}
</label>
