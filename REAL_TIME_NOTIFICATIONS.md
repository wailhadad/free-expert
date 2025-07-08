# Real-time Notifications System

This system provides real-time notifications for all user types (Admin, Seller, User) using WebSocket technology with Pusher and Laravel Echo.

## Features

- ✅ Real-time notifications in dropdown menu
- ✅ Real-time notifications on notification pages
- ✅ Automatic badge updates
- ✅ Toast notifications
- ✅ Support for all user types (Admin, Seller, User)
- ✅ Mark as read functionality
- ✅ Mark all as read functionality
- ✅ Proper WebSocket channel authorization

## Setup Requirements

### 1. Environment Variables

Make sure these environment variables are set in your `.env` file:

```env
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=your_pusher_app_id
PUSHER_APP_KEY=your_pusher_app_key
PUSHER_APP_SECRET=your_pusher_app_secret
PUSHER_APP_CLUSTER=your_pusher_cluster
```

### 2. Broadcasting Configuration

The broadcasting configuration is already set up in `config/broadcasting.php`.

### 3. Broadcast Channels

Broadcast channels are configured in `routes/channels.php`:

```php
// Admin notification channel
Broadcast::channel('App.Models.Admin.{id}', function ($admin, $id) {
    return (int) $admin->id === (int) $id;
});

// Seller notification channel
Broadcast::channel('App.Models.Seller.{id}', function ($seller, $id) {
    return (int) $seller->id === (int) $id;
});

// User notification channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
```

## How to Use

### 1. Sending Notifications

Use the `NotificationService` to send real-time notifications:

```php
use App\Services\NotificationService;

class YourController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function someAction()
    {
        // Send to a specific user
        $user = User::find(1);
        $this->notificationService->sendRealTime($user, [
            'type' => 'order',
            'title' => 'New Order',
            'message' => 'You have received a new order',
            'url' => '/orders/123',
            'icon' => 'bi bi-cart',
            'extra' => ['order_id' => 123]
        ]);

        // Send to all admins
        $this->notificationService->notifyAdmins([
            'type' => 'system',
            'title' => 'System Update',
            'message' => 'System maintenance scheduled',
            'url' => '/admin/maintenance',
            'icon' => 'bi bi-gear'
        ]);

        // Send to all users
        $this->notificationService->notifyUsers([
            'type' => 'announcement',
            'title' => 'New Feature',
            'message' => 'Check out our new features',
            'url' => '/features',
            'icon' => 'bi bi-star'
        ]);
    }
}
```

### 2. Notification Data Structure

```php
$notificationData = [
    'type' => 'order',           // Notification type (optional)
    'title' => 'New Order',      // Notification title
    'message' => 'Message here', // Notification message
    'url' => '/orders/123',      // URL to navigate to (optional)
    'icon' => 'bi bi-cart',      // Icon class (optional)
    'extra' => [                 // Extra data (optional)
        'order_id' => 123,
        'amount' => 99.99
    ]
];
```

## Frontend Integration

### 1. Notification Bell Component

The notification bell component (`resources/views/components/notification-bell.blade.php`) automatically includes:

- WebSocket connection setup
- Real-time notification handling
- Dropdown updates
- Badge updates

### 2. Notification Pages

All notification pages (Admin, Seller, User) include:

- Real-time notification updates
- WebSocket connection
- Automatic page updates

### 3. JavaScript Integration

The system uses `public/assets/js/real-time-notifications.js` which provides:

- WebSocket connection management
- Real-time notification handling
- Dropdown and page updates
- Badge management
- Toast notifications

## Testing

### 1. Test Page

Visit `/test-notifications` to test the real-time notification system:

- Send test notifications to current user
- Send notifications to all users of specific types
- Monitor WebSocket connection status
- View real-time notification logs

### 2. Test Routes

```php
// Send test notification to current user
POST /test-notification

// Send test notification to all users of specific type
POST /test-notification-all
Body: {"type": "admin|seller|user|all"}
```

## Troubleshooting

### 1. Notifications not appearing

- Check WebSocket connection status
- Verify Pusher credentials
- Ensure broadcast driver is set to 'pusher'
- Check browser console for errors

### 2. Channel authorization issues

- Verify broadcast channels are properly configured
- Check user authentication
- Ensure user ID matches channel pattern

### 3. Badge not updating

- Check if notification bell component is included
- Verify badge element exists with correct ID
- Check JavaScript console for errors

## File Structure

```
app/
├── Notifications/
│   └── RealTimeNotification.php    # Main notification class
├── Services/
│   └── NotificationService.php     # Service for sending notifications
└── Http/Controllers/
    └── TestNotificationController.php  # Test controller

resources/views/
├── components/
│   └── notification-bell.blade.php     # Notification bell component
├── backend/notifications/
│   └── index.blade.php                 # Admin notifications page
├── seller/notifications/
│   └── index.blade.php                 # Seller notifications page
├── frontend/user/
│   └── notifications.blade.php         # User notifications page
└── test-notifications.blade.php        # Test page

public/assets/js/
└── real-time-notifications.js          # Main JavaScript file

routes/
├── web.php                             # Test routes
└── channels.php                        # Broadcast channels
```

## Security

- All channels are private and require authentication
- Channel authorization is handled by Laravel's broadcast system
- CSRF protection is enabled for all routes
- User can only access their own notification channels

## Performance

- Notifications are queued for better performance
- WebSocket connections are optimized
- Badge updates are debounced
- Notification lists are limited to prevent memory issues 