# Reset Database to Fresh Demo State
# Use this to reset database between demos/tests

Write-Host "╔════════════════════════════════════════════════════════╗" -ForegroundColor Yellow
Write-Host "║         Reset Database to Demo State                  ║" -ForegroundColor Yellow
Write-Host "╚════════════════════════════════════════════════════════╝" -ForegroundColor Yellow
Write-Host ""

Write-Host "⚠️  WARNING: This will delete ALL data in the database!" -ForegroundColor Red
Write-Host ""

$confirm = Read-Host "Are you sure you want to continue? (type 'yes' to confirm)"

if ($confirm -ne "yes") {
    Write-Host ""
    Write-Host "❌ Reset cancelled" -ForegroundColor Yellow
    Write-Host ""
    pause
    exit
}

Write-Host ""
Write-Host "🗄️  Resetting database..." -ForegroundColor Cyan

try {
    # Drop all tables and recreate
    php artisan migrate:fresh --force
    Write-Host "  ✅ Database structure reset" -ForegroundColor Green
    
    # Seed demo data
    php artisan db:seed --force
    Write-Host "  ✅ Demo data seeded" -ForegroundColor Green
    
    # Clear all caches
    php artisan config:clear
    php artisan cache:clear
    php artisan view:clear
    php artisan route:clear
    Write-Host "  ✅ Caches cleared" -ForegroundColor Green
    
    Write-Host ""
    Write-Host "╔════════════════════════════════════════════════════════╗" -ForegroundColor Green
    Write-Host "║           ✅ Database Reset Complete!                 ║" -ForegroundColor Green
    Write-Host "╚════════════════════════════════════════════════════════╝" -ForegroundColor Green
    Write-Host ""
    
    Write-Host "📋 Demo Accounts:" -ForegroundColor Cyan
    Write-Host "   Admin:  admin@dorm.test / password" -ForegroundColor White
    Write-Host "   Staff:  staff@dorm.test / password" -ForegroundColor White
    Write-Host "   Tenant: tenant1@dorm.test / password" -ForegroundColor White
    Write-Host "   (tenant1 through tenant20 available)" -ForegroundColor Gray
    Write-Host ""
    
    Write-Host "🎯 Demo Data Includes:" -ForegroundColor Cyan
    Write-Host "   • 15 rooms with various capacities" -ForegroundColor Gray
    Write-Host "   • 20 demo tenants" -ForegroundColor Gray
    Write-Host "   • Active contracts" -ForegroundColor Gray
    Write-Host "   • Sample invoices (some paid, some pending)" -ForegroundColor Gray
    Write-Host "   • Meter readings" -ForegroundColor Gray
    Write-Host "   • Sample payments" -ForegroundColor Gray
    Write-Host ""
    
} catch {
    Write-Host ""
    Write-Host "❌ Reset failed!" -ForegroundColor Red
    Write-Host "Error: $_" -ForegroundColor Red
    Write-Host ""
    Write-Host "Common issues:" -ForegroundColor Yellow
    Write-Host "  • MySQL is not running" -ForegroundColor Gray
    Write-Host "  • Database connection settings incorrect in .env" -ForegroundColor Gray
    Write-Host "  • Database doesn't exist (create 'dormitory_db' first)" -ForegroundColor Gray
    Write-Host ""
}

pause
