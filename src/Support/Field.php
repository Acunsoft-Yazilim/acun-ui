<?php

namespace Acun\Ui\Support;

use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Support\Arr;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ComponentAttributeBag;

/**
 * What the form fields (acun:input, select, textarea, input-group, checkbox, radio,
 * search) work out the same way: their name, the key of their error and old input,
 * a stable id, where the page's attributes go, and the classes of the control.
 */
final class Field
{
    /** Attributes that place the whole field (see isLayout()). */
    private const LAYOUT = ['class', 'style', ':class', ':style', 'x-bind:class', 'x-bind:style', 'x-show', 'x-cloak', 'wire:key', 'wire:show'];

    /** What belongs to the native control even where the root holds the value (see isNative()). */
    private const NATIVE = ['required', 'autofocus', 'tabindex', 'autocomplete', 'autocapitalize', 'spellcheck', 'inputmode', 'enterkeyhint'];

    /** The kinds that a wider one sets too: p sets px, py, pt…; size sets w and h. */
    private const COVERS = [
        'p' => ['px', 'py', 'ps', 'pe', 'pt', 'pb', 'pl', 'pr'], 'px' => ['ps', 'pe', 'pl', 'pr'], 'py' => ['pt', 'pb'],
        'm' => ['mx', 'my', 'ms', 'me', 'mt', 'mb', 'ml', 'mr'], 'mx' => ['ms', 'me', 'ml', 'mr'], 'my' => ['mt', 'mb'],
        'size' => ['w', 'h'], 'gap' => ['gap-x', 'gap-y'],
        'inset' => ['inset-x', 'inset-y', 'start', 'end', 'top', 'bottom', 'left', 'right'], 'inset-x' => ['start', 'end', 'left', 'right'], 'inset-y' => ['top', 'bottom'],
        'rounded' => ['rounded-s', 'rounded-e', 'rounded-t', 'rounded-b', 'rounded-l', 'rounded-r', 'rounded-ss', 'rounded-se', 'rounded-es', 'rounded-ee', 'rounded-tl', 'rounded-tr', 'rounded-bl', 'rounded-br'],
        'border-w' => ['border-w-x', 'border-w-y', 'border-w-s', 'border-w-e', 'border-w-t', 'border-w-b', 'border-w-l', 'border-w-r'],
        'border-color' => ['border-color-x', 'border-color-y', 'border-color-s', 'border-color-e', 'border-color-t', 'border-color-b', 'border-color-l', 'border-color-r'],
    ];

    /**
     * Layout attributes place the whole field: class, style (and their bound forms), x-show,
     * x-cloak, x-transition…, wire:key, wire:show, wire:transition. A field puts them on its
     * outer element, so the label and the message follow them.
     */
    public static function isLayout(string $key): bool
    {
        return in_array($key, self::LAYOUT, true) || str_starts_with($key, 'x-transition') || str_starts_with($key, 'wire:transition');
    }

    /** The attributes that place the whole field: they go on its outer element. */
    public static function layoutAttributes(ComponentAttributeBag $attributes): ComponentAttributeBag
    {
        return $attributes->filter(fn ($value, $key) => self::isLayout((string) $key));
    }

    /** The rest (name, wire:model, required, aria-*, data-*, events…): they go on the control. */
    public static function controlAttributes(ComponentAttributeBag $attributes): ComponentAttributeBag
    {
        return $attributes->filter(fn ($value, $key) => ! self::isLayout((string) $key));
    }

    /**
     * A field whose root holds the value (x-modelable: combobox, tag-input, rating) keeps the
     * model, the listeners and the layout attributes on the root, which dispatches the events.
     * Its native control still gets the focus and typing attributes (required, autofocus,
     * tabindex, autocomplete, autocapitalize, spellcheck, inputmode, enterkeyhint), aria-*
     * and data-*; data-dirty-* stays on the root (it keeps the whole field, its hidden fields
     * too, out of the dirty-check).
     */
    public static function isNative(string $key): bool
    {
        return in_array($key, self::NATIVE, true)
            || str_starts_with($key, 'aria-')
            || (str_starts_with($key, 'data-') && ! str_starts_with($key, 'data-dirty'));
    }

    /** The native control's attributes of a field whose root holds the value (see isNative()). */
    public static function nativeAttributes(ComponentAttributeBag $attributes): ComponentAttributeBag
    {
        return $attributes->filter(fn ($value, $key) => self::isNative((string) $key));
    }

    /** The root's attributes of a field that holds the value there: all but the native control's. */
    public static function rootAttributes(ComponentAttributeBag $attributes): ComponentAttributeBag
    {
        return $attributes->filter(fn ($value, $key) => ! self::isNative((string) $key));
    }

