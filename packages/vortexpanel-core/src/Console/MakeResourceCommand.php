<?php

namespace VortexPanel\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use VortexPanel\Core\Support\PermissionManager;

class MakeResourceCommand extends Command
{
    protected $signature = 'vortexpanel:make-resource
        {name : Resource name (e.g. Post, Invoice, Product)}
        {--model= : Fully-qualified model class (default App\\Models\\Name)}
        {--slug= : Override slug (default kebab plural, e.g. blog-posts)}
        {--label= : Override label (default Headline plural, e.g. Blog Posts)}
        {--force : Overwrite existing files}
        {--create-model : Create model if it does not exist}
        {--no-policy : Do NOT generate a policy}
        {--observer : Also generate an observer and auto-register it}
    ';

    protected $description = 'Generate a VortexPanel Resource (and optional Policy/Observer)';

    public function handle(Filesystem $fs): int
    {
        $name = Str::studly((string)$this->argument('name'));
        $model = (string)($this->option('model') ?: "App\\Models\\{$name}");
        $slug  = (string)($this->option('slug') ?: Str::kebab(Str::pluralStudly($name)));
        $label = (string)($this->option('label') ?: Str::headline(Str::pluralStudly($name)));

        if (!class_exists($model)) {
            if ($this->option('create-model')) {
                $this->call('make:model', ['name' => $name]);
            } else {
                $this->warn("Model class not found: {$model}");
                $this->warn("Tip: re-run with --create-model or pass --model=...");
            }
        }

        $resourceClass = "{$name}Resource";
        $policyClass   = "{$name}Policy";
        $observerClass = "{$name}Observer";

        $resourcePath = app_path("VortexPanel/Resources/{$resourceClass}.php");
        $policyPath   = app_path("Policies/{$policyClass}.php");
        $observerPath = app_path("Observers/{$observerClass}.php");

        $force = (bool)$this->option('force');

        // Ensure dirs
        $fs->ensureDirectoryExists(dirname($resourcePath));
        if (!$this->option('no-policy')) {
            $fs->ensureDirectoryExists(dirname($policyPath));
        }
        if ($this->option('observer')) {
            $fs->ensureDirectoryExists(dirname($observerPath));
        }

        // Build smart defaults (best-effort)
        [$searchable, $columns, $form] = $this->guessDefaults($model);

        // Render Resource from stub
        $stub = $this->readStub('Resource.stub');
        $resourcePhp = str_replace([
            '{{ model_import }}',
            '{{ model_short }}',
            '{{ resource_class }}',
            '{{ slug }}',
            '{{ label }}',
            '{{ searchable }}',
            '{{ columns }}',
            '{{ form }}',
        ], [
            $model,
            class_exists($model) ? class_basename($model) : 'Model',
            $resourceClass,
            $slug,
            $label,
            $this->exportArray($searchable),
            $this->exportArray($columns),
            $this->exportArray($form),
        ], $stub);

        if (!$fs->exists($resourcePath) || $force) {
            $fs->put($resourcePath, $resourcePhp);
            $this->info("Created: {$resourcePath}");
        } else {
            $this->warn("Skipped (exists): {$resourcePath}");
        }

        // Policy (default ON)
        if (!$this->option('no-policy')) {
            $policyStub = $this->readStub('Policy.stub');
            $pm = app(\VortexPanel\Core\Support\PermissionManager::class);
            $resourceKey = $pm->resourceKeyFromModel($model);
            $policyPhp = str_replace([
                '{{ model_import }}',
                '{{ model_short }}',
                '{{ policy_class }}',
                '{{ resource_key }}',
            ], [
                $model,
                class_exists($model) ? class_basename($model) : 'Model',
                $policyClass,
                $resourceKey,
            ], $policyStub);

            if (!$fs->exists($policyPath) || $force) {
                $fs->put($policyPath, $policyPhp);
                $this->info("Created: {$policyPath}");
            } else {
                $this->warn("Skipped (exists): {$policyPath}");
            }
        }

        // Observer (optional)
        if ($this->option('observer')) {
            $observerStub = $this->readStub('Observer.stub');
            $observerPhp = str_replace([
                '{{ model_import }}',
                '{{ model_short }}',
                '{{ observer_class }}',
            ], [
                $model,
                class_exists($model) ? class_basename($model) : 'Model',
                $observerClass,
            ], $observerStub);

            if (!$fs->exists($observerPath) || $force) {
                $fs->put($observerPath, $observerPhp);
                $this->info("Created: {$observerPath}");
            } else {
                $this->warn("Skipped (exists): {$observerPath}");
            }

            $this->tryRegisterObserver($fs, $model, "App\\Observers\\{$observerClass}");
        }

        // Auto-create CRUD permissions (dots) for this resource
        if ((bool) config('vortexpanel.permissions.create_on_make_resource', true)) {
            $pm = app(PermissionManager::class);

            $resourceKey = $pm->resourceKeyFromModel($model);
            $created = $pm->ensureCrudPermissions($resourceKey);

            if ($created === []) {
                $this->warn("Permissions not created (Spatie not installed or tables missing).");
            } else {
                $this->info("Created/ensured permissions: " . implode(', ', $created));
            }
        }

        // Register resource into config/vortexpanel.php
        $this->registerInConfig($fs, "App\\VortexPanel\\Resources\\{$resourceClass}");

        $this->line('');
        $this->info('Next: build assets if needed, then open /admin.');
        return self::SUCCESS;
    }

