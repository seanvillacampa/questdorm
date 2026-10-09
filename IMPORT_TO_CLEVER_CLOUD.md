# Import Dummy Data to Clever Cloud

## ✅ SQL File Ready

File: `clever_cloud_seed.sql`  
Size: ~2.2 MB  
Contains: Complete dummy data from October 2025 to October 2026

## 📊 What's Included

- **Users**: 1 owner + 2 staff + 60+ tenants
- **Rooms**: 28 rooms (11 on floor 1, rest distributed on floors 2-4)
- **Contracts**: Active contracts for all rooms
- **Invoices**: 13 months of billing data (Oct 2025 - Oct 2026)
- **Payments**: Realistic payment history
- **Laundry Orders**: ~2,000-3,000 laundry transactions
- **Laundry Services**: 5 services with tiered pricing
- **Detergent Inventory**: Stock tracking with audit logs
- **Meter Readings**: Monthly electricity readings
- **Tenant Deposits**: Security deposits for all tenants

## 🚀 How to Import

### Option 1: Via Clever Cloud phpMyAdmin (Easiest)

1. **Login to Clever Cloud**
   - Go to https://console.clever-cloud.com
   - Open your MySQL add-on

2. **Open phpMyAdmin**
   - Click the "phpMyAdmin" button
   - You'll be automatically logged in

3. **Select Your Database**
   - Click on your database name in the left sidebar

4. **Import the SQL File**
   - Click "Import" tab at the top
   - Click "Choose File" and select `clever_cloud_seed.sql`
   - Scroll down and click "Go"
   - Wait for the import to complete (may take 1-2 minutes)

5. **Verify Import**
   - Click "Structure" to see all tables
   - Click on "users" table and browse to see data

### Option 2: Via Clever Cloud Console

1. **Login to Clever Cloud**
   - Go to https://console.clever-cloud.com
   - Open your MySQL add-on

2. **Open MySQL Console**
   - Click the "Console" button
   - You'll see a SQL console

3. **Copy-Paste SQL**
   - Open `clever_cloud_seed.sql` in a text editor
   - Copy ALL the contents
   - Paste into the console
   - Click "Execute" or press Ctrl+Enter

⚠️ **Note**: This method may time out for large files. Use phpMyAdmin instead.

### Option 3: Via Command Line (Advanced)

If you have MySQL client installed:

```bash
mysql -h your-host.mysql.services.clever-cloud.com \
      -P 3306 \
      -u your_username \
      -p \
      your_database_name \
      < clever_cloud_seed.sql
```

Get your credentials from:
Clever Cloud Dashboard → MySQL Add-on → Information tab

## 🔐 Test Accounts

After importing, you can login with these accounts:

### Owner Account
```
Email: owner@dorm.test
Password: password
```

### Employee/Staff Accounts
```
Email: staff@dorm.test
Password: password

Email: staff2@dorm.test
Password: password
```

### Tenant Accounts
Tenants are also seeded with username/password combinations.
All passwords are: `password`

## ✅ Post-Import Checklist

After importing, verify these work:

1. **Login**
   - [ ] Can login as owner
   - [ ] Can login as staff
   - [ ] Dashboard loads without errors

2. **Rooms & Tenants**
   - [ ] 28 rooms exist
   - [ ] Rooms show occupied status
   - [ ] Tenants have active contracts

3. **Invoices & Payments**
   - [ ] Invoices are visible
   - [ ] Payment history shows
   - [ ] Dashboard statistics display correctly

4. **Laundry System**
   - [ ] Laundry services are available
   - [ ] Can view laundry orders
   - [ ] Tenant autocomplete works
   - [ ] Detergent inventory shows stock

5. **Reports**
   - [ ] Can generate reports
   - [ ] Export to CSV/PDF works
   - [ ] Date ranges filter correctly

## 🔄 Re-Import Instructions

If you need to re-import (start fresh):

### Clear All Tables First

Via phpMyAdmin:
1. Go to your database
2. Click "Structure" tab
3. Check "Check all" at the bottom
4. In the dropdown, select "Drop"
5. Confirm the deletion
6. Then import `clever_cloud_seed.sql` again

Via Console:
```sql
-- BE CAREFUL! This deletes everything
SET FOREIGN_KEY_CHECKS=0;

-- Drop all tables (repeat for all tables)
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS rooms;
DROP TABLE IF EXISTS tenants;
-- ... (drop all tables)

SET FOREIGN_KEY_CHECKS=1;
```

Then import the SQL file again.

## ⚠️ Important Notes

1. **Backup First**: If you have existing data, export it first before importing
2. **Large File**: The import may take 1-2 minutes depending on your connection
3. **Browser Timeout**: If phpMyAdmin times out, try splitting the file or use command line
4. **Production Warning**: This will REPLACE all existing data

## 🐛 Troubleshooting

### "Table already exists" error
- Your database already has tables
- Solution: Drop all tables first, then import

### Import times out
- File is too large for web import
- Solution: Use command line method or increase PHP timeout

### "Access denied" error
- Check your database credentials
- Make sure you're connected to the correct database

### Missing data after import
- Check if import completed successfully
- Look for error messages in phpMyAdmin
- Verify file size is ~2.2 MB

### Foreign key constraint errors
- Usually happens if tables were partially created
- Solution: Drop all tables and re-import

## 📝 Database Statistics

After successful import, you should see approximately:

| Table | Count |
|-------|-------|
| users | ~65 |
| rooms | 28 |
| tenants | ~60-70 |
| contracts | 28 |
| invoices | ~364 |
| payments | ~300 |
| meter_readings | ~364 |
| laundry_orders | ~2,000-3,000 |
| services | 5 |
| service_prices | 15 |
| customers | ~100 |
| detergent_logs | ~2,500+ |

## 🎯 Next Steps

After successful import:

1. **Test Login**: Try logging in as owner/staff
2. **Explore Dashboard**: Check all statistics are showing
3. **Test Features**:
   - Create a new invoice
   - Record a payment
   - Add a laundry order
   - Test tenant autocomplete
4. **Configure PayMongo**: Set up payment gateway if needed
5. **Customize**: Update settings, electricity rates, etc.

## 💡 Tips

- **Bookmark phpMyAdmin**: Keep the link handy for future use
- **Regular Backups**: Export your database regularly
- **Test Environment**: Use a separate Clever Cloud database for testing
- **Check Logs**: Monitor application logs for any errors after import

---

## Need Help?

If you encounter issues:
1. Check Clever Cloud documentation
2. Verify database credentials
3. Try re-importing with a fresh database
4. Contact Clever Cloud support for database-specific issues

**Good luck! 🎉**