    /**
     * The control's classes: the kit's, with input-class after them. A class of input-class
     * replaces the kit's classes of the same kind under the same variants (ring-blue-500 the
     * kit's ring colour, h-12 its height, p-0 its px/py), since Tailwind orders utilities by
     * their value, not by where they are written, and either could win. $keep are kit classes
     * input-class never replaces (the error frame: a field with an error always shows it).
     * Without input-class the kit's classes are returned untouched.
     *
     * Only the kinds a form control uses are recognised (spacing, sizing, display, position,
     * rounded, border, ring, outline, shadow, background, text, font…); any other class of
     * input-class is added beside the kit's. An important class (! before or after) is
     * matched like the plain one.
     */
    public static function controlClass(string $kit, mixed $inputClass = null, string $keep = ''): string
    {
        $own = self::classList(Arr::toCssClasses($inputClass ?? []));

        if ($own === []) {
            return $kit;
        }

        $kept = array_filter(array_map(self::classKey(...), self::classList($keep)));
        $replaced = [];

        foreach ($own as $index => $class) {
            $key = self::classKey($class);

            if ($key === null) {
                continue;
            }

            [$variants, $group] = explode('|', $key, 2);
            $covers = array_map(fn (string $covered) => $variants.'|'.$covered, [$group, ...(self::COVERS[$group] ?? [])]);

            // The page's class would hide a kept kit class (the error frame): it gives way.
            if (array_intersect($covers, $kept) !== []) {
                unset($own[$index]);

                continue;
            }

            array_push($replaced, ...$covers);
        }

        $kitClasses = array_filter(self::classList($kit), fn (string $class) => ! in_array(self::classKey($class), $replaced, true));

        return implode(' ', [...$kitClasses, ...$own]);
    }

    /** @return list<string> */
    private static function classList(string $classes): array
    {
        return array_values(array_filter(preg_split('/\s+/', trim($classes)) ?: [], fn (string $class) => $class !== ''));
    }

    /** "variants|kind" of a utility class (focus:ring-blue-500 → "focus|ring-color"); null when its kind is not known. */
    private static function classKey(string $class): ?string
    {
        // The variants are split off at the colons outside [...] and (...): [&[readonly]]:bg-gray-50, data-[x=y]:p-1.
        $parts = preg_split('/:(?![^\[]*\])(?![^(]*\))/', $class) ?: [$class];
        $utility = (string) array_pop($parts);
        $group = self::classGroup(ltrim(trim($utility, '!'), '-'));

        return $group === null ? null : implode(':', $parts).'|'.$group;
    }

    private static function classGroup(string $utility): ?string
    {
        if (in_array($utility, ['block', 'inline-block', 'inline', 'flex', 'inline-flex', 'grid', 'inline-grid', 'contents', 'hidden', 'table', 'flow-root', 'list-item'], true)) {
            return 'display';
        }

        if (in_array($utility, ['static', 'fixed', 'absolute', 'relative', 'sticky'], true)) {
            return 'position';
        }

        // A width: a number or an arbitrary length (border-[3px], border-(length:--w)); border-(--c) is a colour.
        $width = '(?:\d+|\[(?:length:)?\d[^\]]*\]|\[length:[^\]]*\]|\(length:[^)]*\))';

        return match (true) {
            (bool) preg_match('/^(min-w|min-h|max-w|max-h|size|w|h|gap-x|gap-y|gap|inset-x|inset-y|inset|start|end|top|bottom|left|right|z|order|leading|tracking|opacity|cursor|appearance|resize|items|justify|self|overflow-x|overflow-y|overflow|duration|ease|delay|accent|caret|basis|grow|shrink|select|indent|align|whitespace|transition|p[xysetblr]?|m[xysetblr]?)(?:-|$)/', $utility, $match) => $match[1],
            (bool) preg_match('/^rounded(?:-(ss|se|es|ee|tl|tr|bl|br|s|e|t|b|l|r))?(?:-|$)/', $utility, $match) => isset($match[1]) && $match[1] !== '' ? 'rounded-'.$match[1] : 'rounded',
            (bool) preg_match('/^text-(xs|sm|base|lg|xl|[2-9]xl)(?:\/.+)?$|^text-\[(?:length:|\d)|^text-\(length:/', $utility) => 'text-size',
            (bool) preg_match('/^text-(left|center|right|justify|start|end)$/', $utility) => 'text-align',
            (bool) preg_match('/^text-(wrap|nowrap|balance|pretty|ellipsis|clip)$/', $utility) => 'text-wrap',
            str_starts_with($utility, 'text-') => 'text-color',
            (bool) preg_match('/^bg-(fixed|local|scroll|auto|cover|contain|none|center|top|bottom|left|right|repeat|no-repeat|clip-|origin-|linear|radial|conic|gradient|blend|size-|position-|\[url)/', $utility) => null,
            str_starts_with($utility, 'bg-') => 'bg-color',
            (bool) preg_match('/^border(?:-([xysetblr]))?(?:-'.$width.')?$/', $utility, $match) => 'border-w'.(isset($match[1]) && $match[1] !== '' ? '-'.$match[1] : ''),
            (bool) preg_match('/^border-(solid|dashed|dotted|double|hidden|none)$/', $utility) => 'border-style',
            (bool) preg_match('/^border-(collapse|separate|spacing)/', $utility) => null,
            (bool) preg_match('/^border-([xysetblr])-/', $utility, $match) => 'border-color-'.$match[1],
            str_starts_with($utility, 'border-') => 'border-color',
            (bool) preg_match('/^ring(?:-'.$width.')?$/', $utility) => 'ring-w',
            $utility === 'ring-inset' => 'ring-inset',
            (bool) preg_match('/^ring-offset-'.$width.'$/', $utility) => 'ring-offset-w',
            str_starts_with($utility, 'ring-offset-') => 'ring-offset-color',
            str_starts_with($utility, 'ring-') => 'ring-color',
            (bool) preg_match('/^outline-(none|hidden|solid|dashed|dotted|double)$/', $utility) => 'outline-style',
            (bool) preg_match('/^outline(?:-'.$width.')?$/', $utility) => 'outline-w',
            str_starts_with($utility, 'outline-offset-') => 'outline-offset',
            str_starts_with($utility, 'outline-') => 'outline-color',
            (bool) preg_match('/^shadow(?:-(?:2xs|xs|sm|md|lg|xl|2xl|none|inner|\[.*\]|\(.*\)))?$/', $utility) => 'shadow',
            str_starts_with($utility, 'shadow-') => 'shadow-color',
            (bool) preg_match('/^font-(thin|extralight|light|normal|medium|semibold|bold|extrabold|black|\d+)$/', $utility) => 'font-weight',
            str_starts_with($utility, 'font-') => 'font-family',
            (bool) preg_match('/^flex-(row|col)(?:-reverse)?$/', $utility) => 'flex-direction',
            (bool) preg_match('/^flex-(wrap|nowrap|wrap-reverse)$/', $utility) => 'flex-wrap',
            str_starts_with($utility, 'flex-') => 'flex',
            default => null,
        };
    }

