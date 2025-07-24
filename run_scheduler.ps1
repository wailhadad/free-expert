Write-Host "Starting Laravel Scheduler..." -ForegroundColor Green
Write-Host "This will run the scheduler every 30 seconds automatically" -ForegroundColor Yellow
Write-Host "Press Ctrl+C to stop" -ForegroundColor Red
Write-Host ""

while ($true) {
    Write-Host "$(Get-Date -Format 'yyyy-MM-dd HH:mm:ss'): Running scheduler..." -ForegroundColor Cyan
    php artisan schedule:run
    Start-Sleep -Seconds 30
} 