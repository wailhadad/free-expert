#!/bin/bash

echo "=== Laravel Scheduler Setup for Linux ==="
echo ""

# Get the current directory
PROJECT_PATH=$(pwd)
echo "Project path: $PROJECT_PATH"
echo ""

# Create the cron job entry
CRON_JOB="* * * * * cd $PROJECT_PATH && php artisan schedule:run >> /dev/null 2>&1"

echo "Adding cron job:"
echo "$CRON_JOB"
echo ""

# Add to crontab
(crontab -l 2>/dev/null; echo "$CRON_JOB") | crontab -

echo "✅ Cron job added successfully!"
echo ""
echo "To verify it's working:"
echo "1. Check if cron is running: sudo systemctl status cron"
echo "2. View cron logs: tail -f /var/log/cron"
echo "3. Test manually: php artisan memberships:process-expired"
echo ""
echo "To remove the cron job later:"
echo "crontab -e"
echo "Then delete the line with 'php artisan schedule:run'" 