# VortexPanel Windows setup (PowerShell)
# - Prefers Docker Compose (recommended, works on Windows + WSL1)
# - Falls back to WSL (requires WSL2 + node/npm inside WSL)

$ErrorActionPreference = "Stop"

$root = Resolve-Path (Join-Path $PSScriptRoot "..")

Write-Host "==> VortexPanel setup" -ForegroundColor Cyan
Write-Host "Root: $root" -ForegroundColor DarkGray

function HasCommand([string]$name) {
  return [bool](Get-Command $name -ErrorAction SilentlyContinue)
}

if (HasCommand "docker") {
  Write-Host "==> Running setup inside Docker (recommended on Windows)..." -ForegroundColor Cyan
  docker compose --project-directory "$root" --profile setup run --rm setup

  Write-Host "==> Starting dev servers (PHP + Vite)..." -ForegroundColor Cyan
  docker compose --project-directory "$root" up -d app vite

  Write-Host "" 
  Write-Host "Open:" -ForegroundColor Green
  Write-Host "  Admin: http://localhost:8000/admin"
  Write-Host "  Vite : http://localhost:5173" 
  Write-Host "" 
  Write-Host "Stop with: docker compose --project-directory `"$root`" down" -ForegroundColor DarkGray
  exit 0
}

Write-Host "Docker not found. Falling back to WSL..." -ForegroundColor Yellow

if (-not (HasCommand "wsl")) {
  throw "Neither Docker nor WSL were found. Please install Docker Desktop or WSL2."
}

# Convert Windows path to WSL path
$rootUnix = (wsl wslpath -a "$root").Trim()

Write-Host "==> Running setup script in WSL: $rootUnix" -ForegroundColor Cyan
wsl bash -lc "cd '$rootUnix' && chmod +x scripts/setup-demo.sh && ./scripts/setup-demo.sh"

Write-Host "" 
Write-Host "Done." -ForegroundColor Green
Write-Host "Run inside WSL:" -ForegroundColor Green
Write-Host "  cd '$rootUnix/apps/demo'"
Write-Host "  php artisan serve"
Write-Host "  npm run dev"
