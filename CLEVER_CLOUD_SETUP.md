# Clever Cloud Database Setup Guide

## Problem
The `quest_complete_2026.sql` file contains only INSERT/TRUNCATE statements. It assumes tables already exist from Laravel migrations. When you import it directly to Clever Cloud phpMyAdmin, you get the "unknown column" error because **tables don't exist yet**.

## Solutions

### ✅ Solution 1: Run Migrations First (Recommended)

**Step 1: SSH to Clever Cloud**
```bash
clever ssh
```

**Step 2: Run migrations to create tables**
```bash
php artisan migrate
```

**Step 3: Exit SSH**
```bash
exit
```

**Step 4: Import SQL in phpMyAdmin**
Now import `quest_complete_2026.sql` in Clever Cloud's phpMyAdmin interface.

---

### ✅ Solution 2: Use Laravel Seeder (Best Method)

**Step 1: Deploy your code to Clever Cloud**
```bash
git add .
git commit -m "Add Quest Building seeder"
git push clever master
```

**Step 2: SSH to Clever Cloud**
```bash
clever ssh
```

**Step 3: Run fresh migration + seeder**
```bash
php artisan migrate:fresh
php artisan seed:quest-building
```

**Step 4: Exit**
```bash
exit
```

---

### ✅ Solution 3: Export Full Database Dump with CREATE TABLE statements

If you want a single SQL file that works in phpMyAdmin:

**Step 1: On your local machine, run migrations**
```powershell
php artisan migrate:fresh
```

**Step 2: Export complete database structure + data**
```powershell
# Using mysqldump (if you have MySQL installed locally)
mysqldump -u root -p dormitory_db > quest_complete_with_schema.sql
```

**Step 3: Import quest_complete_with_schema.sql to Clever Cloud**
This file will include CREATE TABLE statements, so it works directly in phpMyAdmin.

---

## Current Error Explanation

```
#1054 - Unknown column 'deposit_required' in 'field list'
```

This happens because the SQL tries to:
```sql
INSERT INTO `contracts` (..., `deposit_required`, ...) VALUES ...
```

But the `contracts` table **doesn't exist** in your Clever Cloud database yet!

---

## Recommended Workflow

### For Development (Local):
```powershell
php artisan migrate:fresh
php artisan seed:quest-building
```

### For Production (Clever Cloud):

**First time setup:**
```bash
# SSH to Clever Cloud
clever ssh

# Run migrations
php artisan migrate

# Run seeder
php artisan seed:quest-building

# Exit
exit
```

**Subsequent updates:**
```bash
# Push code
git push clever master

# SSH and run seeder
clever ssh
php artisan seed:quest-building
exit
```

---

## Checking if Migrations Ran

### Via SSH:
```bash
clever ssh
php artisan migrate:status
exit
```

### Via phpMyAdmin:
Check if these tables exist:
- users
- rooms
- contracts
- tenants
- invoices
- payments
- tenant_payments
- meter_readings
- etc.

---

## Alternative: Manual Table Creation

If you can't use SSH/Artisan commands, you can create tables manually in phpMyAdmin, but it's **not recommended** because:
- You'd need to manually run 20+ CREATE TABLE statements
- Foreign keys are complex to set up
- Easy to make mistakes
- Time-consuming

---

## Quick Reference

| Method | Pros | Cons |
|--------|------|------|
| SSH + Migrations | ✅ Proper way<br>✅ Handles foreign keys<br>✅ Versioned | Requires SSH access |
| SSH + Seeder | ✅ Best method<br>✅ Complete setup<br>✅ One command | Requires code deployment |
| phpMyAdmin + Full SQL | ✅ No SSH needed | Need to export locally first |
| phpMyAdmin + Data SQL | ❌ Doesn't work | Tables must exist first |

---

## Need Help?

1. **Can't SSH to Clever Cloud?**
   - Install Clever Tools: https://www.clever-cloud.com/doc/getting-started/cli/
   - Link your app: `clever link [app-id]`

2. **Forgot app ID?**
   - Check `.clever.json` in your project root
   - Or visit: https://console.clever-cloud.com/

3. **Seeder not found?**
   - Make sure you pushed your code: `git push clever master`
   - Check deployment logs in Clever Cloud console

---

## Summary

**✅ DO THIS:**
```bash
# 1. SSH to Clever Cloud
clever ssh

# 2. Run migrations
php artisan migrate

# 3. Run seeder
php artisan seed:quest-building

# 4. Done!
exit
```

**❌ DON'T DO THIS:**
- Import `quest_complete_2026.sql` directly in phpMyAdmin without running migrations first
- Try to manually create 20+ tables
- Ignore foreign key constraints

