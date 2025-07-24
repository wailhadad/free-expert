# Laravel Scheduler Setup for Linux

## Option 1: Using Cron (Recommended)

### Quick Setup
```bash
# Make the script executable
chmod +x setup_linux_cron.sh

# Run the setup script
./setup_linux_cron.sh
```

### Manual Setup
```bash
# Add to crontab
crontab -e

# Add this line:
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

## Option 2: Using Systemd Service (Advanced)

### 1. Create Service File
```bash
sudo cp laravel-scheduler.service /etc/systemd/system/
```

### 2. Edit the Service File
```bash
sudo nano /etc/systemd/system/laravel-scheduler.service
```

Update the paths:
- `WorkingDirectory=/path/to/your/project` → Your actual project path
- `User=www-data` → Your web server user
- `Group=www-data` → Your web server group

### 3. Enable and Start Service
```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-scheduler
sudo systemctl start laravel-scheduler
```

### 4. Check Status
```bash
sudo systemctl status laravel-scheduler
```

## Option 3: Using Supervisor (Production)

### 1. Install Supervisor
```bash
sudo apt-get install supervisor
```

### 2. Create Config File
```bash
sudo nano /etc/supervisor/conf.d/laravel-scheduler.conf
```

Add:
```ini
[program:laravel-scheduler]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan schedule:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/scheduler.log
stopwaitsecs=3600
```

### 3. Start Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-scheduler:*
```

## Verification

### Check if it's working:
```bash
# Test manually
php artisan memberships:process-expired

# Check logs
tail -f storage/logs/laravel.log

# Check cron logs (if using cron)
tail -f /var/log/cron
```

### Test Auto-Renewal:
```bash
# Expire a membership
php test_scheduler_auto_renewal.php

# Wait 1 minute, then check for new membership
php check_membership_status.php
```

## Troubleshooting

### If cron isn't working:
```bash
# Check if cron is running
sudo systemctl status cron

# Start cron if needed
sudo systemctl start cron
sudo systemctl enable cron
```

### If service isn't working:
```bash
# Check service status
sudo systemctl status laravel-scheduler

# View logs
sudo journalctl -u laravel-scheduler -f
```

### Permissions:
```bash
# Ensure proper permissions
sudo chown -R www-data:www-data /path/to/your/project
sudo chmod -R 755 /path/to/your/project/storage
``` 