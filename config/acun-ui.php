<?php

return [

    /*
    | Default appearance of the panel layouts (acun:layout.sidebar, horizontal,
    | auth, message). These are defaults: a choice the user made (saved in the
    | browser, or passed to the layout as a saved account preference) wins.
    | A single page can force its own values with Acun\Ui\Support\Layout::page().
    | null keeps the package default shown in the comment.
    */
    'layout' => [
        'theme' => null,          // light | dark | system (system)
        'brand_color' => null,    // default | green | red | yellow | blue | gray | dark | #rrggbb (default)
        'skin' => null,           // default | bordered (default)
        'content_width' => null,  // full | boxed: at most 1440px, centred (full)
        'semi_dark' => null,      // true: dark menu in the light theme (false)
        'sidebar_mode' => null,   // expanded | collapsed (expanded)
        'navbar_mode' => null,    // fixed | static | hidden (fixed)
        'customizer' => null,     // true: the appearance customizer on the right edge (false)
        'theme_toggle' => null,   // false: no theme button in the top bar (true)
    ],

    // Livewire select fix: after an update, <select wire:model> boxes of the component that sent the
    // request show the value that component holds (a browser/morph quirk can leave the old option
    // selected). Off by default; a container with data-acun-select-sync turns it on for the boxes in it.
    'select_sync' => false,

    // The browser builds of the @acunsoft/acun-ui runtime: acun-ui-boot.js (acun:layout.head) and
    // acun-ui.js (@acunUiScripts, for applications without Vite).
    'assets' => [
        // The package serves them at /{prefix}/{file} (no middleware, cached for a year). After
        // php artisan vendor:publish --tag=acun-ui-assets the copies in public/vendor/acun-ui are loaded instead.
        'prefix' => 'acun-ui',
        // The URL of the folder holding them (a CDN); wins over both.
        'url' => null,
    ],

    // PRO JS without Vite: @acunUiScripts (acunsoft/acun-ui-pro).
    'pro' => [
        'assets' => [
            // true: the package serves dist/acun-ui-pro.js at /{prefix}/acun-ui-pro.js for @acunUiScripts without Vite.
            // Off by default (with Vite the paid bundle would be public at a known address); or publish it / set url.
            'route' => false,
            // The route's path prefix (no middleware, cached for a year). After
            // php artisan vendor:publish --tag=acun-ui-pro-assets the copy in
            // public/vendor/acun-ui-pro is loaded instead.
            'prefix' => 'acun-ui',
            // A full URL of the file (a CDN); wins over both.
            'url' => null,
        ],
    ],

];
