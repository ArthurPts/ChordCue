@props([
    'chord',
])

@php
    $diagramUrl = $chord->diagram_url ?? asset('storage/diagrams/No_Image_Available.jpg');
@endphp

<img
    src="{{ $diagramUrl }}"
    alt="{{ $chord->name }}"
    class="max-h-full max-w-full object-contain rounded-sm opacity-85 group-hover:opacity-100 transition-opacity"
>
