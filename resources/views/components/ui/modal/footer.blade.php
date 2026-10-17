{{--
    The button row of an acun:modal written in parts, as the classic footer slot:
    buttons at the end, on a grey band. Attributes (class…) go on the row.
--}}
<div data-slot="modal-footer" {{ $attributes->merge(['class' => 'flex flex-none items-center justify-end gap-2 border-t border-gray-200 bg-gray-50 px-5 py-4 dark:border-zinc-700 dark:bg-zinc-800/50']) }}>
    {{ $slot }}
</div>
