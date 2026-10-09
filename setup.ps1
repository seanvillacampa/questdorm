# Dorm Management System - Automated Setup Script
# Run this script to setup the application on a new machine

$ErrorActionPreference = "Stop"

Write-Host "╔════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║      Dorm Management System - Setup Wizard            ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Function to check if command exists
function Test-Command {
    param($Command)
    $null = Get-Command $Command -ErrorAction SilentlyContinue
    return $?
}

# Step 1: Check Prerequisites
Write-Host "📋 Step 1: Checking Prerequisites..." -ForegroundColor Yellow
Write-Host ""

$missingTools = @()

if (-not (Test-Command "php")) {
    $missingTools += "PHP 8.2+"
    Write-Host "  ❌ PHP not found" -ForegroundColor Red
} else {
    $phpVersion = php -v | Select-String -Pattern "PHP (\d+\.\d+)" | ForEach-Object { $_.Matches.Groups[1].Value }
    Write-Host "  ✅ PHP $phpVersion found" -ForegroundColor Green
}

if (-not (Test-Command "composer")) {
    $missingTools += "Composer"
    Write-Host "  ❌ Composer not found" -ForegroundColor Red
} else {
    Write-Host "  ✅ Composer found" -ForegroundColor Green
}

if (-not (Test-Command "npm")) {
    $missingTools += "Node.js/npm"
    Write-Host "  ❌ npm not found" -ForegroundColor Red
} else {
    Write-Host "  ✅ npm found" -ForegroundColor Green
}

if ($missingTools.Count -gt 0) {
    Write-Host ""
    Write-Host "⚠️  Missing required tools:" -ForegroundColor Red
    foreach ($tool in $missingTools) {
        Write-Host "   - $tool" -ForegroundColor Red
    }
    Write-Host ""
    Write-Host "Please install the missing tools and run this script again." -ForegroundColor Yellow
    Write-Host "See SETUP_GUIDE.md for download links." -ForegroundColor Yellow
    Write-Host ""
    pause
    exit 1
}

Write-Host ""
Write-Host "✅ All prerequisites installed!" -ForegroundColor Green
Write-Host ""

# Step 2: Environment Configuration
Write-Host "📝 Step 2: Environment Configuration..." -ForegroundColor Yellow
Write-Host ""

if (-not (Test-Path ".env")) {
    Write-Host "  Creating .env file from .env.example..." -ForegroundColor Cyan
    Copy-Item .env.example .env
    Write-Host "  ✅ .env file created" -ForegroundColor Green
} else {
    Write-Host "  ℹ️  .env file already exists, skipping..." -ForegroundColor Yellow
}

Write-Host ""

# Prompt for database password
Write-Host "Database Configuration:" -ForegroundColor Cyan
$dbPassword = Read-Host "  Enter MySQL password (leave empty if no password)"

if ($dbPassword) {
    (Get-Content .env) -replace 'DB_PASSWORD=.*', "DB_PASSWORD=$dbPassword" | Set-Content .env
    Write-Host "  ✅ Database password configured" -ForegroundColor Green
}

Write-Host ""

# Prompt for ngrok token
Write-Host "ngrok Configuration:" -ForegroundColor Cyan
Write-Host "  Get your authtoken from: https://dashboard.ngrok.com/get-started/your-authtoken" -ForegroundColor Gray
$ngrokToken = Read-Host "  Enter your ngrok authtoken"

if (-not $ngrokToken) {
    Write-Host "  ⚠️  No ngrok token provided. You'll need to configure it manually later." -ForegroundColor Yellow
} else {
    Write-Host "  ✅ ngrok token saved" -ForegroundColor Green
}

Write-Host ""

# Step 3: Install PHP Dependencies
Write-Host "📦 Step 3: Installing PHP Dependencies..." -ForegroundColor Yellow
Write-Host ""

try {
    composer install --no-interaction --prefer-dist --optimize-autoloader
    Write-Host "  ✅ PHP dependencies installed" -ForegroundColor Green
} catch {
    Write-Host "  ❌ Failed to install PHP dependencies" -ForegroundColor Red
    Write-Host "  Error: $_" -ForegroundColor Red
    pause
    exit 1
}

Write-Host ""

# Step 4: Generate Application Key
Write-Host "🔑 Step 4: Generating Application Key..." -ForegroundColor Yellow
Write-Host ""

try {
    php artisan key:generate --force
    Write-Host "  ✅ Application key generated" -ForegroundColor Green
} catch {
    Write-Host "  ❌ Failed to generate application key" -ForegroundColor Red
    pause
    exit 1
}

Write-Host ""

# Step 5: Install Node.js Dependencies
Write-Host "📦 Step 5: Installing Node.js Dependencies..." -ForegroundColor Yellow
Write-Host ""

