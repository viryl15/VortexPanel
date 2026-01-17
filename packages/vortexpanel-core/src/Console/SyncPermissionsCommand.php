<?php

namespace VortexPanel\Core\Console;

use Illuminate\Console\Command;
use VortexPanel\Core\Support\PermissionManager;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'vortexpanel:sync-permissions {--role= : Override admin role name}';
    protected $description = 'Ensure CRUD permissions exist for all VortexPanel resources (and assign to admin role)';

    public function handle(PermissionManager $pm): int
    {
        if (!$pm->enabled()) {
            $this->warn('Spatie Permission not installed or disabled.');
            return self::SUCCESS;
        }

        if (!$pm->spatieTablesReady()) {
            $this->error('Spatie tables missing. Run: php artisan vendor:publish --provider="Spatie\\Permission\\PermissionServiceProvider" && php artisan migrate');
            return self::FAILURE;
        }

        $resources = (array) config('vortexpanel.resources', []);
        if (empty($resources)) {
            $this->warn('No resources registered in config/vortexpanel.php');
            return self::SUCCESS;
        }

        $pm->ensureAccessPermissionAndAdminRole();

        foreach ($resources as $resourceClass) {
            if (!is_string($resourceClass) || !class_exists($resourceClass)) {
                continue;
            }

            $model = $resourceClass::$model ?? null;
            if (!is_string($model) || $model === '') {
                continue;
            }

            $resourceKey = $pm->resourceKeyFromModel($model);
            $created = $pm->ensureCrudPermissions($resourceKey);

            $this->info("{$resourceClass}: " . implode(', ', $created));
        }

        return self::SUCCESS;
    }
}
