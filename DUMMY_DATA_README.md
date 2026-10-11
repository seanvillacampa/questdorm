# Quest Building Rental Management - Dummy Data Documentation

## Overview
This dummy data set provides comprehensive, realistic testing data for the Quest Building rental management system covering **January 1, 2025 - October 11, 2026** (22 months).

## Files Generated
1. **rental_management_complete.sql** - Complete ready-to-import SQL file with all data
2. **generate_dummy_data.php** - PHP script used to generate invoice/payment data (for reference/regeneration)

## Data Summary

### Properties & Rooms
- **28 Rooms** across 4 floors
  - Floor 1: 11 rooms (Rooms 101-111)
  - Floor 2: 6 rooms (Rooms 201-206)
  - Floor 3: 6 rooms (Rooms 301-306)
  - Floor 4: 5 rooms (Rooms 401-405)
- Monthly rates: ₱4,000 - ₱5,200 depending on floor and capacity
- Room capacities: 2-3 tenants per room

### Tenants
- **45 unique tenants** distributed across 28 rooms
- All tenants have:
  - User accounts with hashed passwords
  - Email addresses
  - Phone numbers
  - Emergency contacts
  - Active status

### Contracts
- **28 active contracts** (one per room)
- Start dates ranging from Jan 2025 - March 2025
- Various due days (1st, 5th, 8th, 10th, 11th, 12th, 14th, 15th, 16th, 18th, 20th, 22nd, 25th, 28th, 30th)
- Tenant distribution:
  - 11 contracts with 1 tenant
  - 13 contracts with 2 tenants
  - 4 contracts with 3 tenants

### Deposits
- **55 tenant deposit records**
- All deposits fully paid (amount_paid = amount_required)
- Deposits calculated as 2 months rent
- Split equally among tenants in each contract

### Billing & Payments

#### Invoices
- **569 total monthly invoices**
- Each invoice includes:
  - Monthly rent
  - Electricity charges (based on meter readings)
  - Split evenly among tenant count
  - Unique invoice numbers (format: BILL-YYYYMM-XXXX)

#### Payment Status Distribution

**January 2025 - July 2026 (19 months): 100% PAID**
- All invoices fully paid
- Payment dates 1-5 days after due date
- No outstanding balances

**August 2026 - October 2026 (3 months): MIXED**
Approximate distribution:
- 40% Fully Paid
- 25% Partial Payment
- 20% Pending
- 15% Overdue

**Outstanding Balance Protection:**
- No tenant exceeds ₱20,000 outstanding balance
- System automatically adjusts partial payments to stay under limit

#### Payment Methods
- 70% Cash
- 15% Bank Transfer
- 15% GCash
- All payments include reference numbers

### Meter Readings
- **1,138 meter readings** (2 per invoice: previous & current)
- Realistic electricity consumption: 150-350 kWh per month
- Electricity rate: ₱12.50 per kWh
- Readings increment realistically over time

### Tenant Payment Records
- **2,983 tenant payment records** (individual tenant shares)
- Each tenant has their share of:
  - Rent amount
  - Electricity amount
  - Total payment status
  - Carry-over balance (for partial payments)

## Import Instructions

### Prerequisites
1. Fresh Laravel installation with migrations run
2. MySQL/MariaDB database configured
3. Database user with appropriate permissions

### Step 1: Backup Current Database (if needed)
```bash
php artisan db:backup
# or
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

### Step 2: Import the Dummy Data
```bash
mysql -u your_username -p your_database_name < rental_management_complete.sql
```

Or use phpMyAdmin:
1. Open phpMyAdmin
2. Select your database
3. Click "Import" tab
4. Choose `rental_management_complete.sql`
5. Click "Go"

### Step 3: Verify Import
```bash
php artisan tinker
```

Then in tinker:
```php
// Check counts
\App\Models\Room::count();          // Should be 28
\App\Models\Tenant::count();        // Should be 45
\App\Models\Contract::count();      // Should be 28
\App\Models\Invoice::count();       // Should be 569
\App\Models\Payment::count();       // Should be 545
\App\Models\TenantDeposit::count(); // Should be 55

// Check date ranges
\App\Models\Invoice::min('billing_month'); // Should be 2025-01-01 or 2025-02-01
\App\Models\Invoice::max('billing_month'); // Should be 2026-09-01 or 2026-10-01

// Check payment statuses
\App\Models\Invoice::where('status', 'paid')->count();
\App\Models\Invoice::where('status', 'partial')->count();
\App\Models\Invoice::where('status', 'pending')->count();
\App\Models\Invoice::where('status', 'overdue')->count();

