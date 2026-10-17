@props([
    'title' => null,
    // A line under the title: the date, the scope, the number of records…
    'subtitle' => null,
    // @page: A4 · A5 · letter…, portrait | landscape, the margin.
    'size' => 'A4',
    'orientation' => 'portrait',
    'margin' => '12mm',
    // A highlighted line under the header: "Only the first 2,000 records are shown."
    'notice' => null,
    // The filters the document was made with: ['Durum' => 'Aktif'] or [['label' => 'Durum', 'value' => 'Aktif'], …].
    'filters' => [],
    // The totals under the content, in the same form; the last row is the grand total.
    'totals' => [],
    // Signature boxes: ['Hazırlayan', 'Onaylayan'] or [['label' => 'Hazırlayan', 'name' => 'Ayşe Yılmaz'], …].
    'signatures' => [],
    // "Page 1 / 3" in the bottom margin of every printed page (browsers that print page margin boxes).
    'pageNumbers' => true,
    // The Print / Close bar on screen (never printed).
    'toolbar' => true,
    // Opens the browser's print window once the page has loaded.
    'autoPrint' => false,
    // CSP nonce of the inline style and script (default: Vite::cspNonce()).
    'nonce' => null,
])

{{--
    A page made to be printed or saved as PDF: an invoice, an order slip, a report. Standalone:
    its own styles (light theme, A4, tables that repeat their header on every page), no panel,
    no application CSS needed; give the head slot your @vite line to use Tailwind classes inside.
        <acun:layout.print title="Sipariş raporu" subtitle="14.10.2026 · 128 sipariş" :filters="$filtreler" :totals="['Ara toplam' => '₺12.400', 'KDV' => '₺2.480', 'Genel toplam' => '₺14.880']" :signatures="['Hazırlayan', 'Onaylayan']" auto-print>
            <table>…</table>
        </acun:layout.print>
    In the content's tables data-align="end" (amounts) and data-align="center" set the alignment.
    Slots: head (extra <head> contents), aside (the end of the header: a logo, the document
    number), (default) the content, footer (after the signatures). Attributes go on <body>.
--}}
@php
    $nonce ??= \Illuminate\Support\Facades\Vite::cspNonce();
    $nonceAttribute = $nonce ? ' nonce="'.e($nonce).'"' : '';
    // Printed into the style: anything but a page size name, an orientation and lengths falls back to the default.
    $size = preg_match('/^[A-Za-z0-9-]+$/', (string) $size) ? $size : 'A4';
    $orientation = in_array($orientation, ['portrait', 'landscape'], true) ? $orientation : 'portrait';
    $margin = preg_match('/^(\d+(\.\d+)?(mm|cm|in|pt|px)\s*){1,4}$/', trim((string) $margin)) ? trim($margin) : '12mm';
    // ['Label' => 'value'] or [['label' => …, 'value' => …]]: one list of rows.
    $rows = fn (array $items) => collect($items)
        ->map(fn ($item, $key) => is_array($item)
            ? ['label' => (string) ($item['label'] ?? ''), 'value' => (string) ($item['value'] ?? '')]
            : ['label' => (string) $key, 'value' => (string) $item])
        ->values()
        ->all();
    $filters = $rows($filters);
    $totals = $rows($totals);
    $signatures = collect($signatures)
        ->map(fn ($item) => is_array($item) ? ['label' => (string) ($item['label'] ?? ''), 'name' => $item['name'] ?? null] : ['label' => (string) $item, 'name' => null])
        ->values()
        ->all();
    // A CSS string: the translated word may hold quotes.
    $pageLabel = json_encode(__('Page').' ', JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ $title ?? config('app.name') }}</title>
    <style{!! $nonceAttribute !!}>
        @page { size: {{ $size }} {{ $orientation }}; margin: {{ $margin }};@if ($pageNumbers) @bottom-right { content: {!! $pageLabel !!} counter(page) " / " counter(pages); font: 9px system-ui, sans-serif; color: #6b7280; }@endif }
        *, ::before, ::after { box-sizing: border-box; }
        html { color-scheme: light; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        body { margin: 0; padding: 24px; font-family: system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; font-size: 12px; line-height: 1.45; color: #111827; background: #fff; }
        .ac-print-toolbar { display: flex; gap: 8px; margin-bottom: 16px; }
        .ac-print-toolbar button { padding: 6px 12px; border: 1px solid #d1d5db; border-radius: 4px; background: #fff; color: inherit; font: inherit; cursor: pointer; }
        .ac-print-toolbar button:hover { background: #f9fafb; }
        .ac-print-toolbar button:focus-visible { outline: 2px solid #2563eb; outline-offset: 2px; }
        .ac-print-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 24px; margin-bottom: 16px; }
        .ac-print-title { margin: 0 0 4px; font-size: 18px; font-weight: 700; line-height: 1.25; }
        .ac-print-subtitle { margin: 0; color: #4b5563; }
        .ac-print-aside { flex: none; text-align: end; color: #4b5563; }
        .ac-print-notice { margin: 8px 0 0; color: #92400e; }
        .ac-print-filters { display: flex; flex-wrap: wrap; gap: 4px 16px; margin: 8px 0 0; padding: 0; list-style: none; color: #374151; }
        .ac-print-filters dt { display: inline; font-weight: 600; }
        .ac-print-filters dt::after { content: ':'; }
        .ac-print-filters dd { display: inline; margin: 0 0 0 4px; }
        .ac-print-content table { width: 100%; border-collapse: collapse; }
        .ac-print-content thead { display: table-header-group; }
        .ac-print-content tr { break-inside: avoid; }
        .ac-print-content th, .ac-print-content td { padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: start; vertical-align: top; }
        .ac-print-content th { font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #374151; border-bottom: 2px solid #9ca3af; }
        .ac-print-content [data-align="end"] { text-align: end; font-variant-numeric: tabular-nums; }
        .ac-print-content [data-align="center"] { text-align: center; }
        .ac-print-totals { width: max-content; min-width: 240px; margin: 16px 0 0; margin-inline-start: auto; break-inside: avoid; }
        .ac-print-totals > div { display: flex; justify-content: space-between; gap: 32px; padding: 2px 0; }
        .ac-print-totals dt { color: #4b5563; }
        .ac-print-totals dd { margin: 0; text-align: end; font-variant-numeric: tabular-nums; }
        .ac-print-totals > .ac-print-grand { margin-top: 4px; padding-top: 6px; border-top: 2px solid #111827; font-size: 14px; font-weight: 700; }
        .ac-print-grand dt { color: #111827; }
        .ac-print-signatures { display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 32px; margin-top: 48px; break-inside: avoid; }
        .ac-print-signature { padding-top: 56px; border-bottom: 1px solid #9ca3af; }
        .ac-print-signature-label { margin-top: 6px; font-size: 10px; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; color: #374151; }
        .ac-print-signature-name { color: #4b5563; }
        .ac-print-footer { margin-top: 24px; color: #4b5563; }
        @media print { body { padding: 0; } .ac-print-toolbar { display: none; } }
    </style>
    {{ $head ?? '' }}
</head>
<body {{ $attributes }}>
    @if ($toolbar)
        <div class="ac-print-toolbar">
            <button type="button" data-ac-print="print">{{ __('Print') }}</button>
            <button type="button" data-ac-print="close">{{ __('Close') }}</button>
        </div>
    @endif

    <header class="ac-print-header">
        <div>
            @if ($title)
                <h1 class="ac-print-title">{{ $title }}</h1>
            @endif
            @if ($subtitle)
                <p class="ac-print-subtitle">{{ $subtitle }}</p>
            @endif
            @if ($filters !== [])
                <dl class="ac-print-filters">
                    @foreach ($filters as $filter)
                        <div><dt>{{ $filter['label'] }}</dt><dd>{{ $filter['value'] }}</dd></div>
                    @endforeach
                </dl>
            @endif
            @if ($notice)
                <p class="ac-print-notice">{{ $notice }}</p>
            @endif
        </div>

        @isset($aside)
            <div class="ac-print-aside">{{ $aside }}</div>
        @endisset
    </header>

    <main class="ac-print-content">
        {{ $slot }}
    </main>

    @if ($totals !== [])
        <dl class="ac-print-totals">
            @foreach ($totals as $total)
                <div{!! $loop->last && $loop->count > 1 ? ' class="ac-print-grand"' : '' !!}><dt>{{ $total['label'] }}</dt><dd>{{ $total['value'] }}</dd></div>
            @endforeach
        </dl>
    @endif

    @if ($signatures !== [])
        <div class="ac-print-signatures">
            @foreach ($signatures as $signature)
                <div>
                    <div class="ac-print-signature"></div>
                    <div class="ac-print-signature-label">{{ $signature['label'] }}</div>
                    @if (filled($signature['name']))
                        <div class="ac-print-signature-name">{{ $signature['name'] }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @isset($footer)
        <footer class="ac-print-footer">{{ $footer }}</footer>
    @endisset

    {{-- From a script with the nonce, not onclick="": a strict CSP blocks inline handlers. --}}
    @if ($toolbar || $autoPrint)
        <script{!! $nonceAttribute !!}>
            document.querySelectorAll('[data-ac-print]').forEach(function (button) {
                button.addEventListener('click', function () { button.dataset.acPrint === 'print' ? window.print() : window.close(); });
            });
            @if ($autoPrint)
                window.addEventListener('load', function () { window.print(); });
            @endif
        </script>
    @endif
</body>
</html>
