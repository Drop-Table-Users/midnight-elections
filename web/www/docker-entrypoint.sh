#!/bin/bash
set -e

echo "=== Laravel Docker Entrypoint ==="

# Check if composer dependencies are installed
if [ ! -d "/app/vendor" ] || [ ! -f "/app/vendor/autoload.php" ]; then
    echo "Installing Composer dependencies..."
    # Run as root to install, then fix permissions
    if [ "$(id -u)" = "82" ]; then
        # Running as www-data, can't install
        echo "ERROR: Composer dependencies not installed and running as www-data"
        echo "Please run 'cd web && composer install' before starting containers"
        exit 1
    fi
    composer install --no-dev --optimize-autoloader --no-interaction
    chown -R www-data:www-data /app/vendor
fi

# Wait for database file to be accessible
echo "Checking database..."
if [ ! -f /app/database/database.sqlite ]; then
    echo "Creating database file..."
    touch /app/database/database.sqlite
    chown www-data:www-data /app/database/database.sqlite
fi

# Function to check if migrations have been run
check_migrations() {
    php artisan migrate:status 2>&1 | grep -q "Migration table not found" && return 1
    php artisan migrate:status 2>&1 | grep -q "No migrations found" && return 1
    return 0
}

# Function to check if database has any tables
check_database_tables() {
    # Query SQLite to count tables
    table_count=$(sqlite3 /app/database/database.sqlite "SELECT COUNT(*) FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';" 2>/dev/null || echo "0")

    if [ "$table_count" -gt "0" ]; then
        return 0  # Tables exist
    else
        return 1  # No tables
    fi
}

# Check if we need to run migrations
echo "Checking migration status..."
if ! check_database_tables || ! check_migrations; then
    echo "Database not initialized. Running migrations..."

    # Generate app key if not set
    if grep -q "APP_KEY=$" /app/.env 2>/dev/null || [ ! -f /app/.env ]; then
        echo "Generating application key..."
        php artisan key:generate --force
    fi

    # Run migrations
    echo "Running migrations..."
    php artisan migrate --force

    # Check if seeders exist and should be run
    if [ -d "/app/database/seeders" ] && [ "$(ls -A /app/database/seeders/*.php 2>/dev/null)" ]; then
        echo "Running database seeders..."
        php artisan db:seed --force || echo "Warning: Seeding failed or no seeders found"
    fi

    echo "Database initialization complete!"
else
    echo "Database already initialized. Checking for pending migrations..."

    # Check for pending migrations
    if php artisan migrate:status 2>&1 | grep -q "Pending"; then
        echo "Found pending migrations. Running migrations..."
        php artisan migrate --force
    else
        echo "No pending migrations."
    fi
fi

# Clear and cache config for production
if [ "$APP_ENV" = "production" ]; then
    echo "Optimizing for production..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

# Clear caches for local development
if [ "$APP_ENV" = "local" ]; then
    echo "Clearing caches for development..."
    php artisan config:clear
    php artisan route:clear
    php artisan view:clear
fi

echo "=== Starting Application ==="
echo "Environment: $APP_ENV"
echo "URL: $APP_URL"
echo ""

# Execute the main command
exec "$@"