    private function guessDefaults(string $model): array
    {
        // Fallback defaults
        $searchable = [];
        $columns = [
            ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ['key' => 'created_at', 'label' => 'Created', 'sortable' => true],
        ];
        $form = [];

        if (!class_exists($model)) {
            return [$searchable, $columns, $form];
        }

        try {
            $instance = new $model();
            $table = method_exists($instance, 'getTable') ? $instance->getTable() : null;

            if (!$table || !Schema::hasTable($table)) {
                return [$searchable, $columns, $form];
            }

            // Prefer common fields if they exist
            $candidates = [
                ['name', 'text', 'Name'],
                ['title', 'text', 'Title'],
                ['email', 'email', 'Email'],
            ];

            $cols = [
                ['key' => 'id', 'label' => 'ID', 'sortable' => true],
            ];

            foreach ($candidates as [$key, $type, $label]) {
                if (Schema::hasColumn($table, $key)) {
                    $cols[] = ['key' => $key, 'label' => $label, 'sortable' => true];
                    $searchable[] = $key;
                    $form[] = ['key' => $key, 'type' => $type, 'label' => $label, 'required' => true];
                }
            }

            if (Schema::hasColumn($table, 'created_at')) {
                $cols[] = ['key' => 'created_at', 'label' => 'Created', 'sortable' => true];
            }

            if (count($cols) > 0) {
                $columns = $cols;
            }

            return [$searchable, $columns, $form];
        } catch (\Throwable) {
            return [$searchable, $columns, $form];
        }
    }

    private function registerInConfig(Filesystem $fs, string $resourceFqn): void
    {
        $configPath = config_path('vortexpanel.php');
        if (!$fs->exists($configPath)) {
            $this->warn("Config not found: {$configPath} (run vortexpanel:install first)");
            return;
        }

        $cfg = $fs->get($configPath);
        $needle = $resourceFqn . '::class';

        if (Str::contains($cfg, $needle)) {
            $this->info("Already registered in config: {$needle}");
            return;
        }

        $updated = preg_replace(
            "/'resources'\s*=>\s*\[(\s*)/m",
            "'resources' => [\\1        {$needle},\\1",
            $cfg,
            1
        );

        if (is_string($updated) && $updated !== $cfg) {
            $fs->put($configPath, $updated);
            $this->info("Registered resource in config/vortexpanel.php: {$needle}");
        } else {
            $this->warn("Could not auto-register resource. Add manually to config/vortexpanel.php:");
            $this->line("    {$needle},");
        }
    }

    private function tryRegisterObserver(Filesystem $fs, string $modelFqn, string $observerFqn): void
    {
        if (!class_exists($modelFqn)) {
            $this->warn("Observer not auto-registered: model missing ({$modelFqn})");
            return;
        }

        $appProvider = app_path('Providers/AppServiceProvider.php');
        if (!$fs->exists($appProvider)) {
            $this->warn("Observer not auto-registered: AppServiceProvider not found.");
            return;
        }

        $contents = $fs->get($appProvider);
        $line = "\\{$modelFqn}::observe(\\{$observerFqn}::class);";

        if (Str::contains($contents, $line)) {
            $this->info("Observer already registered in AppServiceProvider.");
            return;
        }

        // Ensure imports (simple: just use FQN line)
        $patched = preg_replace(
            '/public function boot\(\): void\s*\{\s*/',
            "public function boot(): void\n    {\n        {$line}\n\n",
            $contents,
            1
        );

        if (is_string($patched) && $patched !== $contents) {
            $fs->put($appProvider, $patched);
            $this->info("Auto-registered Observer in AppServiceProvider::boot()");
        } else {
            $this->warn("Could not patch AppServiceProvider. Register manually in boot():");
            $this->line("    {$line}");
        }
    }

    private function readStub(string $name): string
    {
        $packageRoot = dirname(__DIR__, 2); // packages/vortexpanel-core
        $path = $packageRoot . '/stubs/' . $name;

        if (!file_exists($path)) {
            throw new \RuntimeException("Stub not found: {$path}");
        }

        return (string)file_get_contents($path);
    }

    private function exportArray(array $value, int $level = 0): string
    {
        $indent = str_repeat('    ', $level);

        if ($value === []) {
            return '[]';
        }

        $out = "[\n";
        foreach ($value as $k => $v) {
            $out .= $indent . '    ';
            if (!is_int($k)) {
                $out .= var_export($k, true) . ' => ';
            }
            if (is_array($v)) {
                $out .= $this->exportArray($v, $level + 1);
            } else {
                $out .= var_export($v, true);
            }
            $out .= ",\n";
        }
        $out .= $indent . ']';
        return $out;
    }
}
