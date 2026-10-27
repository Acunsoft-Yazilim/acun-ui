<?php

namespace Acun\Ui\Support;

use Illuminate\Contracts\Support\Htmlable;

/**
 * Components written in parts (<acun:card.header>, <acun:modal.body>…) mark their
 * root element with data-slot="{component}-{part}". A container renders its slot
 * as it is when the slot starts with one of its parts, and keeps its classic
 * layout (title prop, padded body…) otherwise.
 *
 * Only the first element counts: a card nested inside another card's body must
 * not switch the outer card to parts. Whitespace and HTML comments (Livewire's
 * <!--[if BLOCK]--> markers) before it are skipped.
 */
final class Parts
{
    /**
     * The data-slot value of a part, e.g. Parts::name('card', 'header') is 'card-header'.
     */
    public static function name(string $component, string $part): string
    {
        return $component.'-'.$part;
    }

    /**
     * Whether the slot starts with a part of the component, e.g. Parts::used($slot, 'card').
     */
    public static function used(Htmlable|string|null $slot, string $component): bool
    {
        return (bool) preg_match('/^<[a-z][\w-]*\b[^>]*\sdata-slot="'.preg_quote($component, '/').'-[\w-]+"/i', self::trimmed($slot));
    }

    /**
     * When the slot starts with the root element of a nested Livewire component (wire:id),
     * what the component draws inside it; null otherwise.
     *
     * Livewire draws a nested component whole only on its first render: the parent's later
     * updates leave an empty stub in its place (and the browser keeps the component's DOM),
     * which holds nothing. A container must not change its markup around the
     * component with what it draws, or the morph of those updates would move or drop the
     * component's DOM.
     */
    public static function nestedComponent(Htmlable|string|null $slot): ?string
    {
        $html = self::trimmed($slot);

        if (! preg_match('/^<[a-z][\w-]*\b(?:[^>"\']|"[^"]*"|\'[^\']*\')*>/i', $html, $tag) || ! preg_match('/\swire:id="/', $tag[0])) {
            return null;
        }

        return substr($html, strlen($tag[0]));
    }

    /**
     * The slot with attributes added to its first element when that is a button or a link (a trigger's
     * control: aria-expanded…); anything else is left as it is. Whitespace and comments before it are skipped.
     */
    public static function controlAttributes(Htmlable|string|null $slot, string $attributes): string
    {
        $html = $slot instanceof Htmlable ? $slot->toHtml() : (string) $slot;

        return (string) preg_replace_callback(
            '/^((?:\s+|<!--.*?-->)*<(?:button|a)\b)/is',
            fn (array $match): string => $match[1].' '.$attributes,
            $html,
            1,
        );
    }

    /** The slot as HTML without the whitespace and comments before its first element. */
    private static function trimmed(Htmlable|string|null $slot): string
    {
        $html = $slot instanceof Htmlable ? $slot->toHtml() : (string) $slot;

        return (string) preg_replace('/^(?:\s+|<!--.*?-->)+/s', '', $html);
    }
}
