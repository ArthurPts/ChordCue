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
    class="{{ $large ? 'max-h-[240px] max-w-full object-contain rounded-sm' : 'max-h-full max-w-full object-contain rounded-sm' }} opacity-85 group-hover:opacity-100 transition-opacity"
>
