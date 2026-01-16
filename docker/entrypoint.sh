#!/bin/sh
# =============================================================================
# Docker Entrypoint Script
# =============================================================================
# Initialize database, modules, and caches before starting services.

set -e

echo "=========================================="
echo "Tool Dock - Starting Application"
echo "=========================================="

# Wait for database to be ready (if DATABASE_URL or DB_HOST is set)
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database connection..."
    max_tries=30
    tries=0
    until pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "${DB_USERNAME:-postgres}" > /dev/null 2>&1; do
        tries=$((tries + 1))
        if [ $tries -gt $max_tries ]; then
            echo "Error: Database connection timeout after ${max_tries} attempts"
            exit 1
        fi
        echo "Waiting for database... attempt $tries/$max_tries"
        sleep 2
    done
    echo "Database is ready!"
fi

# Create storage symlink
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creating storage symlink..."
    php artisan storage:link --force 2>/dev/null || true
fi

# Clear caches
echo "Clearing caches..."
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true
php artisan event:clear 2>/dev/null || true

# Run migrations
if [ "$AUTO_MIGRATE" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force --no-interaction
    echo "Migrations completed!"
fi

# Discover modules
echo "Discovering modules..."
php artisan module:discover --no-interaction 2>/dev/null || true

# Install protected modules
echo "Checking protected modules installation..."
php artisan tinker --execute="
use App\Services\Modules\ModuleLifecycleService;
use Illuminate\Support\Facades\DB;

if (DB::table('modules_statuses')->where('is_installed', true)->doesntExist()) {
    echo 'Installing protected modules...';
    \$service = app(ModuleLifecycleService::class);
    \$installed = \$service->installProtectedModules();
    echo 'Installed: ' . implode(', ', \$installed);
} else {
    echo 'Protected modules already installed.';
}
" 2>/dev/null || true

# Seed module registries
echo "Seeding module registries..."
php artisan tinker --execute="
use App\Services\Registry\MenuRegistry;
use App\Services\Registry\SettingsRegistry;
use App\Services\Registry\PermissionRegistry;
use App\Services\Registry\CategoryRegistry;
use App\Services\Registry\GroupRegistry;
use App\Services\Registry\RoleRegistry;

app(MenuRegistry::class)->seed();
app(SettingsRegistry::class)->seed();
app(PermissionRegistry::class)->seed();
app(CategoryRegistry::class)->seed();
app(GroupRegistry::class)->seed();
app(RoleRegistry::class)->seed();

echo 'Registries seeded successfully.';
" 2>/dev/null || true

# Cache for production
if [ "$APP_ENV" = "production" ]; then
    echo "Caching configuration for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    echo "Configuration cached successfully!"
fi

echo "=========================================="
echo "Initialization complete, starting services"
echo "=========================================="

# Execute the main command
exec "$@"
