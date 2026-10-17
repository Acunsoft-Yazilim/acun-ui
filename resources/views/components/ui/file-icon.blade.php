@props([
    // The file's name (fatura-2026-10.pdf) or its extension (pdf); the extension picks the colour.
    'name' => '',
    // sm · md · lg
    'size' => 'md',
])

{{--
    A file type at a glance: a coloured sheet with the extension on it, for file lists,
    attachments and downloads.
        <acun:file-icon name="fatura-2026-10.pdf" />
    PDF red, spreadsheets green, documents blue, presentations orange, images violet,
    archives amber, audio and video sky, anything else grey. It is decorative (aria-hidden):
    the file's name next to it says what it is.
--}}
@php
    $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION) ?: (string) $name);
    $extension = preg_replace('/[^a-z0-9]/', '', $extension);
    $groups = [
        'red' => ['pdf'],
        'green' => ['xls', 'xlsx', 'csv', 'ods', 'numbers'],
        'blue' => ['doc', 'docx', 'odt', 'rtf', 'txt', 'md', 'pages'],
        'orange' => ['ppt', 'pptx', 'odp', 'key'],
        'violet' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'avif', 'heic', 'bmp', 'tif', 'tiff'],
        'amber' => ['zip', 'rar', '7z', 'gz', 'tar', 'tgz'],
        'sky' => ['mp3', 'wav', 'ogg', 'm4a', 'mp4', 'mov', 'webm', 'avi', 'mkv'],
    ];
    $tone = collect($groups)->search(fn (array $extensions) => in_array($extension, $extensions, true)) ?: 'gray';
    $colors = [
        'red' => 'bg-red-50 text-red-600 ring-red-200 dark:bg-red-500/15 dark:text-red-400 dark:ring-red-500/30',
        'green' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/15 dark:text-emerald-400 dark:ring-emerald-500/30',
        'blue' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/15 dark:text-sky-400 dark:ring-sky-500/30',
        'orange' => 'bg-orange-50 text-orange-700 ring-orange-200 dark:bg-orange-500/15 dark:text-orange-400 dark:ring-orange-500/30',
        'violet' => 'bg-brand-50 text-brand-700 ring-brand-200 dark:bg-brand-500/15 dark:text-brand-300 dark:ring-brand-500/30',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/15 dark:text-amber-400 dark:ring-amber-500/30',
        'sky' => 'bg-sky-50 text-sky-700 ring-sky-200 dark:bg-sky-500/15 dark:text-sky-400 dark:ring-sky-500/30',
        'gray' => 'bg-gray-50 text-gray-600 ring-gray-200 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700',
    ];
    $sizes = ['sm' => 'h-8 w-6.5 text-[8px]', 'md' => 'h-10 w-8 text-[9px]', 'lg' => 'h-14 w-11 text-xs'];
    $label = strtoupper(substr($extension, 0, 4)) ?: '?';
@endphp

<span aria-hidden="true" {{ $attributes->class([
    'relative inline-flex flex-none items-end justify-center overflow-hidden rounded-sm pb-1 font-mono font-semibold ring-1 ring-inset',
    $sizes[$size] ?? $sizes['md'],
    $colors[$tone],
]) }}>
    {{-- The folded corner of the sheet. --}}
    <span class="absolute end-0 top-0 size-2 rounded-es-[2px] bg-current opacity-20"></span>
    {{ $label }}
</span>
