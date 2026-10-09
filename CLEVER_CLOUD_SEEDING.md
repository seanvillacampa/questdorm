# Seeding Clever Cloud Database

## Option 1: Via SSH (Recommended)

If your Clever Cloud app has SSH access:

```bash
# 1. SSH into your Clever Cloud instance
clever ssh

# 2. Run the seeder
php artisan db:seed --class=DatabaseSeeder

# Or fresh migration with seed
php artisan migrate:fresh --seed
```

## Option 2: Via Artisan Command on Deployment

Add a post-deploy hook to run seeders automatically.

### In `.clever.json`:
```json
{
  "hooks": {
    "postDeploy": "php artisan db:seed --class=DatabaseSeeder --force"
  }
}
```

**Note**: Use `--force` flag to skip confirmation in production.

## Option 3: Local Connection to Clever Cloud Database

Connect to your Clever Cloud MySQL database from your local machine and run the seeder locally.

### Step 1: Get Clever Cloud Database Credentials

From Clever Cloud dashboard, get:
- Host
- Port  
- Database name
- Username
- Password

### Step 2: Update `.env` file temporarily

```env
DB_CONNECTION=mysql
DB_HOST=your-clever-cloud-host.mysql.services.clever-cloud.com
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### Step 3: Run Seeder Locally

```bash
# Make sure you're pointing to Clever Cloud database
php artisan config:clear
php artisan db:seed --class=DatabaseSeeder
```

⚠️ **DANGER**: This will **delete all data** in your production database!

### Step 4: Restore Local `.env`

Change your `.env` back to local database settings after seeding.

## Option 4: Export SQL from Local and Import to Clever Cloud

This is safer as you can review the SQL before importing.

### Step 1: Seed your local database first

```bash
php artisan migrate:fresh --seed
```

### Step 2: Export the seeded data to SQL

```bash
# Export entire database
mysqldump -u root -p questdorm > seeded_database.sql

# Or export specific tables only (skip structure)
mysqldump -u root -p questdorm \
  --no-create-info \
  --skip-triggers \
  users roles model_has_roles tenants rooms contracts \
  invoices meter_readings payments tenant_payments \
  services service_prices customers laundry_orders order_items \
  detergent_inventory detergent_logs settings \
  > seeded_data_only.sql
```

### Step 3: Import to Clever Cloud via phpMyAdmin or MySQL Console

1. Go to Clever Cloud Dashboard
2. Open your MySQL add-on
3. Click "phpMyAdmin" or "MySQL Console"
4. Import the `seeded_database.sql` file

Or via command line:

```bash
mysql -h your-clever-cloud-host.mysql.services.clever-cloud.com \
      -P 3306 \
      -u your_username \
      -p your_database_name \
      < seeded_database.sql
```

## Option 5: Create a Custom Artisan Command

Create a command that only seeds if database is empty (safer for production).

```bash
php artisan make:command SeedIfEmpty
```

### `app/Console/Commands/SeedIfEmpty.php`

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedIfEmpty extends Command
{
    protected $signature = 'db:seed-if-empty';
    protected $description = 'Seed database only if it is empty';

    public function handle()
    {
        // Check if database has data
        $userCount = DB::table('users')->count();
        
        if ($userCount > 0) {
            $this->error('Database is not empty! Seeding aborted.');
            return 1;
        }

        $this->info('Database is empty. Starting seeding...');
        $this->call('db:seed', ['--class' => 'DatabaseSeeder', '--force' => true]);
        $this->info('Seeding completed!');
        
        return 0;
    }
}
```

Then run via SSH or deployment hook:

```bash
php artisan db:seed-if-empty
```

## Recommended Approach for Production

**Best practice for Clever Cloud:**

1. **Test locally first**:
   ```bash
   php artisan migrate:fresh --seed
   ```

2. **Export the SQL**:
   ```bash
   mysqldump -u root -p questdorm > production_seed.sql
   ```

3. **Review the SQL file** to ensure it's correct

4. **Import to Clever Cloud** via their MySQL console or phpMyAdmin

5. **Verify** by checking a few records in the database

## Important Notes

⚠️ **WARNINGS**:
- `migrate:fresh` will **DROP ALL TABLES** and recreate them
- `db:seed` with your current seeder will **TRUNCATE ALL TABLES** (see line 82-92 in DatabaseSeeder.php)
- Always backup your production database before seeding
- Test on a staging environment first if possible

## Safe Alternative: Conditional Seeding

If you want to seed only missing data without wiping existing data, modify your seeder:

```php
// In DatabaseSeeder.php, replace the wipe section with:
public function run(): void
{
    // Only seed if database is empty
    if (User::count() > 0) {
        $this->command->error('Database already has data. Seeding aborted.');
        return;
    }
    
    // ... rest of seeding code
}
```

## Check Database Connection

Before seeding, verify connection:

```bash
php artisan tinker
```

```php
DB::connection()->getPdo();
echo "Connected to: " . DB::connection()->getDatabaseName();
```
