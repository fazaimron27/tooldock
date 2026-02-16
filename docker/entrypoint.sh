#!/bin/sh
# =============================================================================
# Docker Entrypoint Script
# =============================================================================
# Initialize database and caches before starting services.
# Module discovery, installation, and registry seeding are handled
# automatically by the AutoInstallProtectedModules listener.

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

# Run migrations (triggers AutoInstallProtectedModules listener on fresh databases,
# which handles module discovery, protected module installation, and registry seeding)
if [ "$AUTO_MIGRATE" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --no-interaction
    echo "Migrations completed!"
fi

# Cache and warm for production
if [ "$APP_ENV" = "production" ]; then
    echo "Building production caches..."

    # Laravel framework caches
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache

    # Ziggy frontend route manifest (must run AFTER route:cache to capture final routes)
    php artisan ziggy:generate 2>/dev/null || true

    # Reset Spatie permission cache to clear stale data from previous containers
    # Permissions auto-warm on first access via Spatie's lazy loading
    php artisan permission:cache-reset 2>/dev/null || true

    echo "Production caches ready!"
fi

echo "=========================================="
echo "Initialization complete, starting services"
echo "=========================================="

# Execute the main command
exec "$@"
