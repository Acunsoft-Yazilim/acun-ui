<?php

namespace Acun\Ui\Support;

/**
 * The messages of the browser's validation bubbles for the kit's fields, in the current language
 * (acun:layout.head prints them; js/behavior/validation-messages.js uses them). Keys are the
 * browser's ValidityState flags, with :min, :max, :minlength and :maxlength filled in by the field.
 */
final class ValidationMessages
{
    /** @return array<string, string> */
    public static function all(): array
    {
        return [
            'valueMissing' => __('Please fill in this field.'),
            'valueMissingCheckbox' => __('Please tick this box.'),
            'valueMissingChoice' => __('Please choose an option.'),
            'typeMismatch' => __('Please enter a valid value.'),
            'typeMismatchEmail' => __('Please enter an e-mail address.'),
            'typeMismatchUrl' => __('Please enter a web address.'),
            'badInput' => __('Please enter a number.'),
            'patternMismatch' => __('Please match the requested format.'),
            'tooLong' => __('Please use at most :maxlength characters.'),
            'tooShort' => __('Please use at least :minlength characters.'),
            'rangeUnderflow' => __('Please enter :min or more.'),
            'rangeOverflow' => __('Please enter :max or less.'),
            'stepMismatch' => __('Please enter a valid value.'),
        ];
    }
}
