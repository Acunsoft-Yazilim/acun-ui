<?php

namespace Acun\Ui\Support;

use Illuminate\View\ComponentAttributeBag;
use Livewire\Livewire;

/**
 * The value a wire:model binds, read on the server from the Livewire component being rendered, so a component
 * draws its first frame from the model (acun:tabs opens the bound tab without :active="$tab" beside wire:model).
 * Its parts ask without attributes: the binding of the nearest component around them (Blade keeps a component's
 * attributes in its data, which @aware reads too). Null without Livewire, without a binding or outside a render.
 */
final class Bound
{
    /** wire:model and the modifiers it is written with. */
    private const MODELS = ['wire:model', 'wire:model.live', 'wire:model.blur', 'wire:model.change', 'wire:model.lazy', 'wire:model.defer', 'wire:model.fill'];

    public static function value(?ComponentAttributeBag $attributes = null): mixed
    {
        if (! class_exists(Livewire::class)) {
            return null;
        }

        $property = $attributes?->whereStartsWith('wire:model')->first();
        if ($attributes === null) {
            $view = app('view');
            foreach (self::MODELS as $key) {
                $found = $view->getConsumableComponentData($key);
                if (is_string($found) && $found !== '') {
                    $property = $found;
                    break;
                }
            }
        }

        if (! is_string($property) || $property === '') {
            return null;
        }

        $component = Livewire::current();

        return $component === null ? null : data_get($component, $property);
    }
}
