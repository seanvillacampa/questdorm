# ✅ FINAL Import Guide - Schema Corrected

## What Was Fixed

The SQL file has been completely rewritten to match your **actual database schema**:

### ❌ Old (Wrong) → ✅ New (Correct)

**Users Table:**
- ❌ `phone_number` → ✅ `phone`
- ❌ `emergency_contact` → ✅ (removed)
- ❌ `role` column → ✅ Uses Spatie's `model_has_roles` table
- ✅ Added `phone_country_code`

**Contracts Table:**
- ❌ `deposit_amount` → ✅ `deposit_required` + `deposit_collected`
- ❌ `is_active` → ✅ `status` ('active', 'ended', 'ending_soon')
- ✅ `end_date` is NOT NULL (set to 2026-12-31)
- ✅ Added `monthly_rent` column
- ✅ Added `contract_file_path` (nullable)
- ✅ Added `created_by` (nullable)

**Invoices Table:**
- ✅ Has `tenant_count` column (from migration 2026_09_27_400000)
- ✅ Status values: 'pending', 'paid', 'late', 'overdue', 'void'
- ✅ Has `paid_at` timestamp
- ✅ Has `created_by` foreign key

## 📦 NEW FILE TO USE

**`quest_building_complete_FINAL.sql`**

This is the corrected version that will work with your database.

## 🚀 Import Instructions

### Step 1: Check Tenant Role ID

```sql
SELECT id FROM roles WHERE name = 'tenant';
```

- If it returns **3**: Continue to Step 2
- If it returns something else: Edit the SQL file and search/replace `(3, 'App\\Models\\User'` with `(YOUR_ROLE_ID, 'App\\Models\\User'`

### Step 2: Import the SQL File

```bash
mysql -u root -p your_database < quest_building_complete_FINAL.sql
```

OR using PowerShell:
```powershell
Get-Content quest_building_complete_FINAL.sql | mysql -u root -p your_database
```

OR using phpMyAdmin:
1. Open phpMyAdmin
2. Select your database
3. Click "Import"
4. Choose `quest_building_complete_FINAL.sql`
5. Click "Go"

### Step 3: Verify Import

```sql
-- Should return 45
SELECT COUNT(*) FROM users WHERE id >= 101;

-- Should return 28
SELECT COUNT(*) FROM contracts;

-- Should return 569
SELECT COUNT(*) FROM invoices;

-- Should return 45 (role assignments)
SELECT COUNT(*) FROM model_has_roles 
WHERE model_type = 'App\\Models\\User' AND model_id >= 101;

-- Check a sample tenant
SELECT u.id, u.name, u.email, u.phone, u.phone_country_code, 
       r.name as role,
       c.room_id, c.monthly_rent
FROM users u
LEFT JOIN model_has_roles mhr ON u.id = mhr.model_id
LEFT JOIN roles r ON mhr.role_id = r.id
LEFT JOIN tenants t ON u.id = t.user_id
LEFT JOIN contract_tenants ct ON t.id = ct.tenant_id AND ct.left_on IS NULL
LEFT JOIN contracts c ON ct.contract_id = c.id
WHERE u.id = 101;
```

## ⚠️ Common Errors & Solutions

### Error: Duplicate entry for key 'PRIMARY'
**Cause:** Tables not empty  
**Solution:** The SQL file includes TRUNCATE statements. Make sure foreign key checks are disabled at the start.

### Error: Unknown column 'deposit_amount'
**Cause:** Using old SQL file  
**Solution:** Make sure you're using `quest_building_complete_FINAL.sql`

### Error: Foreign key constraint fails (role_id)
**Cause:** Tenant role doesn't exist  
**Solution:**
```sql
INSERT INTO roles (name, guard_name, created_at, updated_at) 
VALUES ('tenant', 'web', NOW(), NOW());
```

### Error: Data too long for column 'status'
**Cause:** Wrong status value  
**Solution:** The new file uses correct status values: 'active' for contracts, 'pending'/'paid'/'overdue' for invoices

## 📊 What's Included

✅ 28 rooms across 4 floors  
✅ 45 tenant users with Spatie roles  
✅ 28 active contracts (end_date: 2026-12-31)  
✅ 55 tenant deposits (all fully paid)  
✅ 569 monthly invoices (Jan 2025 - Oct 2026)  
✅ 545 payment records  
✅ 2,983 tenant payment shares  
✅ 1,138 meter readings  

## 🔐 Testing Login

Reset tenant passwords:
```bash
php artisan tinker
```

```php
\App\Models\User::where('id', '>=', 101)->update(['password' => bcrypt('password123')]);
exit
```

Then login with:
- **Email:** maria.santos@email.com
- **Password:** password123

(Or any other tenant email from the list)

## 📋 Full Tenant List

maria.santos@email.com, juan.delacruz@email.com, ana.reyes@email.com,
pedro.garcia@email.com, rosa.mendoza@email.com, carlos.ramos@email.com,
elena.torres@email.com, miguel.castro@email.com, sofia.flores@email.com,
david.aquino@email.com, isabel.cruz@email.com, ricardo.lopez@email.com,
carmen.diaz@email.com, luis.hernandez@email.com, patricia.gonzalez@email.com,
fernando.martinez@email.com, laura.rodriguez@email.com, jorge.sanchez@email.com,
monica.perez@email.com, alberto.gomez@email.com, diana.rivera@email.com,
rafael.morales@email.com, gloria.jimenez@email.com, antonio.ruiz@email.com,
veronica.ortiz@email.com, eduardo.vargas@email.com, beatriz.herrera@email.com,
sergio.silva@email.com, mariana.medina@email.com, raul.moreno@email.com,
adriana.guzman@email.com, pablo.romero@email.com, liliana.aguilar@email.com,
hector.mendez@email.com, valentina.castillo@email.com, oscar.navarro@email.com,
natalia.vega@email.com, gabriel.campos@email.com, camila.rios@email.com,
andres.soto@email.com, daniela.luna@email.com, javier.pena@email.com,
lucia.ramirez@email.com, rodrigo.chavez@email.com, sandra.benitez@email.com

## ✅ Success Indicators

After import, your dashboard should show:
- 28 occupied rooms (100% occupancy)
- 45 active tenants
- ~94% collection rate
- Revenue data from Jan 2025 - Oct 2026
- Mix of paid/partial/pending/overdue in Aug-Oct 2026

---

**File:** quest_building_complete_FINAL.sql  
**Schema Version:** Matched to your migrations  
**Generated:** October 11, 2026  
**Status:** ✅ Ready to Import
