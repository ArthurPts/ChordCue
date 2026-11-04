@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-regular text-body text-ink bg-success-tint border border-success rounded-sm p-12 flex items-center gap-8']) }}>
        <x-icon name="check" class="h-icon-control w-icon-control text-success" />
        <span>{{ $status }}</span>
    </div>
@endif
