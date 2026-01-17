<?php

namespace VortexPanel\Core\Support;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionManager
{
    public function enabled(): bool
    {
        return (bool) config('vortexpanel.permissions.enabled', true)
            && class_exists(Permission::class)
            && class_exists(Role::class);
    }

    public function guard(): string
    {
        return (string) config('vortexpanel.permissions.guard', 'web');
    }

    public function adminRole(): string
    {
        return (string) config('vortexpanel.permissions.admin_role', 'admin');
    }

    public function accessPermission(): string
    {
        return (string) config('vortexpanel.access_permission', 'vortexpanel.access');
    }

    public function spatieTablesReady(): bool
    {
        return Schema::hasTable('permissions') && Schema::hasTable('roles');
    }

    public function ensureAccessPermissionAndAdminRole(): void
    {
        if (!$this->enabled() || !$this->spatieTablesReady()) {
            return;
        }

        $guard = $this->guard();

        $access = Permission::findOrCreate($this->accessPermission(), $guard);
        $admin = Role::findOrCreate($this->adminRole(), $guard);

        $admin->givePermissionTo($access);
    }

    public function ensureCrudPermissions(string $resourceKey): array
    {
        if (!$this->enabled() || !$this->spatieTablesReady()) {
            return [];
        }

        $guard = $this->guard();
        $abilities = (array) config('vortexpanel.permissions.crud_abilities', ['viewAny', 'view', 'create', 'update', 'delete']);

        $created = [];

        foreach ($abilities as $ability) {
            $name = "{$resourceKey}.{$ability}";
            Permission::findOrCreate($name, $guard);
            $created[] = $name;
        }

        if ((bool) config('vortexpanel.permissions.assign_to_admin_role', true)) {
            $admin = Role::findOrCreate($this->adminRole(), $guard);
            $admin->givePermissionTo($created);
        }

        // always ensure panel access exists
        $this->ensureAccessPermissionAndAdminRole();

        return $created;
    }

    public function resourceKeyFromModel(string $modelFqn): string
    {
        return Str::snake(Str::pluralStudly(class_basename($modelFqn)));
    }
}
