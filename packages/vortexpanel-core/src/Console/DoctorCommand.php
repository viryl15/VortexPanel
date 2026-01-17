<?php

namespace VortexPanel\Core\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class DoctorCommand extends Command
{
    protected $signature = 'vortexpanel:doctor {--fix : Attempt to auto-fix common issues}';

    protected $description = 'Check common VortexPanel dev issues (Vite hot file, Spatie tables, etc.)';

    public function handle(): int
    {
        $fix = (bool)$this->option('fix');

        $this->info('VortexPanel Doctor');
        $this->line('');

        // 1) Vite hot file check
        $hot = public_path('hot');
        if (file_exists($hot)) {
            $url = trim((string)@file_get_contents($hot));

            $this->warn("Found public/hot (dev mode assets): {$url}");

            $reachable = $this->isHostReachableFromHotUrl($url);
            if (!$reachable) {
                $this->error("Vite dev server seems NOT reachable, which often causes a white page.");

                if ($fix) {
                    @unlink($hot);
                    $this->info("Deleted public/hot. Now run: npm run build (or start vite dev server).");
                } else {
                    $this->line("Fix: start vite (docker compose up -d vite) OR delete public/hot if using built assets.");
                }
            } else {
                $this->info("Vite dev server looks reachable ✅");
            }
        } else {
            $this->info("public/hot not present ✅ (production build mode)");
        }

        $this->line('');

        // 2) Spatie Permission tables check (optional)
        if (class_exists(\Spatie\Permission\Models\Permission::class)) {
            if (Schema::hasTable('permissions') && Schema::hasTable('roles')) {
                $this->info('Spatie Permission tables exist ✅');
            } else {
                $this->warn('Spatie Permission installed but tables missing.');
                
                if ($fix) {
                    $this->info('Publishing Spatie config and running migrations...');
                    $this->call('vendor:publish', [
                        '--provider' => 'Spatie\\Permission\\PermissionServiceProvider',
                        '--force' => true,
                    ]);
                    $this->call('migrate', ['--force' => true]);
                    $this->info('Spatie tables created ✅');
                } else {
                    $this->line('Fix: php artisan vortexpanel:doctor --fix');
                }
            }
        } else {
            $this->info('Spatie Permission not installed (OK if you don\'t need roles/permissions).');
        }

        $this->line('');

        $db = config('database.default');
        if ($db === 'sqlite') {
            $path = (string) config('database.connections.sqlite.database');
            if ($path !== ':memory:' && !file_exists($path)) {
                $this->error("SQLite database file missing: {$path}");
                if ($fix) {
                    @mkdir(dirname($path), 0775, true);
                    @touch($path);
                    $this->info("Created SQLite file: {$path}");
                    $this->info('Running migrations...');
                    $this->call('migrate', ['--force' => true]);
                }
            } else {
                $this->info("SQLite file OK ✅");
            }
        }

        $this->line('');
        $this->info('Doctor finished.');

        return self::SUCCESS;
    }

    private function isHostReachableFromHotUrl(string $url): bool
    {
        if ($url === '') {
            return false;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['host'])) {
            return false;
        }

        $host = (string)$parts['host'];
        $scheme = (string)($parts['scheme'] ?? 'http');
        $port = (int)($parts['port'] ?? ($scheme === 'https' ? 443 : 80));

        $errno = 0;
        $errstr = '';
        $fp = @fsockopen($host, $port, $errno, $errstr, 0.35);
        if ($fp) {
            fclose($fp);
            return true;
        }

        return false;
    }
}
