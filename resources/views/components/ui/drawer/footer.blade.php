@aware(['compact' => false, 'docked' => false])

{{--
    The button row of an acun:drawer written in parts, as the classic footer slot: it
    follows the drawer's compact (tighter row) and docked (no top line) props.
    Attributes (class…) go on the row.
--}}
<div data-slot="drawer-footer" {{ $attributes->class([
    'flex items-center',
    $compact ? 'gap-1.5 px-3 py-2.5' : 'justify-end gap-2 px-5 py-4',
    $docked ? 'bg-gray-50 dark:bg-zinc-800/50' : 'bg-gray-50 border-t border-gray-200 dark:bg-zinc-800/50 dark:border-zinc-700',
]) }}>
    {{ $slot }}
</div>
