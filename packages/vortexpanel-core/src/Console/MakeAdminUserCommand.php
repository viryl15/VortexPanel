<?php

namespace VortexPanel\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use VortexPanel\Core\Support\PermissionManager;

class MakeAdminUserCommand extends Command
{
    protected $signature = 'vortexpanel:make-admin
        {--name= : Name for the admin user}
        {--email= : Email for the admin user}
        {--password= : Password (leave empty to generate)}
        {--role=admin : Role name to assign/create (Spatie)}
        {--guard=web : Auth guard (Spatie)}
        {--force : Update existing user if email already exists}
    ';

    protected $description = 'Create an admin user and assign VortexPanel access role/permission';

    public function handle(): int
    {
        $userModel = config('auth.providers.users.model', \App\Models\User::class);
        if (!is_string($userModel) || !class_exists($userModel)) {
            $this->error("User model not found. Check auth.providers.users.model.");
            return self::FAILURE;
        }

        $name = (string)($this->option('name') ?: $this->ask('Name', 'Admin'));
        $email = (string)($this->option('email') ?: $this->ask('Email', 'admin@demo.test'));

        $passwordOpt = $this->option('password');
        $password = is_string($passwordOpt) && $passwordOpt !== ''
            ? $passwordOpt
            : (string)($this->secret('Password (leave empty to generate)') ?? '');

        if (trim($password) === '') {
            $password = Str::password(16);
            $this->warn("Generated password: {$password}");
        }

        /** @var \Illuminate\Database\Eloquent\Model $user */
        $user = $userModel::query()->where('email', $email)->first();

        if ($user && !$this->option('force')) {
            $this->error("User {$email} already exists. Re-run with --force to update.");
            return self::FAILURE;
        }

        if (!$user) {
            $user = new $userModel();
        }

        $user->forceFill([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        // Optional: verify email if column exists
        try {
            $tmp = new $userModel();
            $table = method_exists($tmp, 'getTable') ? $tmp->getTable() : 'users';
            if (Schema::hasColumn($table, 'email_verified_at') && empty($user->email_verified_at)) {
                $user->forceFill(['email_verified_at' => now()]);
            }
        } catch (\Throwable) {
            // ignore
        }

        $user->save();

        $this->info("User saved: {$email}");

        // Spatie Permission assignment (best-effort)
        $pm = app(PermissionManager::class);

        if ($pm->enabled() && $pm->spatieTablesReady()) {
            $pm->ensureAccessPermissionAndAdminRole();

            if (method_exists($user, 'assignRole')) {
                $user->assignRole($pm->adminRole());
                $this->info("Assigned role '{$pm->adminRole()}' (includes '{$pm->accessPermission()}').");
            } else {
                $this->warn("User model doesn't have assignRole(). Add HasRoles trait.");
            }
        } else {
            $this->warn("Spatie tables missing (roles/permissions not assigned).");
        }
        /*
        $guard = (string)($this->option('guard') ?: 'web');
        $roleName = (string)($this->option('role') ?: 'admin');
        $accessPerm = (string)config('vortexpanel.access_permission', 'access admin');
        if (class_exists(Role::class) && class_exists(Permission::class)) {
            try {
                if (Schema::hasTable('roles') && Schema::hasTable('permissions')) {
                    $permission = Permission::findOrCreate($accessPerm, $guard);
                    $role = Role::findOrCreate($roleName, $guard);
                    $role->givePermissionTo($permission);

                    if (method_exists($user, 'assignRole')) {
                        $user->assignRole($role);
                        $this->info("Assigned role '{$roleName}' and permission '{$accessPerm}'.");
                    } else {
                        $this->warn("User model doesn't have assignRole(). Did you add HasRoles trait?");
                    }
                } else {
                    $this->warn("Spatie tables not found. Run: php artisan vendor:publish --provider=\"Spatie\\Permission\\PermissionServiceProvider\" && php artisan migrate");
                }
            } catch (\Throwable $e) {
                $this->warn("Spatie assignment failed: " . $e->getMessage());
            }
        } else {
            $this->warn("Spatie Permission not installed. Skipping role/permission assignment.");
        }
        */

        $adminUrl = rtrim((string)config('app.url', 'http://localhost'), '/') . '/' . ltrim((string)config('vortexpanel.path', 'admin'), '/');
        $this->line("Login URL: {$adminUrl}");
        $this->line("Email: {$email}");
        $this->line("Password: {$password}");

        return self::SUCCESS;
    }
}
