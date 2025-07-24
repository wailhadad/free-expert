# User Membership Expiration System

## Overview
This system automatically sends expiration emails and real-time notifications when user memberships expire or are about to expire. It works alongside the existing seller membership expiration system.

## Features

### ✅ Email Notifications
- **Expiration Email**: Sent when a membership expires
- **Reminder Email**: Sent before expiration (configurable days)
- **Professional Templates**: HTML emails with package details and renewal links

### ✅ Real-time Notifications
- **Instant Notifications**: Real-time notifications via WebSocket
- **In-app Alerts**: Appear in notification bell dropdown
- **Toast Notifications**: Immediate pop-up notifications
- **Database Storage**: Persistent notification history

### ✅ Automated Processing
- **Cron Job**: Runs every minute via Laravel scheduler
- **Duplicate Prevention**: Tracks processed memberships
- **Error Handling**: Comprehensive logging and error recovery
- **Queue Processing**: Background email sending

## System Components

### 1. Jobs
- **`UserMembershipExpiredMail`**: Sends expiration emails
- **`UserMembershipReminderMail`**: Sends reminder emails

### 2. Notifications
- **`UserMembershipExpiredNotification`**: Real-time expiration notifications

### 3. Commands
- **`ProcessUserMembershipExpirations`**: Main processing command

### 4. Database Changes
- **`processed_for_expiration`**: Tracks if expiration was processed
- **`reminder_sent`**: Tracks if reminder was sent

### 5. Mail Templates
- **`user_membership_expired`**: Expiration email template
- **`user_membership_expiry_reminder`**: Reminder email template

## Installation & Setup

### 1. Run Migrations
```bash
php artisan migrate
```

### 2. Verify Scheduler
The command is automatically scheduled in `app/Console/Kernel.php`:
```php
$schedule->command('user-memberships:process-expirations')->everyMinute();
```

### 3. Test the System
```bash
php test_user_membership_expiration.php
```

## How It Works

### 1. Expiration Detection
The system checks for expired memberships every minute:
```php
UserMembership::where('status', '1')
    ->where('expire_date', '<', Carbon::now())
    ->where('processed_for_expiration', 0)
    ->get();
```

### 2. Email Sending
For each expired membership:
- Sends email using `MegaMailer`
- Uses template `user_membership_expired`
- Includes package details and renewal link

### 3. Real-time Notifications
For each expired membership:
- Creates `UserMembershipExpiredNotification`
- Sends via `NotificationService`
- Broadcasts via WebSocket (Pusher)

### 4. Reminder System
Checks for memberships expiring soon:
```php
UserMembership::where('status', '1')
    ->whereDate('expire_date', Carbon::now()->addDays($reminderDays))
    ->where('reminder_sent', 0)
    ->get();
```

## Configuration

### Reminder Days
Set in `BasicSettings` table:
```php
$bs->expiration_reminder // Default: 7 days
```

### Email Templates
Templates are stored in `mail_templates` table:
- `user_membership_expired`
- `user_membership_expiry_reminder`

### WebSocket Configuration
Ensure Pusher is configured in `.env`:
```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_app_id
PUSHER_APP_KEY=your_app_key
PUSHER_APP_SECRET=your_app_secret
PUSHER_APP_CLUSTER=your_cluster
```

## Email Templates

### Expiration Email
**Subject**: "Your Membership Has Expired"
**Content**:
- Package name and expiry date
- Renewal instructions
- Login link
- Support contact information

### Reminder Email
**Subject**: "Your Membership Will Expire Soon"
**Content**:
- Package name and expiry date
- Reminder to renew
- Login link
- Thank you message

## Real-time Notifications

### Expiration Notification
```php
[
    'type' => 'user_membership_expired',
    'title' => 'Membership Expired',
    'message' => "Your membership for package '{$package->title}' has expired...",
    'url' => route('user.packages.index'),
    'icon' => 'fas fa-calendar-times',
    'extra' => [
        'membership_id' => $membership->id,
        'package_id' => $membership->package_id,
        'package_title' => $membership->package->title,
        'expire_date' => $membership->expire_date,
    ]
]
```

### Reminder Notification
```php
[
    'type' => 'user_membership_reminder',
    'title' => 'Membership Expiring Soon',
    'message' => "Your membership for package '{$package->title}' will expire...",
    'url' => route('user.packages.index'),
    'icon' => 'fas fa-clock',
    'extra' => [
        'membership_id' => $membership->id,
        'package_id' => $membership->package_id,
        'package_title' => $membership->package->title,
        'expire_date' => $membership->expire_date,
        'days_remaining' => $reminderDays,
    ]
]
```

## Testing

### Manual Testing
```bash
# Test the command directly
php artisan user-memberships:process-expirations

# Test with sample data
php test_user_membership_expiration.php
```

### Automated Testing
The system runs automatically every minute via Laravel scheduler.

## Monitoring & Logging

### Log Files
- **Laravel Log**: `storage/logs/laravel.log`
- **Command Output**: Check command execution logs

### Key Log Messages
```
Cron job: Processing user membership expirations started
User membership expired processed
User membership reminder sent
Cron job: Processing user membership expirations completed successfully
```

## Troubleshooting

### Common Issues

#### 1. Emails Not Sending
- Check SMTP configuration in `BasicSettings`
- Verify email templates exist in database
- Check queue worker is running

#### 2. Notifications Not Appearing
- Verify Pusher configuration
- Check WebSocket connection in browser
- Ensure notification bell component is loaded

#### 3. Command Not Running
- Verify Laravel scheduler is running
- Check cron job setup
- Test command manually

### Debug Commands
```bash
# Check queue status
php artisan queue:work --stop-when-empty

# Test email sending
php artisan tinker
>>> Mail::raw('Test', function($msg) { $msg->to('test@example.com')->subject('Test'); });

# Check notification system
php artisan tinker
>>> $user = App\Models\User::first();
>>> $user->notify(new App\Notifications\UserMembershipExpiredNotification(['title' => 'Test']));
```

## Integration with Existing System

### Compatibility
- Works alongside seller membership system
- Uses same notification infrastructure
- Follows existing email patterns

### Differences from Seller System
- Different email templates
- User-specific routes and links
- Separate processing command
- User-specific notification data

## Security Considerations

### Data Protection
- Email addresses are validated before sending
- Sensitive data is not logged
- Notifications are user-specific

### Rate Limiting
- Jobs are queued to prevent overload
- Duplicate processing is prevented
- Error handling prevents infinite loops

## Performance Optimization

### Database Queries
- Uses eager loading for relationships
- Indexed on `expire_date` and status fields
- Processes in batches

### Queue Processing
- Emails sent via background jobs
- Prevents blocking of main process
- Configurable retry attempts

## Future Enhancements

### Potential Features
- SMS notifications
- Push notifications
- Custom reminder intervals
- Advanced renewal workflows
- Analytics and reporting

### Scalability
- Horizontal scaling via queue workers
- Database optimization for large datasets
- Caching for frequently accessed data

## Support

For issues or questions:
1. Check the logs first
2. Test manually with provided scripts
3. Verify configuration settings
4. Review this documentation

The system is designed to be robust and self-healing, with comprehensive logging for troubleshooting. 