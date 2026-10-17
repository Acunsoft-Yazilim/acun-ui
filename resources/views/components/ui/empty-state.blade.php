@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-3 rounded-sm border border-dashed border-gray-300 px-6 py-12 text-center dark:border-zinc-700']) }}>
    @isset($icon)
        <div class="text-gray-400 dark:text-zinc-500">{{ $icon }}</div>
    @endisset

    <div>
        <p class="text-sm font-semibold text-gray-900 dark:text-zinc-100">{{ $title }}</p>
        @if ($description)
            <p class="mt-1 text-sm text-gray-500 dark:text-zinc-400">{{ $description }}</p>
        @endif
    </div>

    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
