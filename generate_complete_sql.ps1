# PowerShell script to generate complete SQL with CREATE TABLE statements
Write-Host "Generating complete SQL file for Clever Cloud..." -ForegroundColor Cyan

# Read the schema file (with CREATE TABLE statements)
$schemaFile = "quest_building_complete.sql"
$dataFile = "quest_complete_2026.sql"
$outputFile = "quest_clever_import.sql"

if (!(Test-Path $schemaFile)) {
    Write-Host "Error: Schema file not found: $schemaFile" -ForegroundColor Red
    exit 1
}

if (!(Test-Path $dataFile)) {
    Write-Host "Error: Data file not found: $dataFile" -ForegroundColor Red
    exit 1
}

Write-Host "Reading schema..." -ForegroundColor Yellow
$schema = Get-Content $schemaFile -Raw

Write-Host "Reading data..." -ForegroundColor Yellow
$data = Get-Content $dataFile -Raw

# Remove the TRUNCATE statements from data file since we're using DROP TABLE IF EXISTS
$data = $data -replace "SET FOREIGN_KEY_CHECKS = 0;[\s\S]*?SET FOREIGN_KEY_CHECKS = 1;", ""
$data = $data -replace "-- ============================================================================[\s\S]*?-- ============================================================================", ""

# Combine them
Write-Host "Combining schema and data..." -ForegroundColor Yellow
$complete = $schema + "`n`n" + $data

# Write to output file
$complete | Out-File -FilePath $outputFile -Encoding UTF8

$size = (Get-Item $outputFile).Length / 1MB
Write-Host "`nComplete!" -ForegroundColor Green
Write-Host "Output file: $outputFile" -ForegroundColor Cyan
Write-Host "File size: $($size.ToString('F2')) MB" -ForegroundColor Cyan
Write-Host "`nYou can now import $outputFile to Clever Cloud phpMyAdmin" -ForegroundColor Green
