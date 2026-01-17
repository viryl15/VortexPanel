#!/usr/bin/env bash
set -euo pipefail

# VortexPanel demo setup script (Laravel 10+)
# - Creates apps/demo (Laravel)
# - Installs Breeze (Inertia + Vue)
# - Adds local path repositories for VortexPanel packages
# - Installs Spatie Permission (migrations + config) and migrates
# - Runs vortexpanel:install (publishes UI/config, scaffolds UserResource + UserPolicy, patches CSS import + HasRoles)

# If running inside WSL1, Node tooling may break (interop). Prefer WSL2 or Docker.
if grep -qi microsoft /proc/version 2>/dev/null; then
  if ! uname -r | grep -qi 'WSL2'; then
    echo "ERROR: Detected WSL 1. Node/Vite tooling is unreliable in WSL1." >&2
    echo "Please upgrade to WSL 2 or use Docker Compose (recommended)." >&2
    echo "See README.md (Docker workflow)." >&2
    exit 1
  fi
fi

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEMO_DIR="$ROOT_DIR/apps/demo"

LARAVEL_VERSION="${LARAVEL_VERSION:-^10.0}"

echo "==> Creating demo app (Laravel ${LARAVEL_VERSION}) in: $DEMO_DIR"

if [ -d "$DEMO_DIR/vendor" ] || [ -f "$DEMO_DIR/artisan" ]; then
  echo "Demo folder already looks like a Laravel app. Skipping create-project."
else
  rm -rf "$DEMO_DIR"
  mkdir -p "$ROOT_DIR/apps"
  (cd "$ROOT_DIR/apps" && composer create-project laravel/laravel demo "$LARAVEL_VERSION")
fi

cd "$DEMO_DIR"

echo "==> Configuring SQLite for demo (no external DB required)"
mkdir -p database
touch database/database.sqlite
php -r '
$envPath = __DIR__ . "/.env";
if (!file_exists($envPath)) { exit(0); }
$env = file_get_contents($envPath);
$replacements = [
  "DB_CONNECTION" => "sqlite",
  "DB_DATABASE" => "database/database.sqlite",
  "DB_HOST" => "127.0.0.1",
  "DB_PORT" => "3306",
  "DB_USERNAME" => "root",
  "DB_PASSWORD" => "",
];
foreach ($replacements as $k => $v) {
  if (preg_match("/^{$k}=.*$/m", $env)) {
    $env = preg_replace("/^{$k}=.*$/m", "{$k}={$v}", $env);
  } else {
    $env .= "\n{$k}={$v}";
  }
}
file_put_contents($envPath, $env);
'

if ! command -v node >/dev/null 2>&1 || ! command -v npm >/dev/null 2>&1; then
  echo "ERROR: node/npm not found in this environment." >&2
  echo "Install Node.js (recommended: Node 20 LTS) OR run via Docker Compose." >&2
  exit 1
fi

echo "==> Installing Breeze (Inertia + Vue)"
composer require laravel/breeze --dev
php artisan breeze:install vue --no-interaction

echo "==> Adding local path repositories for VortexPanel packages"
php -r '
$path = __DIR__ . "/composer.json";
$j = json_decode(file_get_contents($path), true);
$repos = $j["repositories"] ?? [];
$wanted = [
  ["type"=>"path","url"=>"../../packages/vortexpanel"],
  ["type"=>"path","url"=>"../../packages/vortexpanel-core"],
  ["type"=>"path","url"=>"../../packages/vortexpanel-ui"],
];
foreach ($wanted as $w) {
  $exists = false;
  foreach ($repos as $r) {
    if (($r["type"]??"")==="path" && ($r["url"]??"")===$w["url"]) { $exists=true; break; }
  }
  if (!$exists) $repos[] = $w;
}
$j["repositories"] = $repos;
file_put_contents($path, json_encode($j, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)."\n");
'

echo "==> Allowing dev stability for local packages"
composer config minimum-stability dev
composer config prefer-stable true

echo "==> Requiring VortexPanel meta package"
composer require vortexpanel/vortexpanel:@dev -W

echo "==> Publishing Spatie Permission assets (config + migrations)"
# NOTE: Spatie does not publish under tag 'migrations'. Publishing without tag is most reliable.
php artisan vendor:publish --provider="Spatie\\Permission\\PermissionServiceProvider" --force || true

echo "==> Migrating (includes Spatie Permission tables)"
php artisan migrate

echo "==> Installing VortexPanel into demo"
php artisan vortexpanel:install

echo "==> Installing node deps + building"
npm install
npm run build

echo "Done. If you are using Docker Compose, run from repo root:"
echo "  docker compose up -d app vite"
echo "Then open: http://localhost:8000/admin"
