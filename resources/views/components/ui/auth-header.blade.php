@props([
    'title',
    'description' => null,
])

<div {{ $attributes->class('flex w-full flex-col') }}>
    <h1 class="text-2xl font-semibold tracking-tight text-gray-700 dark:text-zinc-100">{{ $title }}</h1>
    @if (filled($description))
        <p class="mt-2 text-sm leading-6 text-gray-500 dark:text-zinc-400">{{ $description }}</p>
    @endif
</div>
