<?php

namespace Acun\Ui\Support;

use Illuminate\Container\Container;
use InvalidArgumentException;

/**
 * The tags of the paid package, acunsoft/acun-pro, by name only (the kit uses none of its code).
 * In an application without it such a tag stops with an error that says where the component is,
 * not Laravel's bare "Unable to locate a class or view for component".
 */
final class PaidComponents
{
    /** Tag names; an entry ending in "." covers every name under it (nav.item, shop.price…). */
    public const NAMES = [
        // The panel: layouts, menu, user menu, filters and the chrome-less auth and message panels.
        'layout.sidebar', 'layout.horizontal', 'layout.auth', 'layout.message', 'layout.print',
        'menu', 'nav.', 'user-menu', 'filter-button', 'filter-context', 'auth-panel', 'message-panel',
        // The PRO components.
        'chart', 'combobox', 'datatables', 'date-picker', 'date-range', 'file-upload', 'form', 'form-actions',
        'form-guard-dialog', 'form-modal', 'input-otp', 'loading-overlay', 'map', 'media-player', 'option-card',
        'pill-action', 'range', 'rating', 'sortable', 'sortable-item', 'sortable.item', 'step', 'stepper',
        'stepper.step', 'tag-input', 'timeline', 'timeline-item', 'timeline.item', 'tour', 'tree', 'tree-branch',
        'tree.branch', 'unsaved-changes-dialog',
        // The storefront components.
        'shop.',
    ];

    public static function isPaid(string $name): bool
    {
        foreach (self::NAMES as $paid) {
            if ($name === $paid || (str_ends_with($paid, '.') && str_starts_with($name, $paid))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Stops at the first paid tag that no component folder has.
     *
     * @param  iterable<string>  $names  The <acun:*> tag names of a template (AcunTagCompiler::names()).
     *
     * @throws InvalidArgumentException
     */
    public static function ensureInstalled(iterable $names): void
    {
        foreach ($names as $name) {
            if (self::isPaid($name) && ! Container::getInstance()->make(ComponentPaths::class)->resolves($name)) {
                throw new InvalidArgumentException('[Acun UI] <'.AcunTagCompiler::PREFIX.":{$name}> is an Acun Pro component (acunsoft/acun-pro), which this"
                    .' application does not have. The free acunsoft/acun-ui has the general components and the'
                    .' acun:layout.head and acun:layout.blank layouts; the panel layouts, the menu, the PRO components'
                    .' and the storefront come with acunsoft/acun-pro.');
            }
        }
    }
}
