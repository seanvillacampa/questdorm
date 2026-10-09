# Deployment Fix Guide

## Current Issues

1. **Permission denied on log files** - Storage directory not writable
2. **Missing database table** - Migrations not running properly

## Solution

### Option 1: Quick Fix (If already deployed)

Connect to your Render service and run these commands:

```bash
# SSH into your Render container or use Render Shell
php artisan migrate --force
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Option 2: Proper Fix (Recommended)

This fix ensures migrations run automatically on each deployment:

1. **Files Updated:**
   - `Dockerfile` - Updated to use entrypoint script
   - `docker-entrypoint.sh` - New startup script that runs migrations

2. **Commit and Push:**

```bash
git add Dockerfile docker-entrypoint.sh DEPLOYMENT_FIX_GUIDE.md
git commit -m "Fix: Add entrypoint script for proper migration handling"
git push origin main
```

3. **Redeploy on Render:**
   - Render will automatically detect the changes and rebuild
   - The new entrypoint script will:
     - Wait for database connection
     - Fix storage permissions
     - Run migrations automatically
     - Cache configuration for performance

### Option 3: Manual Database Migration (Emergency)

If you need to run migrations manually right now:

**Connect to Clever Cloud Database:**

```bash
# Use the connection details from your .env file
mysql -h bio0zbdnyef0m1cqyjl6-mysql.services.clever-cloud.com \
  -u <username> -p bio0zbdnyef0m1cqyjl6
```

Then run the SQL from these migration files:
- `database/migrations/2026_10_07_070950_create_tenant_messages_table.php`
- `database/migrations/2026_10_07_091724_add_resolved_fields_to_tenant_messages_table.php`

**Or use Render Shell:**

```bash
# In Render dashboard, open Shell for your service
php artisan migrate --force
```

## Verification

After fixing, verify by:

1. **Check logs are writable:**
```bash
ls -la storage/logs/
```

2. **Check tables exist:**
```bash
php artisan tinker
>>> DB::table('tenant_messages')->count();
```

3. **Try logging in as owner** - Should work without errors

## Environment Variables Required

Ensure these are set in Render:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY=<your-app-key>`
- `DB_CONNECTION=mysql`
- `DB_HOST=<clever-cloud-host>`
- `DB_PORT=3306`
- `DB_DATABASE=<your-database>`
- `DB_USERNAME=<your-username>`
- `DB_PASSWORD=<your-password>`

## Troubleshooting

### Still getting permission errors?

Add this to your Render service settings:
- Go to Environment
- Add: `APACHE_RUN_USER=www-data`
- Add: `APACHE_RUN_GROUP=www-data`

### Migrations not running?

Check Render logs:
```
Look for "Running database migrations..." message
If it fails, check database connection details
```

### Database connection timeout?

Clever Cloud might need to be whitelisted for Render IP, or check:
- Database credentials are correct
- Database is accessible from external connections
- Connection string format is correct

## Quick Commands Reference

```bash
# Check migration status
php artisan migrate:status

# Run specific migration
php artisan migrate --path=/database/migrations/2026_10_07_070950_create_tenant_messages_table.php --force

# Fix permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Rebuild caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Next Steps

1. Push the updated Dockerfile and entrypoint script
2. Wait for Render to rebuild (this will take 5-10 minutes)
3. Check Render logs to confirm migrations ran successfully
4. Test login as owner

If you need immediate access, use Option 1 (Quick Fix) while waiting for the proper fix to deploy.
