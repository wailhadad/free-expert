<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Real-time Notifications Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    
    <!-- Add meta tags for real-time notifications -->
    @if(auth()->check())
    <meta name="notifiable-type" content="User">
    <meta name="notifiable-id" content="{{ auth()->id() }}">
    @elseif(auth()->guard('admin')->check())
    <meta name="notifiable-type" content="Admin">
    <meta name="notifiable-id" content="{{ auth()->guard('admin')->id() }}">
    @elseif(auth()->guard('seller')->check())
    <meta name="notifiable-type" content="Seller">
    <meta name="notifiable-id" content="{{ auth()->guard('seller')->id() }}">
    @endif

    @php
        // Get Pusher settings from database
        $bs = DB::table('basic_settings')->select('pusher_key', 'pusher_cluster')->first();
    @endphp
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12 mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Real-time Notifications Test</h2>
                    <div>
                        @include('components.notification-bell')
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Test Chat Notifications</h5>
                    </div>
                    <div class="card-body">
                        <p>Test real-time chat notifications:</p>
                        <button class="btn btn-primary" onclick="testChatNotification()">Send Test Chat Notification</button>
                        <div id="test-result" class="mt-3"></div>
                            </div>
                        </div>
                                </div>
                                <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5>Test Regular Notifications</h5>
                                </div>
                    <div class="card-body">
                        <p>Test regular notifications:</p>
                        <button class="btn btn-success" onclick="testNotification()">Send Test Notification</button>
                        <button class="btn btn-info" onclick="testNotificationAll()">Send to All</button>
                        <div id="test-result-regular" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- WebSocket setup -->
    <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
    <script>
        // Define Pusher variables like in message.js
        let pusherKey = '{{ $bs->pusher_key ?? env('PUSHER_APP_KEY') }}';
        let pusherCluster = '{{ $bs->pusher_cluster ?? env('PUSHER_APP_CLUSTER') }}';

        // Update WebSocket status
        const statusElement = document.getElementById('websocket-status');
        if (typeof pusherKey !== 'undefined' && typeof pusherCluster !== 'undefined') {
            statusElement.innerHTML = '<i class="bi bi-circle-fill text-success"></i> Pusher variables loaded';
            statusElement.className = 'alert alert-success';
        } else {
            statusElement.innerHTML = '<i class="bi bi-circle-fill text-danger"></i> Pusher variables not found';
            statusElement.className = 'alert alert-danger';
        }

        function sendTestNotification() {
            fetch('/test-notification', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addNotificationToList('Test notification sent successfully', 'success');
                } else {
                    addNotificationToList('Failed to send test notification', 'danger');
                }
            })
            .catch(error => {
                addNotificationToList('Error sending test notification: ' + error.message, 'danger');
            });
        }

        function sendTestNotificationToAll(type) {
            fetch('/test-notification-all', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ type: type })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addNotificationToList(`Test notification sent to all ${type}s successfully`, 'success');
                } else {
                    addNotificationToList('Failed to send test notification', 'danger');
                }
            })
            .catch(error => {
                addNotificationToList('Error sending test notification: ' + error.message, 'danger');
            });
        }

        function addNotificationToList(message, type) {
            const list = document.getElementById('notifications-list');
            const item = document.createElement('div');
            item.className = `list-group-item list-group-item-${type}`;
            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <span>${message}</span>
                    <small class="text-muted">${new Date().toLocaleTimeString()}</small>
                </div>
            `;
            
            // Remove "No notifications yet" message if present
            const noNotifications = list.querySelector('.text-muted');
            if (noNotifications && noNotifications.textContent === 'No notifications yet') {
                noNotifications.remove();
            }
            
            list.insertBefore(item, list.firstChild);
            
            // Keep only last 10 notifications
            const items = list.querySelectorAll('.list-group-item');
            while (items.length > 10) {
                list.removeChild(items[items.length - 1]);
            }
        }

        // Initialize real-time notifications
        document.addEventListener('DOMContentLoaded', function() {
            const notifiableType = document.querySelector('meta[name="notifiable-type"]')?.getAttribute('content');
            const notifiableId = document.querySelector('meta[name="notifiable-id"]')?.getAttribute('content');
            
            if (notifiableType && notifiableId && typeof pusherKey !== 'undefined' && typeof pusherCluster !== 'undefined') {
                const channelName = `notification-channel-${notifiableType.toLowerCase()}-${notifiableId}`;
                
                // Enable Pusher logging
                Pusher.logToConsole = true;
                
                // Create Pusher instance like in message.js
                const pusher = new Pusher(pusherKey, {
                    cluster: pusherCluster
                });
                
                // Log connection events
                pusher.connection.bind('connected', () => {
                    addNotificationToList('Pusher connected successfully', 'success');
                });
                
                pusher.connection.bind('error', (err) => {
                    addNotificationToList('Pusher connection error: ' + err.message, 'danger');
                });
                
                const channel = pusher.subscribe(channelName);
                
                channel.bind('pusher:subscription_succeeded', () => {
                    addNotificationToList('Successfully subscribed to notification channel', 'success');
                });
                
                channel.bind('pusher:subscription_error', (status) => {
                    addNotificationToList('Failed to subscribe to notification channel: ' + status, 'danger');
                });
                
                // Listen for notification events
                channel.bind('notification.received', (data) => {
                    addNotificationToList('Real-time notification received: ' + JSON.stringify(data), 'info');
                });
            }
        });

        // Test chat notification function
        function testChatNotification() {
            const resultDiv = document.getElementById('test-result');
            resultDiv.innerHTML = '<div class="alert alert-info">Sending test chat notification...</div>';
            
            fetch('/test-chat-notification', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div class="alert alert-success">Chat notification sent successfully! Check your notification bell.</div>';
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger">Error: ' + (data.error || 'Unknown error') + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
            });
        }

        // Test regular notification function
        function testNotification() {
            const resultDiv = document.getElementById('test-result-regular');
            resultDiv.innerHTML = '<div class="alert alert-info">Sending test notification...</div>';
            
            fetch('/test-notification', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div class="alert alert-success">Notification sent successfully! Check your notification bell.</div>';
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger">Error: ' + (data.error || 'Unknown error') + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
            });
        }

        // Test notification to all function
        function testNotificationAll() {
            const resultDiv = document.getElementById('test-result-regular');
            resultDiv.innerHTML = '<div class="alert alert-info">Sending test notification to all...</div>';
            
            fetch('/test-notification-all', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ type: 'all' })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    resultDiv.innerHTML = '<div class="alert alert-success">Notification sent to all successfully!</div>';
                } else {
                    resultDiv.innerHTML = '<div class="alert alert-danger">Error: ' + (data.error || 'Unknown error') + '</div>';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
            });
        }
    </script>

<!-- Ensure real-time notifications JS is loaded for badge updates -->
<script src="{{ asset('assets/js/real-time-notifications.js') }}"></script>
</body>
</html> 