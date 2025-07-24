#!/bin/bash

PID_FILE="run_scheduler.pid"

# Stop previous instance if running
if [ -f "$PID_FILE" ]; then
    OLD_PID=$(cat "$PID_FILE")
    if ps -p $OLD_PID > /dev/null 2>&1; then
        echo "Stopping existing scheduler with PID $OLD_PID"
        kill $OLD_PID
    fi
    rm -f "$PID_FILE"
fi

# Save current PID
echo $$ > "$PID_FILE"

# Startup messages
echo -e "\e[32mStarting Laravel Scheduler...\e[0m"
echo -e "\e[33mThis will run the scheduler every 30 seconds automatically\e[0m"
echo -e "\e[31mPress Ctrl+C to stop\e[0m"
echo ""

# Run loop
while true; do
    echo -e "\e[36m$(date '+%Y-%m-%d %H:%M:%S'): Running scheduler...\e[0m"
    php artisan schedule:run
    sleep 30
done
