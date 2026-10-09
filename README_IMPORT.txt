==============================================================================
                        IMPORT TO CLEVER CLOUD
==============================================================================

✅ CORRECT FILE TO IMPORT:
--------------------------
File: clever_cloud_seed_clean.sql
Size: 1.1 MB
Location: C:\Users\Sean\Downloads\questdorm\clever_cloud_seed_clean.sql

⚠️ DO NOT USE THE OLD FILE:
---------------------------
❌ clever_cloud_seed.sql (has encoding issues)

==============================================================================
                        IMPORT INSTRUCTIONS
==============================================================================

STEP 1: Go to Clever Cloud Console
   → https://console.clever-cloud.com

STEP 2: Open Your MySQL Add-on
   → Click on your MySQL database

STEP 3: Click "phpMyAdmin"
   → Button on the right side

STEP 4: Select Your Database
   → Click database name in left sidebar

STEP 5: Import the File
   → Click "Import" tab at top
   → Click "Choose File"
   → Select: clever_cloud_seed_clean.sql
   → Scroll down
   → Click "Go"

STEP 6: Wait (1-2 minutes)
   → Don't close browser
   → Wait for "Import successfully finished"

STEP 7: Test Login
   Email: owner@dorm.test
   Password: password

==============================================================================
                        WHAT'S INCLUDED
==============================================================================

✓ 28 Rooms (11 on floor 1)
✓ 65+ Users (owner, staff, tenants)
✓ 13 Months of Data (Oct 2025 - Oct 2026)
✓ ~364 Invoices
✓ ~300 Payments
✓ ~2,000-3,000 Laundry Orders
✓ 5 Laundry Services
✓ Detergent Inventory System
✓ Complete Financial History

==============================================================================
                        TROUBLESHOOTING
==============================================================================

ERROR: "Table already exists"
→ Your database has existing tables
→ SOLUTION: Drop all tables first, then import

ERROR: Import timeout
→ Connection is slow
→ SOLUTION: Try again or use command line

ERROR: "You have an error in your SQL syntax"
→ You imported the wrong file
→ SOLUTION: Use clever_cloud_seed_clean.sql (NOT the old one)

==============================================================================
                        AFTER IMPORT
==============================================================================

1. Login as owner@dorm.test (password: password)
2. Check dashboard shows data
3. Browse rooms, tenants, invoices
4. Test laundry order creation
5. Verify tenant autocomplete works

==============================================================================

Need help? Check IMPORT_TO_CLEVER_CLOUD.md for detailed guide.

Good luck! 🚀
