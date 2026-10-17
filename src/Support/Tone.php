<?php

namespace Acun\Ui\Support;

/**
 * The palette's colour names, shared by every coloured component (acun:button,
 * badge, alert, progress, stat-card, avatar, timeline-item, pill-action):
 *
 *     primary · secondary · success · danger · warning · info · dark
 *
 * Older and Tailwind names still work: brand → primary, neutral / gray → secondary,
 * error → danger, emerald / green → success, amber / yellow → warning, sky / blue → info.
 */
final class Tone
{
    public const NAMES = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'dark'];

    private const ALIASES = [
        'brand' => 'primary',
        'neutral' => 'secondary',
        'gray' => 'secondary',
        'grey' => 'secondary',
        'error' => 'danger',
        'red' => 'danger',
        'emerald' => 'success',
        'green' => 'success',
        'amber' => 'warning',
        'yellow' => 'warning',
        'sky' => 'info',
        'blue' => 'info',
    ];

    /** The palette name for $value (an alias is resolved); $default when it is not a colour. */
    public static function name(?string $value, string $default = 'primary'): string
    {
        $value = self::ALIASES[$value] ?? $value;

        return in_array($value, self::NAMES, true) ? $value : $default;
    }
}
