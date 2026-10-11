# Import Instructions for Quest Building Dummy Data

## ⚠️ Important: Schema Compatibility Note

This dummy data has been updated to match your actual database schema which uses:
- **Spatie Laravel Permission package** for role management
- **`phone`** column (not `phone_number`)
- **`phone_country_code`** column
- **NO `emergency_contact`** or `role`** columns in users table

## Files Available

1. **rental_management_complete_v2.sql** - RECOMMENDED (Fixed version with correct schema)
2. **rental_management_dummy_data_fixed.sql** - Base data only (settings, rooms, users, tenants, contracts, deposits)
3. **add_role_assignments.sql** - Role assignments for Spatie permissions
4. **rental_management_dummy_data_invoices.sql** - All invoices and payments data

## Quick Import (Recommended)

###  Step 1: Check Your Role ID

First, check what ID the 'tenant' role has in your database:

```sql
SELECT * FROM roles WHERE name = 'tenant';
```

If the tenant role ID is **NOT 3**, you'll need to edit `add_role_assignments.sql` and change all occurrences of role_id `3` to your actual tenant role ID.

### Step 2: Import the Complete File

```bash
mysql -u your_username -p your_database < rental_management_complete_v2.sql
```

OR using PowerShell:
```powershell
Get-Content rental_management_complete_v2.sql | mysql -u root -p questdorm_db
```

### Step 3: Verify the Import

```bash
php validate_dummy_data.php
```

## Manual Import (If you need to customize)

If you need to import in separate steps:

```bash
# 1. Import base data
mysql -u your_username -p your_database < rental_management_dummy_data_fixed.sql

# 2. Assign roles (edit file first if tenant role_id ≠ 3)
mysql -u your_username -p your_database < add_role_assignments.sql

# 3. Import invoices and payments
mysql -u your_username -p your_database < rental_management_dummy_data_invoices.sql
```

## Troubleshooting

### Error: Unknown column 'phone_number'
**Solution:** Use `rental_management_complete_v2.sql` instead of the old `rental_management_complete.sql`

### Error: Duplicate entry for role assignment
**Solution:** The role assignments already exist. You can skip the `add_role_assignments.sql` file or truncate the `model_has_roles` table first:
```sql
TRUNCATE TABLE model_has_roles;
```

### Error: Foreign key constraint fails on role_id
**Solution:** Make sure the 'tenant' role exists in your `roles` table. Create it if missing:
```sql
INSERT INTO roles (name, guard_name, created_at, updated_at) 
VALUES ('tenant', 'web', NOW(), NOW());
```

### Wrong role_id in assignments
**Solution:** Check your tenant role ID:
```sql
SELECT id FROM roles WHERE name = 'tenant';
```

Then edit `add_role_assignments.sql` and replace `(3, 'App\\Models\\User',` with `(YOUR_ROLE_ID, 'App\\Models\\User',` throughout the file.

## Verification Queries

After import, run these to verify everything is correct:

```sql
-- Check total users
SELECT COUNT(*) FROM users; -- Should be 45

-- Check users have tenant role assigned
SELECT COUNT(*) FROM model_has_roles 
WHERE model_type = 'App\\Models\\User'; -- Should be 45

-- Check all tenants are linked to users
SELECT COUNT(*) FROM tenants; -- Should be 45

-- Check invoices imported
SELECT COUNT(*) FROM invoices; -- Should be 569

-- Sample tenant with role
SELECT u.id, u.name, u.email, u.phone, u.phone_country_code, r.name as role_name
FROM users u
LEFT JOIN model_has_roles mhr ON u.id = mhr.model_id AND mhr.model_type = 'App\\Models\\User'
LEFT JOIN roles r ON mhr.role_id = r.id
WHERE u.id = 101;
```

## What's Included

✅ 28 rooms across 4 floors
✅ 45 tenant users with proper Spatie role assignments
✅ 28 active contracts
✅ 55 fully-paid deposits
✅ 569 invoices (Jan 2025 - Oct 2026)
✅ 545 payment records
✅ 2,983 tenant payment shares
✅ 1,138 meter readings

## Payment Status Distribution

- **Jan 2025 - Jul 2026:** 100% PAID (~450 invoices)
- **Aug 2026 - Oct 2026:** MIXED (~119 invoices)
  - 40% Paid
  - 25% Partial  
  - 20% Pending
  - 15% Overdue

## Login Credentials

All tenant users have the same password hash for testing.

To reset passwords for testing:
```php
php artisan tinker
> \App\Models\User::whereHas('roles', function($q) { $q->where('name', 'tenant'); })
    ->update(['password' => bcrypt('password123')]);
> exit
```

Then login with any tenant email (e.g., `maria.santos@email.com`) with password: `password123`

## Need Help?

- Full documentation: `DUMMY_DATA_README.md`
- Quick start guide: `QUICK_START.txt`
- Validation script: `validate_dummy_data.php`

---

**Updated:** October 11, 2026  
**Version:** 2.0 (Schema-corrected)
