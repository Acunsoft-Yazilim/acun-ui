@props([
    // ISO 3166-1 alpha-2 country code: tr, gb, us, de, fr, es, it, nl, ru.
    'code',
])

{{--
    A small flag (3:2), inline SVG: <acun:flag code="tr" class="h-4 w-6" />. Decorative
    (aria-hidden): put the country or language name next to it. An unknown code prints nothing.
--}}
{{ \Acun\Ui\Support\Flags::svg($code, trim('flex-none overflow-hidden rounded-[2px] ring-1 ring-ink/10 '.$attributes->get('class', 'h-3.5 w-5'))) }}
