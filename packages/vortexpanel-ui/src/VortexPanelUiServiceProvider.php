<?php

namespace VortexPanel\UI;

use Illuminate\Support\ServiceProvider;

class VortexPanelUiServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/admin.php');

        // Publish Vue pages/components into the host app so Vite can bundle them
        $this->publishes([
            __DIR__ . '/../resources/js/Pages/VortexPanel' => resource_path('js/Pages/VortexPanel'),
            __DIR__ . '/../resources/js/Components/VortexPanel' => resource_path('js/Components/VortexPanel'),
            __DIR__ . '/../resources/css/vortexpanel.css' => resource_path('css/vortexpanel.css'),
        ], 'vortexpanel-ui');
    }
}
