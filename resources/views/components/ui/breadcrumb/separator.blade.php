{{--
    The chevron between two steps of acun:breadcrumb, or the slot instead (e.g. "/").
    Each acun:breadcrumb.item draws one before itself, so write it only before an item
    with :separator="false". Attributes (class…) go on the separator; it is hidden
    from screen readers.
--}}
@if ($slot->isEmpty())
    <svg data-slot="breadcrumb-separator" {{ $attributes->class('size-3.5 flex-none text-line-strong') }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6"/></svg>
@else
    <span data-slot="breadcrumb-separator" aria-hidden="true" {{ $attributes->class('flex-none text-line-strong') }}>{{ $slot }}</span>
@endif
