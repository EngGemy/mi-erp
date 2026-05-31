#!/usr/bin/env bash
# Crown BOM — production deploy for shared hosting (no npm, no sudo, no symlinks).
# Run from the repository root on the server: bash deploy.sh
set -euo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_ROOT"

log_step() {
    echo ""
    echo "=========================================="
    echo ">>> $1"
    echo "=========================================="
}

log_step "Git: fetch and reset to origin/main"
git fetch origin main
git reset --hard origin/main
echo ">>> Deployed commit: $(git rev-parse --short HEAD) ($(git log -1 --format='%s'))"

log_step "Composer: install production dependencies"
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist

log_step "Database: migrate"
php artisan migrate --force

log_step "Clear all caches (before rebuild)"
php artisan optimize:clear

log_step "Filament: publish panel assets"
php artisan filament:assets

log_step "Storage: sync storage/app/public → public/storage (real directory)"
STORAGE_PUBLIC="public/storage"
SOURCE="storage/app/public"

# Host blocks symlinks — use a real directory; never wipe user media in public/storage.
if [ -L "$STORAGE_PUBLIC" ]; then
    echo ">>> Removing symlink at public/storage (host requires a real directory)"
    rm -f "$STORAGE_PUBLIC"
elif [ -e "$STORAGE_PUBLIC" ] && [ ! -d "$STORAGE_PUBLIC" ]; then
    echo ">>> Removing non-directory public/storage entry"
    rm -f "$STORAGE_PUBLIC"
fi

mkdir -p "$STORAGE_PUBLIC"
mkdir -p "$SOURCE"

if [ -d "$SOURCE" ]; then
    # cp -ru merges into destination; does not delete files only present in public/storage.
    if compgen -G "${SOURCE}/"* >/dev/null 2>&1; then
        cp -ru "${SOURCE}/." "${STORAGE_PUBLIC}/"
        echo ">>> Synced storage/app/public → public/storage"
    else
        echo ">>> storage/app/public is empty (copy skipped)"
    fi
fi

log_step "Permissions: storage, bootstrap/cache, public/storage"
chmod -R 775 storage bootstrap/cache 2>/dev/null || true
find storage -type d -exec chmod 775 {} + 2>/dev/null || true
find bootstrap/cache -type d -exec chmod 775 {} + 2>/dev/null || true
if [ -d "$STORAGE_PUBLIC" ]; then
    find "$STORAGE_PUBLIC" -type d -exec chmod 775 {} + 2>/dev/null || true
    find "$STORAGE_PUBLIC" -type f -exec chmod 644 {} + 2>/dev/null || true
fi

log_step "Rebuild caches"
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan filament:cache-components

log_step "Deploy finished successfully"
echo ">>> Crown BOM live at $(git rev-parse --short HEAD)"
