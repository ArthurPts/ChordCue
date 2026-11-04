@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center text-body font-medium text-accent transition-colors'
            : 'inline-flex items-center text-body font-regular text-ink-secondary hover:text-ink transition-colors';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
