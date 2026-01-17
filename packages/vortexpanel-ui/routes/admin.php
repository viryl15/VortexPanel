<?php

use Illuminate\Support\Facades\Route;
use VortexPanel\UI\Http\Controllers\DashboardController;
use VortexPanel\UI\Http\Controllers\ResourcePageController;
use VortexPanel\UI\Http\Controllers\ResourceFormController;

Route::group([
    'prefix' => config('vortexpanel.path', 'admin'),
    'middleware' => config('vortexpanel.middleware', ['web','auth','vortexpanel.access']),
], function () {
    Route::get('/', DashboardController::class)->name('vortexpanel.dashboard');
    Route::get('/{resource}', ResourcePageController::class)->name('vortexpanel.resource');
    Route::get('/{resource}/create', [ResourceFormController::class, 'create'])->name('vortexpanel.resource.create');
    Route::get('/{resource}/{id}/edit', [ResourceFormController::class, 'edit'])->name('vortexpanel.resource.edit');
});
