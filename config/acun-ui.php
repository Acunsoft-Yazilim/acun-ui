<?php

return [

    /*
    | Default appearance of the panel layouts (acun:layout.sidebar, horizontal,
    | auth, message). These are defaults: a choice the user made (saved in the
    | browser, or passed to the layout as a saved account preference) wins.
    | A single page can force its own values with Acun\Ui\Support\Layout::page().
    | null keeps the package default shown in the comment. The choices are kept per
    | scope, so two products on one site (a panel and a storefront) never share them.
    */
    'layout' => [
        'mode' => null,           // light | dark | system (system)
        'theme' => null,          // a theme's key (default: the built-in light/dark pair); a 1.x light | dark | system here is read as the mode until 3.0
        'scope' => null,          // the browser storage of the choices, localStorage 'acun-ui:{scope}' (app; the panel layouts use panel)
        'brand_color' => null,    // default | green | red | yellow | blue | gray | dark | #rrggbb (default)
        'skin' => null,           // default | bordered (default)
        'content_width' => null,  // the page container (ac-container): default 1280px | wide 1440px | wider 1600px | full | a CSS length such as 1520px; boxed is wide (full)
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

    // acun:toast-container also shows the application's own session messages: a flash key => a toast type
    // (success | error | warning | info), e.g. ['status' => 'success', 'error' => 'error'] for
    // redirect()->with('status', 'Kaydedildi.'). Empty by default: some packages flash codes under 'status'
    // (Breeze's 'verification-link-sent') that are not messages.
    'toast_session_keys' => [],

    // Before Laravel 13 (no font plugin in laravel-vite-plugin, no @fonts): @acunFonts links the kit's fonts
    // (Public Sans, DM Mono) from fonts.bunny.net. false when the application loads them in its own CSS.
    'fonts_fallback' => true,

    // The browser's own validation bubbles ("Please fill out this field.") of the kit's fields in the application's
    // language (lang/*.json: "Please fill in this field." …), instead of the browser's. false leaves them to the browser.
    'validation_messages' => true,

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
