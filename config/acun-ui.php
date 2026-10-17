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

    // The PRO JS without Vite (acunsoft/acun-pro) is set in config/acun-pro.php (panel.assets);
    // a 1.x 'pro' => ['assets' => […]] block here is still read until 3.0.

];
