# VortexPanel

**VortexPanel** is a futuristic, ultra-fast, open-source admin panel for **Laravel 10+**.

Core goals:
- **Speed-first**: server-side tables, predictable queries, minimal JS overhead.
- **Futuristic UI**: clean dark-first interface, keyboard-first UX.
- **Package-first**: installable via Composer and one Artisan command.

This repo is a monorepo:
- `packages/vortexpanel-core` — backend core (routes, middleware, resource system, installer)
- `packages/vortexpanel-ui` — Inertia + Vue pages/components (published into the host app)
- `packages/vortexpanel` — meta package that requires core + ui
- `apps/demo` — local dev demo app (created via script)

## Quick start (demo)

```bash
./scripts/setup-demo.sh
```

> **Windows note (WSL1):** Laravel Breeze / Vite tooling is unreliable in WSL1.
> Use the Docker workflow below (recommended) or upgrade to WSL2.

Then in **apps/demo**:
```bash
php artisan vortexpanel:install
php artisan migrate
npm install
npm run dev
```

Open: `http://127.0.0.1:8000/admin`

## Docker workflow (recommended on Windows)

1) Run the setup once (creates `apps/demo`, installs deps, installs VortexPanel):

```bash
docker compose --profile setup run --rm setup
```

2) Start the dev servers:

```bash
docker compose up -d app vite
```

Open:
- Admin: `http://localhost:8000/admin`
- Vite: `http://localhost:5173`

Stop:

```bash
docker compose down
```

## Windows PowerShell setup

From PowerShell in repo root:

```powershell
./scripts/setup-demo.ps1
```

## License
MIT
