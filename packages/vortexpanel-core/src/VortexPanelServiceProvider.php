<?php

namespace VortexPanel\Core;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use VortexPanel\Core\Console\DoctorCommand;
use VortexPanel\Core\Console\InstallCommand;
use VortexPanel\Core\Console\MakeAdminUserCommand;
use VortexPanel\Core\Console\MakeResourceCommand;
use VortexPanel\Core\Console\SyncPermissionsCommand;
use VortexPanel\Core\Console\VortexPanelCommand;
use VortexPanel\Core\Http\Middleware\VortexPanelAccess;
use VortexPanel\Core\Support\PermissionManager;
use VortexPanel\Core\Support\ResourceRegistry;

class VortexPanelServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/vortexpanel.php', 'vortexpanel');

        $this->app->singleton(ResourceRegistry::class, fn() => new ResourceRegistry());
        $this->app->singleton(PermissionManager::class, fn() => new PermissionManager());

        $this->commands([
            InstallCommand::class,
            VortexPanelCommand::class,
            MakeAdminUserCommand::class,
            MakeResourceCommand::class,
            SyncPermissionsCommand::class,
            DoctorCommand::class,
        ]);
    }

    public function boot(Router $router): void
    {
        $this->publishes([
            __DIR__ . '/../config/vortexpanel.php' => config_path('vortexpanel.php'),
        ], 'vortexpanel-config');

        // Route middleware alias
        $router->aliasMiddleware('vortexpanel.access', VortexPanelAccess::class);

        // Load API routes
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // Register resources from config
        $resources = (array) config('vortexpanel.resources', []);
        foreach ($resources as $resourceClass) {
            if (is_string($resourceClass) && class_exists($resourceClass)) {
                $this->app->make(ResourceRegistry::class)->register($resourceClass);
            }
        }

        // Optional: ensure policies are mapped (Laravel can auto-discover too)
        // Users can also register policies in App\Providers\AuthServiceProvider.
        // This is intentionally minimal.
    }
}
