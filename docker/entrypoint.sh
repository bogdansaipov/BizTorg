#!/usr/bin/env sh
set -eu

# ─── Wait for PostgreSQL ──────────────────────────────────────────────────────
echo "==> Waiting for PostgreSQL at ${DB_HOST:-postgres}:${DB_PORT:-5432}..."
until pg_isready \
    -h "${DB_HOST:-postgres}" \
    -p "${DB_PORT:-5432}" \
    -U "${DB_USERNAME:-biztorg}" \
    -d "${DB_DATABASE:-biztorg}" \
    >/dev/null 2>&1; do
    sleep 2
done
echo "==> PostgreSQL is ready."

# ─── Ensure required directories exist ───────────────────────────────────────
mkdir -p \
    /var/www/html/storage/app/public \
    /var/www/html/storage/framework/cache/data \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/framework/testing \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache

chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

chmod -R 775 \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache

# ─── Link public storage ─────────────────────────────────────────────────────
php artisan storage:link --force 2>/dev/null || true

# ─── Run migrations (only on the app container, not queue/reverb) ─────────────
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "==> Running database migrations..."
    php artisan migrate --force --no-interaction
    echo "==> Migrations complete."
    echo "==> Seeding roles..."
    php artisan db:seed --class=RolesTableSeeder --force --no-interaction
    echo "==> Roles seeded."
fi

# ─── Hand off to the container command (php-fpm, queue:work, reverb:start...) ─
echo "==> Starting: $*"
exec "$@"
