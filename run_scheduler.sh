#!/bin/bash

echo -e "\e[32mStarting Laravel Scheduler...\e[0m"
echo -e "\e[33mThis will run the scheduler every 30 seconds automatically\e[0m"
echo -e "\e[31mPress Ctrl+C to stop\e[0m"
echo ""

while true; do
    echo -e "\e[36m$(date '+%Y-%m-%d %H:%M:%S'): Running scheduler...\e[0m"
    php artisan schedule:run
    sleep 30
done
