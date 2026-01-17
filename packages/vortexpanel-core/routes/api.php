<?php

use Illuminate\Support\Facades\Route;
use VortexPanel\Core\Http\Controllers\ResourcesController;
use VortexPanel\Core\Http\Controllers\GlobalSearchController;

Route::group([
    'prefix' => config('vortexpanel.path', 'admin') . '/api',
    'middleware' => config('vortexpanel.middleware', ['web','auth','vortexpanel.access']),
], function () {
    Route::get('/resources', [ResourcesController::class, 'index'])->name('vortexpanel.api.resources');
    Route::get('/resources/{slug}/data', [ResourcesController::class, 'data'])->name('vortexpanel.api.resources.data');

    // Optional signature feature: global search (for command palette)
    Route::get('/search', GlobalSearchController::class)->name('vortexpanel.api.search');
});
