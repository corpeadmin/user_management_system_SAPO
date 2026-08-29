# PowerShell Launcher for PHP User Portal
$projectDir = Split-Path -Parent $MyInvocation.MyCommand.Path

Write-Host "===================================================" -ForegroundColor Cyan
Write-Host "    PHP User Management Portal - Local Server      " -ForegroundColor Cyan
Write-Host "===================================================" -ForegroundColor Cyan

# Detect PHP
$phpPath = $null
if (Get-Command "php" -ErrorAction SilentlyContinue) {
    $phpPath = "php"
} elseif (Test-Path "C:\xampp\php\php.exe") {
    $phpPath = "C:\xampp\php\php.exe"
}

if (-not $phpPath) {
    Write-Host "[ERROR] PHP was not found in PATH or C:\xampp\php\php.exe" -ForegroundColor Red
    Write-Host "Please start XAMPP or install PHP to continue." -ForegroundColor Yellow
    exit 1
}

Write-Host "[*] PHP Executable : $phpPath" -ForegroundColor Green
Write-Host "[*] Project Folder : $projectDir" -ForegroundColor Green
Write-Host "[*] Server URL     : http://localhost:8000" -ForegroundColor Green
Write-Host ""
Write-Host "Opening default browser and starting server... (Press Ctrl+C to stop)" -ForegroundColor Yellow
Write-Host "===================================================" -ForegroundColor Cyan

# Open browser
Start-Process "http://localhost:8000"

# Start server
& $phpPath -S localhost:8000 -t $projectDir
