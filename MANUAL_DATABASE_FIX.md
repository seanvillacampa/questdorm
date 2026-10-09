# Manual Database Fix (No Shell Access)

Since Render free tier doesn't have Shell access and migrations aren't running automatically, here's how to fix the database manually.

## Option 1: Use Clever Cloud Console (Easiest)

### Step 1: Log into Clever Cloud
1. Go to https://console.clever-cloud.com/
2. Log in with your account
3. Navigate to your MySQL add-on: `bio0zbdnyef0m1cqyjl6`

### Step 2: Open Web Console  
1. Click on your MySQL database
2. Look for "Console" or "phpMyAdmin" link
3. Log in with your database credentials

### Step 3: Run These SQL Commands

Copy and paste each block one at a time:

```sql
-- Create tenant_messages table
CREATE TABLE IF NOT EXISTS `tenant_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `room_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'open',
  `priority` varchar(50) NOT NULL DEFAULT 'normal',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_messages_room_id_foreign` (`room_id`),
  KEY `tenant_messages_user_id_foreign` (`user_id`),
  KEY `tenant_messages_resolved_by_foreign` (`resolved_by`),
  CONSTRAINT `tenant_messages_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_messages_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_messages_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
-- Create tenant_message_replies table
CREATE TABLE IF NOT EXISTS `tenant_message_replies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `tenant_message_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `message` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenant_message_replies_tenant_message_id_foreign` (`tenant_message_id`),
  KEY `tenant_message_replies_user_id_foreign` (`user_id`),
  CONSTRAINT `tenant_message_replies_tenant_message_id_foreign` FOREIGN KEY (`tenant_message_id`) REFERENCES `tenant_messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenant_message_replies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

```sql
-- Verify tables were created
SHOW TABLES LIKE 'tenant_%';
```

You should see:
- tenant_deposits
- tenant_message_replies  
- tenant_messages
- tenant_payments

## Option 2: Use MySQL Client from Your Computer

### Requirements:
- MySQL client installed on your machine
- Clever Cloud database credentials from your `.env`

### Steps:

```bash
# Connect to database
mysql -h bio0zbdnyef0m1cqyjl6-mysql.services.clever-cloud.com \
  -u [YOUR_DB_USERNAME] \
  -p[YOUR_DB_PASSWORD] \
  bio0zbdnyef0m1cqyjl6

# Then paste the SQL from Option 1
```

## Option 3: Add LOG_CHANNEL to Render Environment

This won't fix the table issue, but will stop the permission errors:

### Steps:
1. Go to Render Dashboard
2. Click your service
3. Go to Environment tab
4. Click "Add Environment Variable"
5. Add:
   - **Key**: `LOG_CHANNEL`
   - **Value**: `errorlog`
6. Save and wait for redeploy

This makes Laravel log to stderr instead of files, avoiding permission issues.

## Verify Fix Worked

After running the SQL:

1. Visit your Render URL
2. Click Login
3. Enter owner credentials
4. Should log in successfully! ✅

## Why Manual Fix is Needed

**The Problem:**
- Render free tier = No Shell access
- Migrations need to run AFTER container starts
- But automated startup script isn't working
- Database credentials might not be available at startup
- OR migrations are failing silently

**The Solution:**
- Run migrations manually via database console
- This bypasses the container entirely
- Direct access to database
- See exact errors if something fails

## After Manual Fix

Once tables exist:
1. ✅ App works immediately
2. ✅ Can create more tenants
3. ✅ Can send tenant messages
4. ✅ All features functional

Future code changes won't need manual fixes unless:
- You add NEW database tables
- You modify existing table structure

## Alternative Hosting Options

If this is too cumbersome, consider:

### Railway (https://railway.app)
- ✅ Free tier with Shell access
- ✅ Easy PostgreSQL setup
- ✅ Can run migrations via CLI

### Fly.io (https://fly.io)
- ✅ Free tier with SSH access
- ✅ Can run `fly ssh console`
- ✅ Then run `php artisan migrate`

### DigitalOcean App Platform
- ✅ $5/month starter tier
- ✅ Full Shell access
- ✅ Better for production

### Your Own VPS
- ✅ DigitalOcean Droplet ($4/mo)
- ✅ Linode ($5/mo)
- ✅ Full control
- ✅ SSH access always available

## Need Help?

If you get SQL errors:
1. Share the exact error message
2. Check if related tables exist (rooms, users)
3. Verify database credentials are correct

If login still fails after SQL:
1. Check Render logs for NEW errors
2. Verify database connection in Render environment variables
3. Try adding `LOG_CHANNEL=errorlog` environment variable
