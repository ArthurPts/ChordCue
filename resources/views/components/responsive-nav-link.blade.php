@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full px-12 py-8 rounded-sm bg-accent-soft-bg text-accent-soft-text text-body font-medium transition-colors'
            : 'block w-full px-12 py-8 rounded-sm text-ink-secondary hover:text-ink hover:bg-surface-2 text-body font-regular transition-colors';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
