@props([
    'title',
    'description' => null,
])

<div {{ $attributes->class('flex w-full flex-col') }}>
    <h1 class="text-2xl font-semibold tracking-tight text-body">{{ $title }}</h1>
    @if (filled($description))
        <p class="mt-2 text-sm leading-6 text-muted">{{ $description }}</p>
    @endif
</div>