// Check outstanding balances
\App\Models\TenantPayment::where('status', '!=', 'paid')
    ->selectRaw('tenant_id, SUM(share_amount - amount_paid) as outstanding')
    ->groupBy('tenant_id')
    ->get();
```

## Data Characteristics

### Realistic Features
✅ Sequential invoice numbering  
✅ Realistic electricity consumption patterns  
✅ Payment dates after due dates  
✅ Mixed payment methods  
✅ Gradual meter reading progression  
✅ Proper foreign key relationships  
✅ Realistic Filipino names and contact info  
✅ Varied contract start dates  
✅ Different tenant counts per room  

### Testing Scenarios Supported

#### Financial Testing
- Revenue calculations
- Outstanding balance reports
- Payment collection tracking
- Overdue invoice management
- Partial payment handling

#### Operational Testing
- Tenant onboarding flows
- Contract management
- Billing cycle automation
- Meter reading entry
- Payment processing

#### Reporting Testing
- Monthly revenue reports
- Occupancy rates
- Collection efficiency
- Tenant payment history
- Room-wise profitability

#### Edge Cases
- Single-tenant rooms
- Multi-tenant rooms (2-3 tenants)
- Partial payments
- Overdue invoices
- Mixed payment statuses in same month

## Important Notes

### Date Constraints
- **No data after October 11, 2026**
- Current system date should be set to October 11, 2026 or later for realistic testing
- Automated billing should pick up from November 2026 onwards

### Password Information
All tenant user accounts have the same hashed password:
- Hash: `$2y$12$LQv3c1yLk4Q5M6x8K9h0xez3.J4N5K6L7M8N9O0P1Q2R3S4T5U6V7W`
- This is a bcrypt hash - for testing, you may want to reset passwords using:
```php
\App\Models\User::where('role', 'tenant')->update(['password' => bcrypt('password123')]);
```

### Foreign Key Dependencies
Data is inserted in the correct order to maintain referential integrity:
1. Settings
2. Rooms
3. Users
4. Tenants
5. Contracts
6. Contract_Tenants (pivot)
7. Tenant_Deposits
8. Meter_Readings
9. Invoices
10. Tenant_Payments
11. Payments

### Regenerating Data
If you need to regenerate with different parameters:
```bash
# Edit generate_dummy_data.php to adjust:
# - Contract configurations
# - Payment status distribution
# - Date ranges
# - Electricity consumption patterns

php generate_dummy_data.php > rental_management_dummy_data_invoices.sql

# Then combine with base data:
Get-Content rental_management_dummy_data.sql, rental_management_dummy_data_invoices.sql | Out-File -FilePath "rental_management_complete.sql" -Encoding utf8
```

## Statistics Summary

| Metric | Count |
|--------|-------|
| **Rooms** | 28 |
| **Tenants** | 45 |
| **Contracts** | 28 |
| **Tenant Deposits** | 55 |
| **Invoices** | 569 |
| **Payments** | 545 |
| **Tenant Payments** | 2,983 |
| **Meter Readings** | 1,138 |
| **Months Covered** | 22 |
| **Fully Paid Invoices** | ~450 (Jan 25 - Jul 26) |
| **Mixed Status Invoices** | ~119 (Aug 26 - Oct 26) |

## Troubleshooting

### Import Errors

**Error: Duplicate entry for key 'PRIMARY'**
- Solution: Ensure tables are truncated before import (handled by SQL file)

**Error: Foreign key constraint fails**
- Solution: Check that migrations match the schema expected by SQL
- Verify that `SET FOREIGN_KEY_CHECKS = 0;` is at the top of file

**Error: Unknown column**
- Solution: Run all migrations first: `php artisan migrate:fresh`
- Check that column names match your migration files

### Data Validation Issues

**Outstanding balance exceeds ₱20,000**
- The script includes logic to prevent this, but if found:
- Check `tenant_payments` table for that tenant
- Verify calculation: `SUM(share_amount - amount_paid) WHERE status != 'paid'`

**Missing invoices for some months**
- Contracts start at different dates
- First invoice is generated for the month AFTER contract start
- Example: Contract starts Jan 15, 2025 → First invoice is Feb 2025

## Support

For issues or questions about this dummy data:
1. Check the generation script: `generate_dummy_data.php`
2. Review this documentation
3. Verify your migration files match the expected schema
4. Test with a fresh database import

---

**Generated:** October 11, 2026  
**Version:** 1.0  
**Coverage:** January 2025 - October 2026 (22 months)  
**Status:** Production-Ready Testing Data