try {
    npm install
    Write-Host "  ✅ Node.js dependencies installed" -ForegroundColor Green
} catch {
    Write-Host "  ❌ Failed to install Node.js dependencies" -ForegroundColor Red
    Write-Host "  Error: $_" -ForegroundColor Red
    pause
    exit 1
}

Write-Host ""

# Step 6: Build Frontend Assets
Write-Host "🎨 Step 6: Building Frontend Assets..." -ForegroundColor Yellow
Write-Host ""

try {
    npm run build
    Write-Host "  ✅ Frontend assets built" -ForegroundColor Green
} catch {
    Write-Host "  ❌ Failed to build frontend assets" -ForegroundColor Red
    pause
    exit 1
}

Write-Host ""

# Step 7: Database Migration
Write-Host "🗄️  Step 7: Database Setup..." -ForegroundColor Yellow
Write-Host ""

Write-Host "  Testing database connection..." -ForegroundColor Cyan

try {
    $dbTest = php artisan migrate:status 2>&1
    
    if ($LASTEXITCODE -ne 0) {
        Write-Host "  ⚠️  Cannot connect to database" -ForegroundColor Yellow
        Write-Host "  Please ensure MySQL is running and database 'dormitory_db' exists" -ForegroundColor Yellow
        Write-Host ""
        $continue = Read-Host "  Do you want to continue anyway? (y/n)"
        if ($continue -ne "y") {
            exit 1
        }
    } else {
        Write-Host "  ✅ Database connection successful" -ForegroundColor Green
        Write-Host ""
        
        $runMigrations = Read-Host "  Run database migrations? (y/n)"
        if ($runMigrations -eq "y") {
            php artisan migrate --force
            Write-Host "  ✅ Migrations completed" -ForegroundColor Green
            Write-Host ""
            
            $runSeeders = Read-Host "  Seed demo data? (recommended for testing) (y/n)"
            if ($runSeeders -eq "y") {
                php artisan db:seed --force
                Write-Host "  ✅ Demo data seeded" -ForegroundColor Green
            }
        }
    }
} catch {
    Write-Host "  ⚠️  Database setup skipped" -ForegroundColor Yellow
}

Write-Host ""

# Step 8: Configure ngrok (if token provided)
if ($ngrokToken) {
    Write-Host "🌐 Step 8: Configuring ngrok..." -ForegroundColor Yellow
    Write-Host ""
    
    # Check if ngrok is installed
    $ngrokPath = "C:\ngrok\ngrok.exe"
    
    if (-not (Test-Path $ngrokPath)) {
        Write-Host "  ⚠️  ngrok not found at $ngrokPath" -ForegroundColor Yellow
        Write-Host "  Please download ngrok and extract to C:\ngrok\" -ForegroundColor Yellow
        Write-Host "  Download: https://ngrok.com/download" -ForegroundColor Yellow
    } else {
        try {
            & $ngrokPath config add-authtoken $ngrokToken
            Write-Host "  ✅ ngrok configured" -ForegroundColor Green
        } catch {
            Write-Host "  ⚠️  Failed to configure ngrok" -ForegroundColor Yellow
        }
    }
    Write-Host ""
}

# Step 9: Clear Caches
Write-Host "🧹 Step 9: Clearing Caches..." -ForegroundColor Yellow
Write-Host ""

php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

Write-Host "  ✅ Caches cleared" -ForegroundColor Green
Write-Host ""

# Final Summary
Write-Host "╔════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║           Setup Complete! 🎉                          ║" -ForegroundColor Green
Write-Host "╚════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""

Write-Host "📋 Next Steps:" -ForegroundColor Cyan
Write-Host ""
Write-Host "  1. Start the application:" -ForegroundColor White
Write-Host "     .\start-app.ps1" -ForegroundColor Yellow
Write-Host ""
Write-Host "  2. Configure PayMongo webhook:" -ForegroundColor White
Write-Host "     a. Note your ngrok URL (shown when app starts)" -ForegroundColor Gray
Write-Host "     b. Visit: https://dashboard.paymongo.com/developers" -ForegroundColor Gray
Write-Host "     c. Add webhook with your ngrok URL + /webhooks/paymongo" -ForegroundColor Gray
Write-Host "     d. Copy webhook secret and update .env file" -ForegroundColor Gray
Write-Host "     e. Run: php artisan config:clear" -ForegroundColor Gray
Write-Host ""
Write-Host "  3. Login credentials (if you seeded demo data):" -ForegroundColor White
Write-Host "     Admin:  admin@dorm.test / password" -ForegroundColor Gray
Write-Host "     Tenant: tenant1@dorm.test / password" -ForegroundColor Gray
Write-Host ""
Write-Host "  4. See SETUP_GUIDE.md for detailed documentation" -ForegroundColor White
Write-Host ""

Write-Host "Press any key to exit..."
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
