# Dorm Manager Startup Script
# This starts both Laravel server and ngrok tunnel

$ErrorActionPreference = "SilentlyContinue"

Write-Host "╔════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║        Dorm Management System - Starting...            ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Check if ports are already in use
$portCheck = netstat -ano | Select-String ":8000"
if ($portCheck) {
    Write-Host "⚠️  Port 8000 is already in use!" -ForegroundColor Yellow
    Write-Host "Another instance may already be running." -ForegroundColor Yellow
    Write-Host ""
    $continue = Read-Host "Continue anyway? (y/n)"
    if ($continue -ne "y") {
        exit
    }
}

# Find ngrok executable
$ngrokPaths = @(
    "C:\ngrok\ngrok.exe",
    "C:\Users\$env:USERNAME\ngrok\ngrok.exe",
    "$PSScriptRoot\ngrok.exe"
)

$ngrokPath = $null
foreach ($path in $ngrokPaths) {
    if (Test-Path $path) {
        $ngrokPath = $path
        break
    }
}

if (-not $ngrokPath) {
    # Try to find in PATH
    $ngrokPath = (Get-Command ngrok -ErrorAction SilentlyContinue).Source
}

if (-not $ngrokPath) {
    Write-Host "❌ ngrok not found!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please install ngrok:" -ForegroundColor Yellow
    Write-Host "1. Download from: https://ngrok.com/download" -ForegroundColor Gray
    Write-Host "2. Extract to: C:\ngrok\" -ForegroundColor Gray
    Write-Host "3. Run setup.ps1 to configure" -ForegroundColor Gray
    Write-Host ""
    pause
    exit 1
}

Write-Host "✅ Found ngrok at: $ngrokPath" -ForegroundColor Green
Write-Host ""

# Start Laravel server
Write-Host "▶️  Starting Laravel server..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PSScriptRoot'; Write-Host '🚀 Laravel Server Running' -ForegroundColor Green; Write-Host ''; php artisan serve"

# Wait for Laravel to start
Start-Sleep -Seconds 3

# Start ngrok tunnel
Write-Host "▶️  Starting ngrok tunnel..." -ForegroundColor Cyan
Start-Process powershell -ArgumentList "-NoExit", "-Command", "cd '$PSScriptRoot'; Write-Host '🌐 ngrok Tunnel Running' -ForegroundColor Green; Write-Host ''; & '$ngrokPath' http 8000"

# Wait for ngrok to start
Start-Sleep -Seconds 4

Write-Host "⏳ Retrieving URLs..." -ForegroundColor Cyan
Write-Host ""

# Get the ngrok URL
try {
    $ngrokUrl = (Invoke-RestMethod -Uri http://localhost:4040/api/tunnels).tunnels | Select-Object -First 1 -ExpandProperty public_url
    
    Write-Host "╔════════════════════════════════════════════════════════╗" -ForegroundColor Green
    Write-Host "║           🎉 Application Started Successfully!        ║" -ForegroundColor Green
    Write-Host "╚════════════════════════════════════════════════════════╝" -ForegroundColor Green
    Write-Host ""
    
    Write-Host "📍 Access URLs:" -ForegroundColor Cyan
    Write-Host "   Local:      http://127.0.0.1:8000" -ForegroundColor Yellow
    Write-Host "   Public:     $ngrokUrl" -ForegroundColor Yellow
    Write-Host "   ngrok UI:   http://localhost:4040" -ForegroundColor Yellow
    Write-Host ""
    
    Write-Host "👤 Admin Panel:" -ForegroundColor Cyan
    Write-Host "   URL:        http://127.0.0.1:8000/dashboard" -ForegroundColor Gray
    Write-Host "   Email:      admin@dorm.test" -ForegroundColor Gray
    Write-Host "   Password:   password" -ForegroundColor Gray
    Write-Host ""
    
    Write-Host "👨‍💼 Tenant Portal:" -ForegroundColor Cyan
    Write-Host "   URL:        http://127.0.0.1:8000/tenant-portal" -ForegroundColor Gray
    Write-Host "   Email:      tenant1@dorm.test" -ForegroundColor Gray
    Write-Host "   Password:   password" -ForegroundColor Gray
    Write-Host ""
    
    Write-Host "🔔 PayMongo Webhook:" -ForegroundColor Cyan
    Write-Host "   URL:        $ngrokUrl/webhooks/paymongo" -ForegroundColor White
    Write-Host "   Configure:  https://dashboard.paymongo.com/developers" -ForegroundColor Gray
    Write-Host ""
    
    Write-Host "⚠️  Important Notes:" -ForegroundColor Yellow
    Write-Host "   • First-time visitors to ngrok URL will see a warning page" -ForegroundColor Gray
    Write-Host "   • Click 'Visit Site' to proceed" -ForegroundColor Gray
    Write-Host "   • Keep these PowerShell windows open while testing" -ForegroundColor Gray
    Write-Host "   • View webhook logs: http://localhost:4040" -ForegroundColor Gray
    Write-Host ""
    
    Write-Host "Press any key to stop all services and exit..." -ForegroundColor Magenta
    $null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
    
    Write-Host ""
    Write-Host "🛑 Stopping services..." -ForegroundColor Yellow
    
    # Stop all processes
    Get-Process | Where-Object {$_.ProcessName -eq "php" -or $_.ProcessName -eq "ngrok"} | Stop-Process -Force
    
    Write-Host "✅ All services stopped" -ForegroundColor Green
    Start-Sleep -Seconds 1
    
} catch {
    Write-Host "❌ Could not retrieve ngrok URL" -ForegroundColor Red
    Write-Host ""
    Write-Host "Possible issues:" -ForegroundColor Yellow
    Write-Host "  • ngrok is not running" -ForegroundColor Gray
    Write-Host "  • ngrok authtoken not configured" -ForegroundColor Gray
    Write-Host "  • Port 4040 is in use" -ForegroundColor Gray
    Write-Host ""
    Write-Host "Check the ngrok PowerShell window for errors" -ForegroundColor Yellow
    Write-Host ""
    pause
}
