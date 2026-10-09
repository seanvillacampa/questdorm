FROM php:8.4-apache

# Install system packages and PHP extensions
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpq-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    libpng-dev \
    zip \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql zip mbstring xml \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Enable Apache rewrite
RUN a2enmod rewrite

# Make Apache use port 10000 (Render default)
RUN sed -i 's/Listen 80/Listen 10000/g' /etc/apache2/ports.conf \
    && sed -i 's/<VirtualHost \*:80>/<VirtualHost *:10000>/g' \
    /etc/apache2/sites-available/000-default.conf

# Set Laravel public as document root
RUN sed -i 's|/var/www/html|/var/www/html/public|g' \
    /etc/apache2/sites-available/000-default.conf \
    && sed -i 's|/var/www/html|/var/www/html/public|g' \
    /etc/apache2/apache2.conf

# Allow .htaccess for Laravel
RUN printf '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>\n' \
    > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# Install Node.js
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy Laravel app
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Install frontend dependencies and build assets
RUN npm install && npm run build

# Clear Laravel caches
RUN php artisan config:clear \
    && php artisan route:clear \
    && php artisan view:clear

# Create storage symlink
RUN php artisan storage:link || true

# Fix permissions
RUN mkdir -p \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache \
    public/uploads \
    && chown -R www-data:www-data \
    storage \
    bootstrap/cache \
    public/uploads \
    && chmod -R 775 \
    storage \
    bootstrap/cache \
    public/uploads

# Expose port
EXPOSE 10000

# Create inline start script with better debugging
RUN printf '#!/bin/bash\n\
set -e\n\
echo "========================================"\n\
echo "Starting QuestDorm application..."\n\
echo "========================================"\n\
echo ""\n\
echo "Step 1: Fixing storage permissions..."\n\
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache 2>&1 || echo "  Warning: Could not change ownership"\n\
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>&1 || echo "  Warning: Could not change permissions"\n\
mkdir -p /var/www/html/storage/logs 2>&1 || echo "  Warning: Could not create logs directory"\n\
touch /var/www/html/storage/logs/laravel.log 2>&1 || echo "  Warning: Could not create log file"\n\
chown www-data:www-data /var/www/html/storage/logs/laravel.log 2>&1 || echo "  Warning: Could not change log file ownership"\n\
chmod 664 /var/www/html/storage/logs/laravel.log 2>&1 || echo "  Warning: Could not change log file permissions"\n\
echo "  ✓ Storage permissions configured"\n\
echo ""\n\
echo "Step 2: Testing database connection..."\n\
php artisan tinker --execute="try { DB::connection()->getPdo(); echo \"  ✓ Database connected successfully\"; } catch (Exception \\$e) { echo \"  ✗ Database connection failed: \" . \\$e->getMessage(); }" 2>&1 || echo "  ✗ Could not test database connection"\n\
echo ""\n\
echo "Step 3: Running migrations..."\n\
php artisan migrate --force 2>&1 && echo "  ✓ Migrations completed" || { \n\
  echo "  ✗ Migration attempt 1 failed, waiting 5 seconds...";\n\
  sleep 5;\n\
  echo "  Retrying migrations...";\n\
  php artisan migrate --force 2>&1 && echo "  ✓ Migrations completed on retry" || echo "  ✗ Migrations failed - will need manual intervention";\n\
}\n\
echo ""\n\
echo "Step 4: Caching configuration..."\n\
php artisan config:cache 2>&1 && echo "  ✓ Config cached" || echo "  ✗ Config cache failed"\n\
php artisan route:cache 2>&1 && echo "  ✓ Routes cached" || echo "  ✗ Route cache failed"\n\
php artisan view:cache 2>&1 && echo "  ✓ Views cached" || echo "  ✗ View cache failed"\n\
echo ""\n\
echo "========================================"\n\
echo "QuestDorm startup complete!"\n\
echo "========================================"\n\
echo ""\n\
exec apache2-foreground\n' > /start.sh \
    && chmod +x /start.sh

CMD ["/start.sh"]