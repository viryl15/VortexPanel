# VortexPanel Documentation (Laravel 10+ • Vue 3 • Inertia)

VortexPanel is built to be the **fastest** and most **futuristic** open-source admin panel for Laravel.

**Non-negotiables:**
- No Livewire dependency.
- Server-side tables only (pagination/filter/sort/search).
- Cache stable metadata (menu, permissions map).
- Policies-first authorization (with permission fallback).

Repo layout:
```
VortexPanel/
  apps/
    demo/                  # Demo app created via script
  packages/
    vortexpanel/           # Meta package
    vortexpanel-core/      # Core backend
    vortexpanel-ui/        # UI (Inertia pages/components published into host)
```

## Install into a Laravel project

```bash
composer require vortexpanel/vortexpanel
php artisan vortexpanel:install
php artisan migrate
npm install
npm run build
```

Admin URL: `/admin` (configurable)

## Development (demo)

```bash
./scripts/setup-demo.sh
cd apps/demo
php artisan serve
npm run dev
```

## Performance budgets
- Common list pages should target **<150ms** server time locally.
- Avoid contains-search (`%term%`) on large tables; prefer prefix (`term%`) + indexes.

## Resource system
Resources live in the host project at:
- `app/VortexPanel/Resources/*Resource.php`

The installer ships a sample `UserResource`.

## Security
- `/admin` is protected by `auth` + `vortexpanel.access` middleware.
- Access controlled via **Policies** and/or the `vortexpanel.access` permission.

