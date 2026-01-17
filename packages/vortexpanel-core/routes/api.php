<?php

use Illuminate\Support\Facades\Route;
use VortexPanel\Core\Http\Controllers\ResourcesController;
use VortexPanel\Core\Http\Controllers\GlobalSearchController;
use VortexPanel\Core\Http\Middleware\FastResponse;

Route::group([
    'prefix' => config('vortexpanel.path', 'admin') . '/api',
    'middleware' => array_merge(
        config('vortexpanel.middleware', ['web', 'auth', 'vortexpanel.access']),
        [FastResponse::class]
    ),
], function () {
    Route::get('/resources', [ResourcesController::class, 'index'])->name('vortexpanel.api.resources');
    Route::get('/resources/{slug}/data', [ResourcesController::class, 'data'])->name('vortexpanel.api.resources.data');
    Route::post('/resources/{slug}', [ResourcesController::class, 'store'])->name('vortexpanel.api.resources.store');
    Route::put('/resources/{slug}/{id}', [ResourcesController::class, 'update'])->name('vortexpanel.api.resources.update');
    Route::delete('/resources/{slug}/{id}', [ResourcesController::class, 'destroy'])->name('vortexpanel.api.resources.destroy');

    // Global search for command palette
    Route::get('/search', GlobalSearchController::class)->name('vortexpanel.api.search');
});
