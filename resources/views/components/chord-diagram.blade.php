@props([
    'chord',
    'large' => false,
])

@php
    $diagramUrl = $chord->diagram_url ?? asset('storage/diagrams/No_Image_Available.jpg');
@endphp

<img
    src="{{ $diagramUrl }}"
    alt="{{ $chord->name }}"
    class="{{ $large ? 'max-h-[260px] max-w-full object-contain rounded-xl' : 'max-h-full max-w-full object-contain rounded-lg' }}"
>
