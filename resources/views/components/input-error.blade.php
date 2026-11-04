@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'text-caption text-error mt-4 space-y-4']) }}>
        @foreach ((array) $messages as $message)
            <li class="flex items-center gap-4">
                <x-icon name="alert-circle" class="h-icon-chip w-icon-chip text-error" />
                <span>{{ $message }}</span>
            </li>
        @endforeach
    </ul>
@endif
