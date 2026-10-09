#!/bin/bash
set -e

echo "Starting QuestDorm application..."

# Fix permissions at runtime (in case volumes are mounted)
echo "Setting storage permissions..."
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# Wait for database to be ready with retry logic
echo "Waiting for database connection..."
max_attempts=30
attempt=0
until php artisan migrate --force 2>/dev/null || [ $attempt -eq $max_attempts ]; do
    attempt=$((attempt+1))
    echo "Database not ready, waiting... (attempt $attempt/$max_attempts)"
    sleep 2
done

if [ $attempt -eq $max_attempts ]; then
    echo "ERROR: Could not connect to database after $max_attempts attempts"
    echo "Starting Apache anyway (migrations can be run manually)..."
else
    echo "Database migrations completed successfully!"
fi

# Clear any existing caches
echo "Clearing caches..."
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# Cache configuration for performance (only if migrations succeeded)
if [ $attempt -lt $max_attempts ]; then
    echo "Caching configuration..."
    php artisan config:cache 2>/dev/null || true
    php artisan route:cache 2>/dev/null || true
    php artisan view:cache 2>/dev/null || true
fi

# Create storage link if it doesn't exist
echo "Creating storage link..."
php artisan storage:link 2>/dev/null || true

echo "QuestDorm application started successfully!"

# Start Apache
exec apache2-foreground
