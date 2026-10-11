================================================================================
QUEST BUILDING DUMMY DATA - SCHEMA VERIFIED
================================================================================

FILE TO IMPORT: IMPORT_THIS_quest_2026.sql
SIZE: 324.5 KB
STATUS: ✅ ALL COLUMNS VERIFIED AGAINST YOUR DATABASE

================================================================================
WHAT WAS FIXED
================================================================================

❌ OLD ERROR: "Unknown column 'deposit_required' in contracts"
✅ FIXED: Removed deposit_required/deposit_collected from contracts table
          (These columns were removed by migration 2026_10_07_080444)

✅ ADDED: deposit_required column to rooms table  
          (Added by migration 2026_10_07_080424)

✅ VERIFIED: All other table columns match your migrations exactly

================================================================================
SCHEMA DIFFERENCES FROM STANDARD
================================================================================

CONTRACTS table:
  ✅ HAS: id, room_id, start_date, end_date, monthly_rent, due_day, status
  ❌ NO: deposit_required, deposit_collected (removed by migration)
  
ROOMS table:
  ✅ HAS: deposit_required (added by migration 2026_10_07_080424)
  
USERS table:
  ✅ HAS: first_name, middle_name, last_name, phone_country_code
  ❌ NO: role column (uses Spatie's model_has_roles table)

INVOICES table:
  ✅ HAS: tenant_count (added by migration 2026_09_27_400000)

================================================================================
QUICK IMPORT
================================================================================

1. Check tenant role ID:
   SELECT id FROM roles WHERE name = 'tenant';
   
2. If NOT 3, edit SQL file:
   Search:  (3, 'App\\Models\\User',
   Replace: (YOUR_ROLE_ID, 'App\\Models\\User',

3. Import to Clever Cloud:
   - Via phpMyAdmin: Import → IMPORT_THIS_quest_2026.sql
   - Via Console: mysql -u user -p database < IMPORT_THIS_quest_2026.sql
   - Via Clever CLI: clever mysql-console < IMPORT_THIS_quest_2026.sql

================================================================================
DATA INCLUDED
================================================================================

✅ 28 Rooms (11 on Floor 1, each with deposit_required field)
✅ 45 Tenants (random Filipino names, unique)
✅ 28 Contracts (NO deposit fields, random due dates)
✅ 45+ Tenant Deposits (in tenant_deposits table, all paid)
✅ ~280 Invoices (Jan 2026 - Oct 11, 2026)
✅ ~200 Payments (Jan-Jul 100% paid, Aug-Oct mixed)
✅ ~800 Tenant Payment Shares
✅ ~560 Meter Readings (realistic 150-350 kWh)
✅ 45 Role Assignments (Spatie permissions)

Period: January 1, 2026 - October 11, 2026
Collection Rate: ~85-90%
Max Arrears: Under ₱20,000 per tenant

================================================================================
VERIFICATION AFTER IMPORT
================================================================================

-- Should return 28 rooms with 11 on floor 1
SELECT floor, COUNT(*) FROM rooms GROUP BY floor;

-- Should show deposit_required in rooms
SELECT room_number, monthly_rate, deposit_required FROM rooms LIMIT 5;

-- Should have NO deposit_required column error
SELECT * FROM contracts LIMIT 1;

-- Check payment statuses (Jan-Jul should be 100% paid)
SELECT 
  CASE WHEN billing_month < '2026-08-01' THEN 'Jan-Jul' ELSE 'Aug-Oct' END as period,
  status,
  COUNT(*) as count
FROM invoices
GROUP BY period, status;

-- Verify arrears under 20K
SELECT tenant_id, SUM(share_amount - amount_paid) as balance
FROM tenant_payments
WHERE status != 'paid'
GROUP BY tenant_id
HAVING balance > 20000;
-- Should return 0 rows

================================================================================
FILES IN THIS PACKAGE
================================================================================

1. IMPORT_THIS_quest_2026.sql ← MAIN FILE (Import this!)
2. FINAL_README.txt ← This file
3. quest_dummy_jan_oct_2026.php ← Generator script (for reference)

================================================================================
READY TO GO!
================================================================================

The file IMPORT_THIS_quest_2026.sql is:
✅ Schema-verified against all your migrations
✅ Complete with 10 months of data
✅ Tested and validated
✅ Ready for Clever Cloud import

Just import and start testing!

================================================================================
