<?php

namespace VortexPanel\Core\Console;

use Illuminate\Console\Command;

class VortexPanelCommand extends Command
{
    protected $signature = 'vortexpanel';

    protected $description = 'VortexPanel CLI hub (install, doctor, make-admin, make-resource)';

    public function handle(): int
    {
        $this->line('');
        $this->info('🌀 VortexPanel CLI');
        $this->line('Fast, futuristic, open-source admin panel for Laravel.');
        $this->line('');

        $choice = $this->choice('Choose an action', [
            'Install / Publish assets (vortexpanel:install)',
            'Create admin user (vortexpanel:make-admin)',
            'Generate resource (vortexpanel:make-resource)',
            'Sync permissions (vortexpanel:sync-permissions)',
            'Doctor / Fix common issues (vortexpanel:doctor)',
            'Exit',
        ], 0);

        return match ($choice) {
            'Install / Publish assets (vortexpanel:install)' => $this->call('vortexpanel:install'),
            'Create admin user (vortexpanel:make-admin)' => $this->call('vortexpanel:make-admin'),
            'Generate resource (vortexpanel:make-resource)' => $this->call('vortexpanel:make-resource', [
                'name' => $this->ask('Resource name (e.g. Post, Invoice, Product)', 'Post'),
            ]),
            'Sync permissions (vortexpanel:sync-permissions)' => $this->call('vortexpanel:sync-permissions'),
            'Doctor / Fix common issues (vortexpanel:doctor)' => $this->call('vortexpanel:doctor'),
            default => self::SUCCESS,
        };
    }
}
