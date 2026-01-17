<?php

namespace VortexPanel\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class InstallCommand extends Command
{
    protected $signature = 'vortexpanel:install {--force : Overwrite published files and stubs}';

    protected $description = 'Install VortexPanel (publish config + UI, scaffold sample resources/policies)';

    public function handle(Filesystem $fs): int
    {
        $force = (bool) $this->option('force');

        $this->info('Publishing VortexPanel config...');
        $this->call('vendor:publish', [
            '--tag' => 'vortexpanel-config',
            '--force' => $force,
        ]);

        // Publish UI assets if UI package is installed
        if (class_exists('VortexPanel\\UI\\VortexPanelUiServiceProvider')) {
            $this->info('Publishing VortexPanel UI (Pages/Components/CSS)...');
            $this->call('vendor:publish', [
                '--tag' => 'vortexpanel-ui',
                '--force' => $force,
            ]);
        } else {
            $this->warn('UI package not detected. Did you install vortexpanel/vortexpanel-ui?');
        }

        $this->info('Ensuring default role/permission (Spatie Permission)...');
        $this->ensureSpatieRolePermission();

        // Best-effort: ensure VortexPanel CSS import order is valid (Vite requires @import first)
        $this->ensureVortexCssImport($fs);

        // Best-effort: ensure User model has HasRoles (Spatie)
        $this->ensureHasRolesTrait($fs);

        // Scaffold a sample UserResource + UserPolicy
        $this->info('Scaffolding sample Resource + Policy...');
        $resourceDir = app_path('VortexPanel/Resources');
        $policyDir = app_path('Policies');
        $fs->ensureDirectoryExists($resourceDir);
        $fs->ensureDirectoryExists($policyDir);

        $resourcePath = $resourceDir . '/UserResource.php';
        $policyPath = $policyDir . '/UserPolicy.php';

        $packageRoot = dirname(__DIR__, 2); // .../packages/vortexpanel-core
        $resourceStub = $packageRoot . '/stubs/UserResource.stub';
        $policyStub = $packageRoot . '/stubs/UserPolicy.stub';

        if ($force || !$fs->exists($resourcePath)) {
            $fs->put($resourcePath, $fs->get($resourceStub));
            $this->info('Created app/VortexPanel/Resources/UserResource.php');
        }

        if ($force || !$fs->exists($policyPath)) {
            $fs->put($policyPath, $fs->get($policyStub));
            $this->info('Created app/Policies/UserPolicy.php');
        }

        // Best-effort: add UserResource to config/vortexpanel.php resources list
        $configPath = config_path('vortexpanel.php');
        if ($fs->exists($configPath)) {
            $cfg = $fs->get($configPath);
            $needle = 'App\\VortexPanel\\Resources\\UserResource::class';
            if (!Str::contains($cfg, $needle)) {
                $cfg2 = preg_replace(
                    "/'resources'\s*=>\s*\[(\s*)/m",
                    "'resources' => [\\1        {$needle},\\1",
                    $cfg,
                    1
                );
                if (is_string($cfg2) && $cfg2 !== $cfg) {
                    $fs->put($configPath, $cfg2);
                    $this->info('Registered UserResource in config/vortexpanel.php');
                }
            }
        }

        $this->line('');
        $this->info('VortexPanel installed. Next steps:');
        $this->line('- Ensure Spatie Permission is installed and migrated (if you use roles/permissions):');
        $this->line('  php artisan vendor:publish --provider="Spatie\\Permission\\PermissionServiceProvider"');
        $this->line('  php artisan migrate');
        $this->line('- Build assets: npm install && npm run dev (or npm run build)');
        $this->line('- Open /admin');

        return self::SUCCESS;
    }

    protected function ensureSpatieRolePermission(): void
    {
        if (!class_exists(Permission::class) || !class_exists(Role::class)) {
            $this->warn('Spatie Permission not detected. Skipping default role/permission bootstrap.');
            return;
        }

        try {
            if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) {
                $this->warn('Spatie Permission tables not found (permissions/roles).');
                $this->warn('Run: php artisan vendor:publish --provider="Spatie\\Permission\\PermissionServiceProvider" && php artisan migrate');
                return;
            }
        } catch (\Throwable $e) {
            $this->warn('Could not check Spatie Permission tables: ' . $e->getMessage());
            return;
        }

        try {
            $guard = config('vortexpanel.guard', 'web');
            $permName = config('vortexpanel.access_permission', 'access admin');

            $permission = Permission::findOrCreate($permName, $guard);
            $role = Role::findOrCreate('admin', $guard);
            $role->givePermissionTo($permission);
        } catch (\Throwable $e) {
            $this->warn('Could not create role/permission automatically: ' . $e->getMessage());
            $this->warn('You can configure roles/permissions manually.');
        }
    }

    protected function ensureVortexCssImport(Filesystem $fs): void
    {
        $appCss = resource_path('css/app.css');
        $import = "@import './vortexpanel.css';";

        if (!$fs->exists($appCss)) {
            return;
        }

        $contents = $fs->get($appCss);
        if (Str::contains($contents, $import)) {
            return;
        }

        // Vite requires @import to appear before other statements (except @charset).
        if (preg_match('/^@charset\s+"[^"]+";\s*/', $contents, $m)) {
            $prefix = $m[0];
            $rest = substr($contents, strlen($prefix));
            $new = rtrim($prefix) . "\n" . $import . "\n\n" . ltrim($rest);
        } else {
            $new = $import . "\n\n" . ltrim($contents);
        }

        $fs->put($appCss, $new);
        $this->info('Added VortexPanel CSS import to resources/css/app.css (top-of-file)');
    }

    protected function ensureHasRolesTrait(Filesystem $fs): void
    {
        $userModel = app_path('Models/User.php');
        if (!$fs->exists($userModel)) {
            return;
        }

        $contents = $fs->get($userModel);
        if (Str::contains($contents, 'Spatie\\Permission\\Traits\\HasRoles') || Str::contains($contents, 'HasRoles')) {
            return;
        }

        // Add import after namespace block.
        $contents2 = preg_replace(
            '/(namespace\s+[^;]+;\s*\R\R)/',
            "$1use Spatie\\Permission\\Traits\\HasRoles;\n",
            $contents,
            1
        );

        if (!is_string($contents2) || $contents2 === $contents) {
            return;
        }

        // Add trait to the class "use" line (inside the class).
        $contents3 = preg_replace_callback('/^(\s{4}use\s+)([^;]+);/m', function ($m) {
            $prefix = $m[1];
            $list = array_map('trim', explode(',', $m[2]));
            if (!in_array('HasRoles', $list, true)) {
                $list[] = 'HasRoles';
            }
            return $prefix . implode(', ', $list) . ';';
        }, $contents2, 1);

        if (is_string($contents3)) {
            $fs->put($userModel, $contents3);
            $this->info('Updated app/Models/User.php to include HasRoles (Spatie)');
        }
    }
}
