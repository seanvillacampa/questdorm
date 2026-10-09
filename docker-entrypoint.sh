#!/bin/bash
set -e

echo "Starting QuestDorm application..."

# Wait for database to be ready
echo "Waiting for database connection..."
php artisan tinker --execute="DB::connection()->getPdo();" 2>/dev/null || {
    echo "Waiting for database..."
    sleep 5
}

# Fix permissions at runtime (in case volumes are mounted)
echo "Setting storage permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Cache configuration for performance
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create storage link if it doesn't exist
echo "Creating storage link..."
php artisan storage:link || true

echo "QuestDorm application started successfully!"

# Start Apache
exec apache2-foreground
