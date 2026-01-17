<?php

use Illuminate\Support\Facades\Route;
use VortexPanel\UI\Http\Controllers\DashboardController;
use VortexPanel\UI\Http\Controllers\ResourcePageController;

Route::group([
    'prefix' => config('vortexpanel.path', 'admin'),
    'middleware' => config('vortexpanel.middleware', ['web','auth','vortexpanel.access']),
], function () {
    Route::get('/', DashboardController::class)->name('vortexpanel.dashboard');
    Route::get('/{resource}', ResourcePageController::class)->name('vortexpanel.resource');
});
