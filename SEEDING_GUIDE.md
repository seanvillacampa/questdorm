# Database Seeding Guide

This guide explains the two different seeding options available for the Quest Building Dorm Management System.

## 📊 Seeding Options Overview

### Option 1: Quest Building Dataset (Recommended for Demos)
A complete, realistic dataset based on actual Quest Building operations from January to October 2026.

### Option 2: Generated Dataset
Randomly generated data with varied patterns, ideal for testing and development.

---

## 🏢 Option 1: Quest Building Dataset

### What's Included

**Rooms (28 total):**
- Floor 1: 11 rooms (101-111)
- Floor 2: 6 rooms (201-206)
- Floor 3: 6 rooms (301-306)
- Floor 4: 5 rooms (401-405)

**Room Details:**
- Room 101: ₱4,500/month, 3 capacity, 2 tenants
- Room 102: ₱4,000/month, 2 capacity, 2 tenants
- Room 103: ₱4,500/month, 3 capacity, 1 tenant
- And so on...

**Tenants (45 total):**
All with Filipino names and real phone numbers:
- Elena Rivera (elena.rivera@email.com)
- Daniela Alvarez (daniela.alvarez@email.com)
- Jose Santos (jose.santos@email.com)
- And 42 more...

**Contracts (28 active):**
- Start Date: December 15, 2025
- End Date: December 31, 2027
- Due Days: Varied (1st, 5th, 8th, 10th, 12th, 15th, 18th, 20th, 22nd, 25th, 28th)
- All deposits fully paid

**Invoices & Payments:**
- **January - July 2026:** All paid (100% completion)
- **August 2026:** Mixed status (Room 101 pending, others paid)
- **September 2026:** Mostly paid
- **October 2026:** Mixed (paid, partial, pending)

### How to Use

```powershell
# Option 1: Fresh migration + seed
php artisan seed:quest-building --fresh

# Option 2: Seed only (keeps existing schema)
php artisan seed:quest-building
```

### Sample Login Credentials

**Owner Account:**
```
Email: owner@dorm.test
Password: password
```

**Sample Tenant Accounts:**
```
Email: elena.rivera@email.com
Password: password

Email: daniela.alvarez@email.com
Password: password

Email: jose.santos@email.com
Password: password
```

All tenant passwords are: `password`

### Key Features of Quest Dataset

1. **Realistic Payment Patterns:**
   - Most tenants pay on time
   - Some have partial payments
   - A few are overdue
   - Includes carry-over balances

2. **Actual Meter Readings:**
   - Monthly readings from December 2025 to October 2026
   - Realistic kWh consumption (150-330 kWh/month)
   - Electricity rate: ₱12.50/kWh (2026 rate)

3. **Complete Audit Trail:**
   - All invoices numbered (BILL-202601-0001, etc.)
   - Payment references (CASH-XXXXXX, REF-XXXXXXXX)
   - Multiple payment methods (cash, gcash, bank_transfer)

4. **Deposit Management:**
   - All tenants have paid deposits
   - Deposit amount = 2 months rent
   - No deductions or refunds yet

---

## 🎲 Option 2: Generated Dataset

### What's Included

**Rooms (28 total):**
- Same structure as Quest Building
- Random configurations
- Mixed air-conditioned/non-AC
- Varying capacities (1-4 persons)

**Tenants:**
- 40-60 randomly generated tenants
- Filipino names
- Random phone numbers
- Email format: firstname.lastname#@dorm.test

**Contracts:**
- Random start dates
- Random due days
- Random occupancy (1 to room capacity)

**Invoices:**
- October 2025 to October 2026
- Randomized payment patterns:
  - Current month: 25% paid, 35% pending, 40% overdue
  - Last month: 45% paid, 15% partial, 15% pending, 25% overdue
  - 2-3 months ago: 60% paid, 15% partial, 15% pending, 10% overdue
  - Older: 75% paid, 13% partial, 7% pending, 5% overdue

**Laundry Orders:**
- Random orders from October 2025 to October 2026
- 2-8 orders per day
- Multiple service types:
  - WDF (Wash-Dry-Fold): ₱70-80
  - WD (Wash-Dry): ₱60-70
  - DRY (Dry Only): ₱40-50
  - WO (Wash Only): ₱35-45
  - COM (Comforter): ₱100-120

### How to Use