    /** The name prop, the name attribute or the wire:model property (with .live and other modifiers). */
    public static function name(?string $name, ComponentAttributeBag $attributes): ?string
    {
        return $name ?? $attributes->get('name') ?? $attributes->whereStartsWith('wire:model')->first();
    }

    /** The key of the error bag and old(): address[city] is address.city, tags[] is tags. */
    public static function key(?string $name): ?string
    {
        return blank($name) ? null : trim(str_replace(['[]', '[', ']'], ['', '.', ''], $name), '.');
    }

    /**
     * The first validation error of the field, or null (also where no error bag is
     * shared: a mail, a PDF, a view rendered outside the web middleware).
     */
    public static function error(?string $name, mixed $errors): ?string
    {
        $key = self::key($name);

        return $key !== null && ($errors instanceof ViewErrorBag || $errors instanceof MessageBag) ? ($errors->first($key) ?: null) : null;
    }

    /** The value a classic form gets back after a failed validation, else $default. */
    public static function old(?string $name, mixed $default = null): mixed
    {
        $key = self::key($name);

        return $key !== null && request()->hasSession() ? request()->old($key, $default) : $default;
    }

    /** The field is bound to Livewire or Alpine: its value comes from there, not from old(). */
    public static function bound(ComponentAttributeBag $attributes): bool
    {
        return $attributes->whereStartsWith(['wire:model', 'x-model'])->isNotEmpty();
    }

    /**
     * The id: the id attribute, else $fallback (the field name), else the x-model
     * expression, else one made from what the field shows. Never random: Livewire's
     * morph matches elements by id, and a new id on every render replaced the field
     * (the focus and the caret were lost while typing). A worked-out id used again in the
     * same render (the same field twice on a page) gets -2, -3…; an id attribute is kept.
     */
    public static function id(string $kind, ComponentAttributeBag $attributes, ?string $fallback = null, mixed ...$seed): string
    {
        if (filled($attributes->get('id'))) {
            return (string) $attributes->get('id');
        }

        $id = $fallback ?? $attributes->whereStartsWith('x-model')->first();

        if (blank($id)) {
            $shown = json_encode([$seed, $attributes->except(['class', 'id'])->getAttributes()], JSON_PARTIAL_OUTPUT_ON_ERROR);
            $id = $kind.'-'.substr(md5((string) $shown), 0, 10);
        }

        return self::unique((string) $id);
    }

    /**
     * Counted per render: Blade forgets its @once marks when a page (or a Livewire
     * component, which an update renders alone) has been rendered, so the same field
     * gets the same id on every render. The ids of a Livewire component are counted
     * apart from the page around it.
     */
    private static function unique(string $id): string
    {
        $view = app('view');
        $scope = self::livewireScope();

        for ($count = 1; ; $count++) {
            $mark = "acun-ui-field-id:{$scope}:{$id}:{$count}";

            if (! $view->hasRenderedOnce($mark)) {
                $view->markAsRenderedOnce($mark);

                return $count === 1 ? $id : $id.'-'.$count;
            }
        }
    }

    /** The Livewire component being rendered, or 'page'. */
    private static function livewireScope(): string
    {
        if (! class_exists(\Livewire\Mechanisms\HandleComponents\HandleComponents::class)) {
            return 'page';
        }

        $component = last(\Livewire\Mechanisms\HandleComponents\HandleComponents::$componentStack);

        return $component ? $component->getId() : 'page';
    }
}
