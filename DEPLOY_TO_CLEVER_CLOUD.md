# Deploy to Clever Cloud - SSH Key Setup

Your git push failed because you don't have SSH keys configured. Here are two solutions:

## Option 1: Generate SSH Keys (Recommended)

### Step 1: Generate SSH Key

Open PowerShell and run:

\`\`\`powershell
ssh-keygen -t rsa -b 4096 -C "your-email@example.com"
\`\`\`

Press Enter to accept default location, then press Enter twice to skip passphrase.

### Step 2: Copy Your Public Key

\`\`\`powershell
Get-Content ~/.ssh/id_rsa.pub | Set-Clipboard
Write-Host "✅ SSH key copied to clipboard!"
\`\`\`

### Step 3: Add Key to Clever Cloud

1. Go to: https://console.clever-cloud.com
2. Click your **profile icon** (top right)
3. Go to **"SSH Keys"** or **"Profile"** → **"SSH Keys"**
4. Click **"Add SSH Key"**
5. Paste your key (Ctrl+V)
6. Give it a name like "My PC"
7. Click **Save**

### Step 4: Deploy

\`\`\`powershell
# Now push to Clever Cloud
git push clever main
\`\`\`

---

## Option 2: Manual FTP Upload (No SSH Needed)

If you can't set up SSH keys, upload files via FTP:

### Step 1: Get FTP Credentials

1. Go to your app in Clever Cloud console
2. Look for **"FTP"** or **"FTPS"** tab
3. Note your:
   - Host
   - Username  
   - Password

### Step 2: Upload Files

1. Download **FileZilla** (free FTP client)
2. Connect using your FTP credentials
3. Navigate to your app folder
4. Upload these 8 files (maintaining folder structure):
   - `app/Models/User.php`
   - `app/Models/Room.php`
   - `app/Http/Controllers/EmployeeController.php`
   - `app/Http/Controllers/Auth/RegisterController.php`
   - `app/Http/Controllers/TenantController.php`
   - `app/Http/Controllers/AuditLogController.php`
   - `app/Http/Controllers/LaundryOrderController.php`
   - `app/Http/Controllers/PaymentController.php`

(These files are in the `clever_cloud_update.zip` file)

---

## Option 3: Use Clever CLI

Install Clever CLI and deploy:

\`\`\`powershell
# Install Clever CLI (if you have npm)
npm install -g clever-tools

# Login
clever login

# Link to your app
clever link app_a86aff06-0525-42cd-a7a1-87536fe39288

# Deploy
clever deploy
\`\`\`

---

## Quick Check: Do You Have the Files?

The files you need to upload are in: `clever_cloud_update.zip`

Extract it and you'll see all 8 PHP files ready to upload.

---

## Which Option Should You Choose?

- **Option 1 (SSH)**: Best for future updates. One-time setup, then `git push` anytime.
- **Option 2 (FTP)**: Quick fix for now, but manual every time.
- **Option 3 (CLI)**: Good if you have Node.js installed.

**Recommendation**: Do Option 1 (SSH keys) - it's a 5-minute setup and makes future deployments super easy!
