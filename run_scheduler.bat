@echo off
echo Starting Laravel Scheduler...
echo This will run the scheduler every 30 seconds automatically
echo Press Ctrl+C to stop
echo.

:loop
php artisan schedule:run
timeout /t 30 /nobreak >nul
goto loop 