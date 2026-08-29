@echo off
title PHP User Portal - Server
cd /d "%~dp0"

echo ===================================================
echo     PHP User Management Portal - Local Server
echo ===================================================
echo.

:: Detect PHP Executable
set PHP_BIN=
where php >nul 2>&1
if %ERRORLEVEL% EQU 0 (
    set PHP_BIN=php
) else if exist "C:\xampp\php\php.exe" (
    set "PHP_BIN=C:\xampp\php\php.exe"
) else if exist "C:\laragon\bin\php\php-8.2*\php.exe" (
    for /d %%i in ("C:\laragon\bin\php\php-*") do (
        if exist "%%i\php.exe" set "PHP_BIN=%%i\php.exe"
    )
)

if "%PHP_BIN%"=="" (
    echo [ERROR] PHP executable not found.
    echo Please make sure PHP is installed (e.g. XAMPP in C:\xampp) or added to your PATH.
    echo.
    pause
    exit /b 1
)

echo [*] PHP Executable: %PHP_BIN%
echo [*] Project Directory: %~dp0
echo [*] Starting PHP Built-in Server on http://localhost:8000 ...
echo.
echo [*] Opening browser to http://localhost:8000
echo [*] Press Ctrl+C in this window to stop the server anytime.
echo ===================================================
echo.

:: Launch browser in background after 1 second
start "" cmd /c "timeout /t 1 /nobreak >nul && start http://localhost:8000"

:: Start the built-in PHP web server
"%PHP_BIN%" -S localhost:8000 -t "%~dp0"

pause