```powershell
# Fresh migration + seed
php artisan migrate:fresh --seed
```

### Sample Login Credentials

**Owner Account:**
```
Email: owner@dorm.test
Password: password
```

**Staff Accounts:**
```
Email: staff@dorm.test
Password: password

Email: staff2@dorm.test
Password: password
```

**Tenant Accounts:**
Check database for generated emails (format varies)

---

## 🔄 Switching Between Datasets

### From Generated to Quest Building:
```powershell
php artisan seed:quest-building --fresh
```

### From Quest Building to Generated:
```powershell
php artisan migrate:fresh --seed
```

---

## 📁 Technical Details

### Quest Building Data Source

The Quest Building dataset is loaded from:
```
database/seeders/quest_complete_2026.sql
```

This is a complete SQL dump containing:
- INSERT statements for all tables
- Proper foreign key relationships
- Realistic IDs and sequences

### Generated Data Source

The generated dataset is created by:
```
database/seeders/DatabaseSeeder.php
```

This uses Laravel's factory pattern and random data generation.

---

## 🎯 Use Cases

### Use Quest Building Dataset When:
- ✅ Presenting to stakeholders
- ✅ Demonstrating realistic scenarios
- ✅ Testing specific tenant/room combinations
- ✅ Showing historical data trends
- ✅ Training new users

### Use Generated Dataset When:
- ✅ Development and testing
- ✅ Stress testing with varied data
- ✅ Testing edge cases
- ✅ Experimenting with new features
- ✅ Creating custom scenarios

---

## 🛠️ Troubleshooting

### Seeding Fails with Foreign Key Error:
```powershell
# Solution: Use --fresh flag to recreate tables
php artisan seed:quest-building --fresh
```

### Wrong Dataset Loaded:
```powershell
# Check which seeder ran
php artisan tinker
>>> App\Models\User::count();  # Should be 45 for Quest, varies for Generated
>>> App\Models\Room::count();  # Should be 28 for both
```

### Database Connection Error:
1. Check `.env` file for correct DB credentials
2. Ensure database exists
3. Test connection:
   ```powershell
   php artisan migrate:status
   ```

### SQL File Not Found:
Ensure `quest_complete_2026.sql` exists in:
```
database/seeders/quest_complete_2026.sql
```

---

## 📊 Data Comparison

| Feature | Quest Building | Generated |
|---------|---------------|-----------|
| Rooms | 28 (fixed layout) | 28 (random config) |
| Tenants | 45 (named) | 40-60 (random) |
| Contracts | 28 (all active) | 28 (all active) |
| Invoices | Jan-Oct 2026 | Oct 2025-Oct 2026 |
| Payment Pattern | Realistic | Random weighted |
| Laundry Orders | ✅ Included | ✅ Included |
| Staff Users | ✅ Created | ✅ Created |
| Meter Readings | Monthly (real values) | Monthly (random) |
| Deposits | All paid | All paid |

---

## 💡 Tips

1. **For Presentations:** Always use Quest Building dataset for consistency
2. **For Development:** Use Generated dataset to test various scenarios
3. **For Testing:** Run both datasets to ensure compatibility
4. **Backup First:** Before switching datasets, backup important data
5. **Check Relationships:** After seeding, verify foreign key relationships work

---

## 🔍 Verifying Seeded Data

### Check Room Count:
```powershell
php artisan tinker
>>> App\Models\Room::count();
28
```

### Check Tenant Count:
```powershell
php artisan tinker
>>> App\Models\Tenant::count();
45  # Quest Building
# Varies for Generated
```

### Check Invoice Count:
```powershell
php artisan tinker
>>> App\Models\Invoice::count();
280  # Quest Building (28 rooms × 10 months)
# Varies for Generated
```

### Check Payment Status Distribution:
```powershell
php artisan tinker
>>> App\Models\Invoice::where('status', 'paid')->count();
>>> App\Models\Invoice::where('status', 'partial')->count();
>>> App\Models\Invoice::where('status', 'pending')->count();
```

---

## 📝 Notes

- Both datasets include staff users (owner@dorm.test, staff@dorm.test)
- All passwords are `password` for easy testing
- Laundry services are identical in both datasets
- Settings table is populated in both options
- Audit logs are generated from seeding activities

---

**For questions or issues, check the main README.md or contact the development team.**
