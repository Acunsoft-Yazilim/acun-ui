<?php

use Acun\Ui\Http\AssetController;
use Acun\Ui\Http\FontController;
use Acun\Ui\Support\Assets;
use Illuminate\Support\Facades\Route;

/*
 * The runtime's browser builds (acun-ui-boot.js for acun:layout.head, acun-ui.js for
 * @acunUiScripts, acun-ui-carousel.js that acun-ui.js loads on a page with a carousel). Public and without middleware (no session, no cookies), like
 * Livewire's own script route. Path: config('acun-ui.assets.prefix') + /{file}.
 */
Route::get(Assets::routePath('{file}'), AssetController::class)
    ->where('file', 'acun-ui(?:-boot|-carousel)?\.js')
    ->name('acun-ui.asset');

/*
 * The font library's files (Acun\Ui\Support\FontLibrary): /{prefix}/fonts/{family}/{file}.woff2, public and cached for
 * a year like the scripts. Only the library's families and their WOFF2 files.
 */
Route::get(Assets::routePath('fonts/{font}/{file}'), FontController::class)
    ->where(['font' => '[a-z0-9]+(?:-[a-z0-9]+)*', 'file' => '[a-z0-9]+(?:-[a-z0-9]+)*\.woff2'])
    ->name('acun-ui.font');
