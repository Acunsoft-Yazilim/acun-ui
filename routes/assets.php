<?php

use Acun\Ui\Http\AssetController;
use Acun\Ui\Support\Assets;
use Illuminate\Support\Facades\Route;

/*
 * The runtime's browser builds (acun-ui-boot.js for acun:layout.head, acun-ui.js for
 * @acunUiScripts). Public and without middleware (no session, no cookies), like
 * Livewire's own script route. Path: config('acun-ui.assets.prefix') + /{file}.
 */
Route::get(Assets::routePath('{file}'), AssetController::class)
    ->where('file', 'acun-ui(?:-boot)?\.js')
    ->name('acun-ui.asset');
