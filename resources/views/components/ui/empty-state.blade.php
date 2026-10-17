@props([
    'title',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-3 rounded-sm border border-dashed border-line-strong px-6 py-12 text-center']) }}>
    @isset($icon)
        <div class="text-faint">{{ $icon }}</div>
    @endisset

    <div>
        <p class="text-sm font-semibold text-ink">{{ $title }}</p>
        @if ($description)
            <p class="mt-1 text-sm text-muted">{{ $description }}</p>
        @endif
    </div>

    @isset($action)
        <div class="mt-2">{{ $action }}</div>
    @endisset
</div>
