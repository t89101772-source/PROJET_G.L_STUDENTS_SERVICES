@echo off
echo ========================================
echo   Student Management App - Backend API
echo ========================================
echo.
echo Starting PHP Server on localhost:8000...
echo Using router.php as entry point for proper CORS handling
echo.
echo IMPORTANT: 
echo   - Keep this window open while using the app
echo   - Press Ctrl+C to stop the server
echo   - Make sure MySQL/MariaDB is running
echo.
echo ========================================
echo.
cd /d %~dp0
php -S localhost:8000 router.php
if errorlevel 1 (
    echo.
    echo ERROR: Failed to start server
    echo Make sure PHP is installed and in your PATH
    pause
)
pause

