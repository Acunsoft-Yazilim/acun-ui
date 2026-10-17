@props([
    'title',
    'description' => null,
    // Breadcrumb items (see acun:breadcrumb); none by default.
    'breadcrumbs' => [],
    'homeRoute' => 'dashboard',
    'homeUrl' => null,
])

{{--
    Top of a page: breadcrumb, title, description and actions on the right.
        <acun:page-header title="Kullanıcılar" description="…" :breadcrumbs="[['label' => 'Uygulamalar'], ['label' => 'Kullanıcılar']]">
            <acun:slot:actions><acun:button>Yeni kullanıcı</acun:button></acun:slot:actions>
        </acun:page-header>
    The default slot is shown under the description (tabs, filters…). The badges slot sits
    beside the title (status, counts); the title can be a slot too.
        <acun:page-header title="Sipariş SP-250052">
            <acun:slot:badges><acun:badge variant="success">Ödendi</acun:badge></acun:slot:badges>
        </acun:page-header>
--}}
<header {{ $attributes->class('space-y-3') }}>
    @if ($breadcrumbs !== [])
        <acun:breadcrumb :items="$breadcrumbs" :home-route="$homeRoute" :home-url="$homeUrl" />
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="min-w-0 space-y-1">
            @isset($badges)
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-zinc-100">{{ $title }}</h1>
                    <div class="flex flex-wrap items-center gap-2">{{ $badges }}</div>
                </div>
            @else
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-zinc-100">{{ $title }}</h1>
            @endisset
            @if ($description)
                <p class="text-sm text-gray-600 dark:text-zinc-400">{{ $description }}</p>
            @endif
        </div>

        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">{{ $actions }}</div>
        @endisset
    </div>

    {{ $slot }}
</header>
