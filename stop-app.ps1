# Stop all Dorm Manager services

Write-Host "🛑 Stopping Dorm Manager..." -ForegroundColor Yellow
Write-Host ""

# Stop PHP processes
$phpProcesses = Get-Process | Where-Object {$_.ProcessName -eq "php"}
if ($phpProcesses) {
    $phpProcesses | Stop-Process -Force
    Write-Host "✅ Stopped Laravel server" -ForegroundColor Green
} else {
    Write-Host "ℹ️  No Laravel server running" -ForegroundColor Gray
}

# Stop ngrok processes
$ngrokProcesses = Get-Process | Where-Object {$_.ProcessName -eq "ngrok"}
if ($ngrokProcesses) {
    $ngrokProcesses | Stop-Process -Force
    Write-Host "✅ Stopped ngrok tunnel" -ForegroundColor Green
} else {
    Write-Host "ℹ️  No ngrok tunnel running" -ForegroundColor Gray
}

Write-Host ""
Write-Host "✅ All services stopped" -ForegroundColor Green
Write-Host ""

Start-Sleep -Seconds 2
