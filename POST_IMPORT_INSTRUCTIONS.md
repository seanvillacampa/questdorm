# Post-Import Instructions for Clever Cloud

You've imported the database successfully. Now you need to update the PHP code on Clever Cloud.

## The Problem
- ✅ Database has: `first_name`, `middle_name`, `last_name` columns
- ❌ PHP code on Clever Cloud still references: `users.name` column

## The Solution - Find Your Deployment Method

### Step 1: Log into Clever Cloud
Go to: https://console.clever-cloud.com

### Step 2: Select Your Application
Find your Quest Building / dormitory application in the dashboard.

### Step 3: Find Deployment Method

Look for these tabs/sections:

#### A. Information Tab
- Look for **"Git deployment URL"**
- It looks like: `git+ssh://git@push-...clever-cloud.com/app_XXXXX.git`
- **If you find this**, go to Option A below

#### B. FTP/FTPS Tab  
- Look for **FTP credentials** (host, username, password)
- This is for your **web application files**, not MySQL
- **If you find this**, go to Option B below

#### C. Deployment Tab
- Some Clever Cloud apps have a file upload or deployment section
- **If you find this**, go to Option C below

---

## Deployment Options

### Option A: Git Deployment (Recommended)

If you found the git URL in Step 3A:

\`\`\`powershell
# In your questdorm folder, run:

# Add Clever Cloud as remote (use YOUR git URL from console)
git remote add clever git+ssh://git@push-par-clevercloud-customers.services.clever-cloud.com/app_YOUR-APP-ID.git

# Push the code
git push clever main
\`\`\`

### Option B: FTP Upload

If you found FTP credentials in Step 3B:

1. **Extract**: `clever_cloud_update.zip` (in your questdorm folder)
2. **Connect via FTP** using:
   - Host: (from Clever Cloud console)
   - Username: (from Clever Cloud console)
   - Password: (from Clever Cloud console)
3. **Upload these 8 files** maintaining folder structure:
   ```
   app/Models/User.php
   app/Models/Room.php
   app/Http/Controllers/EmployeeController.php
   app/Http/Controllers/Auth/RegisterController.php
   app/Http/Controllers/TenantController.php
   app/Http/Controllers/AuditLogController.php
   app/Http/Controllers/LaundryOrderController.php
   app/Http/Controllers/PaymentController.php
   ```

### Option C: Console Upload

If you found a deployment/upload section:

1. **Zip your entire project**: 
   - Right-click `questdorm` folder
   - "Send to" → "Compressed (zipped) folder"
2. **Upload** via the Clever Cloud console
3. Clever Cloud will deploy automatically

---

## Can't Find Any Deployment Method?

If you can't find Git URL, FTP, or deployment section:

**You may have deployed using Clever Cloud CLI** (command line). Try:

\`\`\`powershell
# Check if clever CLI is installed
clever --version

# If installed, deploy with:
clever deploy
\`\`\`

---

## After Deployment

1. Wait 1-2 minutes for deployment
2. Visit your Clever Cloud app URL
3. The "Unknown column 'users.name'" error should be gone!

---

## Still Can't Deploy?

Take a screenshot of your Clever Cloud console showing:
1. The left sidebar menu
2. Any tabs you see for your application

This will help identify the deployment method available for your app.
