// Real-time Notifications System
// Using the same approach as the working message.js

// Check if class already exists to prevent redeclaration
if (typeof RealTimeNotifications === 'undefined') {
    class RealTimeNotifications {
        constructor() {
            // Prevent multiple instances
            if (window.realTimeNotifications) {
                console.warn('RealTimeNotifications already exists, returning existing instance');
                return window.realTimeNotifications;
            }
            
            this.pusher = null;
            this.channel = null;
            this.notifiableType = null;
            this.notifiableId = null;
            this.init();
        }

        init() {
            console.log('Initializing real-time notifications system...');
            
            // Get notifiable info from meta tags
            const notifiableTypeMeta = document.querySelector('meta[name="notifiable-type"]');
            const notifiableIdMeta = document.querySelector('meta[name="notifiable-id"]');
            
            if (notifiableTypeMeta && notifiableIdMeta) {
                this.notifiableType = notifiableTypeMeta.getAttribute('content');
                this.notifiableId = notifiableIdMeta.getAttribute('content');
                console.log('Notifiable info:', { type: this.notifiableType, id: this.notifiableId });
                this.setupPusher();
                this.setupEventListeners();
            } else {
                console.warn('Notifiable meta tags not found');
            }
        }

        setupPusher() {
            // Read pusherKey and pusherCluster from meta tags directly
            const pusherKeyMeta = document.querySelector('meta[name="pusher-key"]');
            const pusherClusterMeta = document.querySelector('meta[name="pusher-cluster"]');
            const pusherKey = pusherKeyMeta ? pusherKeyMeta.getAttribute('content') : null;
            const pusherCluster = pusherClusterMeta ? pusherClusterMeta.getAttribute('content') : null;
            
            console.log('Meta pusher-key:', pusherKeyMeta);
            console.log('Meta pusher-cluster:', pusherClusterMeta);
            console.log('Pusher key:', pusherKey, 'Pusher cluster:', pusherCluster);
            
            if (pusherKey && pusherCluster) {
                console.log('Setting up Pusher with key:', pusherKey, 'and cluster:', pusherCluster);
                Pusher.logToConsole = true;
                this.pusher = new Pusher(pusherKey, {
                    cluster: pusherCluster
                });
                this.channel = this.pusher.subscribe('notification-channel');
                console.log('Subscribed to notification-channel');
                
                this.channel.bind('notification.received', (data) => {
                    console.log('Real-time notification received:', data);
                    console.log('Current notifiable:', this.notifiableType, this.notifiableId);
                    console.log('Received notifiable:', data.notifiable_type, data.notifiable_id);
                    
                    // For admin users, handle all direct chat notifications regardless of recipient
                    if (this.notifiableType === 'Admin' && data.notification?.type === 'direct_chat') {
                        console.log('Admin: Handling direct chat notification for any user...');
                        this.handleNewNotification(data);
                    } else if (data.notifiable_type === this.notifiableType && data.notifiable_id == this.notifiableId) {
                        console.log('Notification is for current user, handling...');
                        this.handleNewNotification(data);
                    } else {
                        console.log('Notification is not for current user, ignoring...');
                    }
                });
                
                this.pusher.connection.bind('connected', () => {
                    console.log('Pusher connected successfully');
                });
                
                this.pusher.connection.bind('error', (err) => {
                    console.error('Pusher connection error:', err);
                });
                
                this.channel.bind('pusher:subscription_succeeded', () => {
                    console.log('Successfully subscribed to notification channel');
                });
                
                this.channel.bind('pusher:subscription_error', (status) => {
                    console.error('Failed to subscribe to notification channel:', status);
                });
            } else {
                console.error('Pusher key or cluster not found. Make sure pusherKey and pusherCluster are defined.');
            }
        }

        handleNewNotification(data) {
            // Skip notification sound and toast for admin users on direct chat notifications
            if (this.notifiableType === 'Admin' && data.notification?.type === 'direct_chat') {
                console.log('Admin: Skipping notification sound and toast for direct chat');
            } else {
                this.playNotificationSound();
                console.log('Real-time notification received!', data);
                console.log('Current timestamp:', new Date().toISOString());
                console.log('Notification type:', data.notification?.type);
                
                // Show toast notification immediately
                this.showToastNotification(data.notification);
            }
            
            // For direct chat notifications, also update the chat if it's open
            if (data.notification?.type === 'direct_chat') {
                console.log('Direct chat notification detected, updating chat if open...');
                
                // For admin users, check if the notification's chat_id matches the currently open chat
                if (this.notifiableType === 'Admin') {
                    const notificationChatId = data.notification?.data?.chat_id;
                    console.log('Admin: Notification chat ID:', notificationChatId, 'Current chat ID:', window.currentDirectChatId);
                    
                    if (window.currentDirectChatId && window.loadDirectChatMessages) {
                        // For admin, update if it's the same chat or if no specific chat is open
                        if (!notificationChatId || String(notificationChatId) === String(window.currentDirectChatId)) {
                            console.log('Admin: Reloading chat messages for chat ID:', window.currentDirectChatId);
                            window.loadDirectChatMessages(window.currentDirectChatId, false); // Don't show loading for real-time updates
                        }
                    }
                } else {
                    // For non-admin users, only update if it's their chat
                    if (window.currentDirectChatId && window.loadDirectChatMessages) {
                        console.log('Reloading chat messages for chat ID:', window.currentDirectChatId);
                        window.loadDirectChatMessages(window.currentDirectChatId, false); // Don't show loading for real-time updates
                    }
                }
                
                // Also update discussions list if on discussions page
                if (typeof window.updateDiscussionBadge === 'function') {
                    console.log('Updating discussions list in real-time...');
                    setTimeout(() => {
                        window.updateDiscussionBadge();
                    }, 500); // Small delay to ensure database is updated
                }
            }
            
            // Add a small delay to ensure database transaction has committed
            console.log('Scheduling dropdown/badge update in 300ms...');
            setTimeout(() => {
                console.log('Executing delayed dropdown/badge update...');
                
                // Skip dropdown/badge updates for admin users on direct chat notifications
                if (this.notifiableType === 'Admin' && data.notification?.type === 'direct_chat') {
                    console.log('Admin: Skipping dropdown/badge updates for direct chat notifications');
                } else if (this.notifiableType === 'Seller') {
                    console.log('Seller: updating dropdown and badge in real time (delayed)');
                    this.reloadNotificationDropdown();
                    this.updateNotificationBadge();
                    this.reloadNotificationListPage();
                } else {
                    // Keep user logic as is (if any manual addNotificationToDropdown, etc.)
                    this.updateNotificationBadge();
                    this.reloadNotificationDropdown();
                    this.reloadNotificationListPage();
                }
            }, 300); // 300ms delay to allow database transaction to commit
        }

        playNotificationSound() {
            try {
                const audio = new Audio('/assets/notification.mp3');
                audio.volume = 1.0;
                audio.play().catch(e => { /* ignore autoplay errors */ });
                
                // Stop the sound after 3 seconds
                setTimeout(() => {
                    audio.pause();
                    audio.currentTime = 0;
                }, 4000);
            } catch (e) {
                console.warn('Notification sound could not be played:', e);
            }
        }

        getRolePrefix() {
            // Determine the role prefix for endpoints (case-insensitive)
            const type = (this.notifiableType || '').toLowerCase();
            let prefix = '/user';
            if (type === 'admin') prefix = '/admin';
            if (type === 'seller') prefix = '/seller';
            console.log('getRolePrefix:', type, '->', prefix);
            return prefix;
        }

        updateNotificationBadge() {
            const prefix = this.getRolePrefix();
            fetch(`${prefix}/notifications/unread-count`)
                .then(response => response.json())
                .then(data => {
                    document.querySelectorAll('.notif-unread-badge').forEach(badge => {
                        badge.textContent = data.count;
                        badge.style.display = data.count > 0 ? 'flex' : 'none';
                        // console.log('Updated unread badge:', badge, data.count);
                    });
                    window.notifUnreadCount = data.count;
                    // console.log('window.notifUnreadCount set to', data.count);
                });
        }

        updateNotificationCount(count) {
            // Update badge count
            const badge = document.getElementById('notif-unread-badge');
            if (badge) {
                window.notifUnreadCount = count;
                badge.textContent = count;
                badge.style.display = count > 0 ? 'flex' : 'none';
            }
        }

        addNotificationToDropdown(notification) {
            console.log('Adding notification to dropdown:', notification);
            const dropdownList = document.getElementById('nav-notification-list');
            if (!dropdownList) {
                console.warn('Dropdown list not found');
                return;
            }

            console.log('Found dropdown list, creating notification HTML...');
            const notificationHtml = this.createNotificationHtml(notification);
            console.log('Notification HTML created:', notificationHtml);
            
            // Add to top of list
            dropdownList.insertAdjacentHTML('afterbegin', notificationHtml);
            console.log('Notification added to dropdown');
            
            // Remove oldest notification if more than 5
            const notifications = dropdownList.querySelectorAll('.dropdown-item');
            if (notifications.length > 5) {
                notifications[notifications.length - 1].remove();
                console.log('Removed oldest notification');
            }
        }

        addNotificationToPage(notification) {
            console.log('Adding notification to page:', notification);
            const notificationList = document.getElementById('notification-list');
            if (!notificationList) {
                console.warn('Notification list not found');
                return;
            }

            const notificationHtml = this.createNotificationHtml(notification, true);
            notificationList.insertAdjacentHTML('afterbegin', notificationHtml);
            console.log('Notification added to page');
        }

        updateNotificationInDropdown(notification) {
            console.log('Updating notification in dropdown:', notification);
            const notificationElement = document.querySelector(`[data-id="${notification.id}"]`);
            if (notificationElement) {
                const newHtml = this.createNotificationHtml(notification);
                notificationElement.outerHTML = newHtml;
            }
        }

        updateNotificationInPage(notification) {
            console.log('Updating notification in page:', notification);
            const notificationElement = document.querySelector(`[data-id="${notification.id}"]`);
            if (notificationElement) {
                const newHtml = this.createNotificationHtml(notification, true);
                notificationElement.outerHTML = newHtml;
            }
        }

        createNotificationHtml(notification, isPage = false) {
            const timeAgo = this.formatTime(notification.created_at);
            const isRead = notification.read_at !== null;
            const readClass = isRead ? '' : 'fw-bold bg-light';
            const readButton = isRead ? '' : '<button class="btn btn-sm btn-outline-primary mark-read-btn" onclick="markNotificationAsRead(' + notification.id + ')">Mark as Read</button>';
            
            return `
                <div class="dropdown-item ${readClass}" data-id="${notification.id}">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">${notification.data.title || 'New Notification'}</h6>
                            <p class="mb-1 text-muted">${notification.data.message || notification.data.body || 'You have a new notification'}</p>
                            <small class="text-muted">${timeAgo}</small>
                        </div>
                        ${readButton}
                    </div>
                </div>
            `;
        }

        showToastNotification(notification) {
            // Prevent duplicate notifications
            const notificationKey = `${notification.id}-${notification.created_at}`;
            if (window.recentNotifications && window.recentNotifications.has(notificationKey)) {
                console.log('Duplicate notification detected, skipping toast');
                return;
            }
            
            // Initialize recent notifications map if not exists
            if (!window.recentNotifications) {
                window.recentNotifications = new Map();
            }
            
            // Add to recent notifications
            window.recentNotifications.set(notificationKey, Date.now());
            
            // Clean up old entries (older than 5 seconds)
            setTimeout(() => {
                window.recentNotifications.delete(notificationKey);
            }, 5000);

            // Create toast container if it doesn't exist
            let toastContainer = document.getElementById('toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.id = 'toast-container';
                toastContainer.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    z-index: 9999;
                    max-width: 350px;
                `;
                document.body.appendChild(toastContainer);
            }

            // Create toast element
            const toast = document.createElement('div');
            toast.className = 'toast-notification';
            toast.style.cssText = `
                background: rgba(0, 0, 0, 0.9);
                color: white;
                padding: 15px;
                margin-bottom: 10px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                transform: translateX(100%);
                opacity: 0;
                transition: all 0.3s ease;
                border-left: 4px solid #007bff;
                max-width: 350px;
                word-wrap: break-word;
            `;

            // Use the exact same content as displayed in notifications dropdown and list pages
            // This ensures consistency across all notification types: orders, customer offers, services, etc.
            const title = notification.data?.title || notification.title || 'Notification';
            const message = notification.data?.message || notification.data?.body || '';
            
            toast.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div style="flex: 1;">
                        <div style="font-weight: bold; margin-bottom: 5px; color: #ffffff;">${title}</div>
                        <div style="font-size: 14px; color: #cccccc; line-height: 1.4;">${message}</div>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" style="background: none; border: none; color: #ffffff; font-size: 18px; cursor: pointer; margin-left: 10px; padding: 0;">×</button>
                </div>
            `;

            // Add to container
            toastContainer.appendChild(toast);

            // Animate in
            setTimeout(() => {
                toast.style.transform = 'translateX(0)';
                toast.style.opacity = '1';
            }, 100);

            // Auto remove after 5 seconds
            setTimeout(() => {
                toast.style.transform = 'translateX(100%)';
                toast.style.opacity = '0';
                setTimeout(() => {
                    if (toast.parentElement) {
                        toast.remove();
                    }
                }, 300);
            }, 5000);
        }

        formatTime(timestamp) {
            const now = new Date();
            const time = new Date(timestamp);
            const diffInSeconds = Math.floor((now - time) / 1000);

            if (diffInSeconds < 60) return 'Just now';
            if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + 'm ago';
            if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + 'h ago';
            return Math.floor(diffInSeconds / 86400) + 'd ago';
        }

        setupEventListeners() {
            console.log('Setting up notification event listeners...');
            
            // Add event listeners for notification interactions
            document.addEventListener('click', (e) => {
                // Only log if it's a mark as read button to reduce noise
                if (e.target.classList.contains('mark-read-btn') || e.target.closest('.mark-read-btn')) {
                    console.log('Click event detected on mark as read button:', e.target);
                }
                
                // Handle individual mark as read buttons
                if (e.target.classList.contains('mark-read-btn') || e.target.closest('.mark-read-btn')) {
                    console.log('Mark as read button clicked');
                    
                    // Get the button element
                    const button = e.target.classList.contains('mark-read-btn') ? e.target : e.target.closest('.mark-read-btn');
                    const notificationId = button.dataset.id;
                    
                    if (notificationId) {
                        console.log('Marking notification as read:', notificationId);
                        this.markAsRead(notificationId);
                    } else {
                        console.warn('Could not find notification ID on button');
                    }
                }
                
                // Handle "Mark All as Read" button
                if (e.target.id === 'dropdown-mark-all-read' || e.target.closest('#dropdown-mark-all-read')) {
                    console.log('Mark all as read button clicked');
                    e.preventDefault();
                    this.markAllAsRead();
                }
            });
            
            // Also add specific event listeners for better compatibility
            document.addEventListener('DOMContentLoaded', () => {
                console.log('DOM loaded, adding specific event listeners...');
                
                // Add event listener for mark all as read button
                const markAllButton = document.getElementById('dropdown-mark-all-read');
                if (markAllButton) {
                    console.log('Found mark all as read button, adding event listener');
                    markAllButton.addEventListener('click', (e) => {
                        console.log('Mark all as read button clicked via specific listener');
                        e.preventDefault();
                        this.markAllAsRead();
                    });
                } else {
                    console.warn('Mark all as read button not found');
                }
                
                // Add event listeners for individual mark as read buttons
                document.querySelectorAll('.mark-read-btn').forEach(button => {
                    console.log('Adding event listener to mark read button:', button);
                    button.addEventListener('click', (e) => {
                        console.log('Mark as read button clicked via specific listener');
                        e.preventDefault();
                        
                        const notificationId = button.dataset.id;
                        if (notificationId) {
                            console.log('Marking notification as read:', notificationId);
                            this.markAsRead(notificationId);
                        } else {
                            console.warn('Could not find notification ID on button');
                        }
                    });
                });
            });
        }

        markAsRead(notificationId) {
            console.log('markAsRead called with notificationId:', notificationId);
            
            // Determine the correct route based on user type
            let route = '';
            if (this.notifiableType === 'Admin') {
                route = `/admin/notifications/${notificationId}/mark-as-read`;
            } else if (this.notifiableType === 'Seller') {
                route = `/seller/notifications/${notificationId}/mark-as-read`;
            } else {
                route = `/user/notifications/${notificationId}/mark-as-read`;
            }

            fetch(route, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                }
            })
            .then(response => {
                return response.json();
            })
            .then(data => {
                console.log('Mark as read response:', data);
                if (data.success) {
                    console.log('Mark as read successful, updating UI...');
                    // Update the notification in the UI
                    const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
                    if (notificationElement) {
                        // Handle different notification element types
                        if (notificationElement.classList.contains('dropdown-item')) {
                            // Dropdown notification
                            notificationElement.classList.remove('fw-bold', 'bg-light');
                        } else if (notificationElement.classList.contains('notif-card')) {
                            // Notification list page
                            notificationElement.classList.add('read');
                        } else if (notificationElement.classList.contains('list-group-item')) {
                            // Other notification types
                            notificationElement.classList.remove('fw-bold', 'bg-light');
                        }
                        
                        const readButton = notificationElement.querySelector('.mark-read-btn');
                        if (readButton) {
                            readButton.remove();
                        }
                    }
                    // Update count and reload dropdown/badge
                    if (this.notifiableType === 'Seller') {
                        this.reloadNotificationDropdown();
                        this.updateNotificationBadge();
                    } else {
                        if (data.unreadCount !== undefined) {
                            this.updateNotificationCount(data.unreadCount);
                        }
                        this.reloadNotificationDropdown();
                        this.updateNotificationBadge();
                    }
                    this.reloadNotificationListPage();
                    // --- NEW: If this is a direct_chat notification, update the envelope unread badge ---
                    if (data.type === 'direct_chat' || (notificationElement && notificationElement.textContent.toLowerCase().includes('direct'))) {
                        if (typeof updateDiscussionBadge === 'function') {
                            setTimeout(updateDiscussionBadge, 300);
                        }
                    }
                } else {
                    console.warn('Mark as read failed:', data);
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
        }

        markAllAsRead() {
            console.log('markAllAsRead called');
            
            // Determine the correct route based on user type
            let route = '';
            if (this.notifiableType === 'Admin') {
                route = '/admin/notifications/mark-all-as-read';
            } else if (this.notifiableType === 'Seller') {
                route = '/seller/notifications/mark-all-as-read';
            } else {
                route = '/user/notifications/mark-all-as-read';
            }
            
            console.log('Using route:', route);

            fetch(route, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
            })
            .then(response => {
                console.log('Mark all as read response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Mark all as read response data:', data);
                if (data.success) {
                    console.log('Mark all as read successful, updating UI...');
                    // Update all notifications in the UI
                    const dropdownNotifications = document.querySelectorAll('.dropdown-item[data-id]');
                    const cardNotifications = document.querySelectorAll('.notif-card[data-id]');
                    const listNotifications = document.querySelectorAll('.list-group-item[data-id]');
                    
                    console.log('Found notifications to update:', dropdownNotifications.length + cardNotifications.length + listNotifications.length);
                    
                    // Update dropdown notifications
                    dropdownNotifications.forEach(notification => {
                        notification.classList.remove('fw-bold', 'bg-light');
                        const readButton = notification.querySelector('.mark-read-btn');
                        if (readButton) {
                            readButton.remove();
                        }
                    });
                    
                    // Update card notifications (notification list page)
                    cardNotifications.forEach(notification => {
                        notification.classList.add('read');
                        const readButton = notification.querySelector('.mark-read-btn');
                        if (readButton) {
                            readButton.remove();
                        }
                    });
                    
                    // Update list notifications
                    listNotifications.forEach(notification => {
                        notification.classList.remove('fw-bold', 'bg-light');
                        const readButton = notification.querySelector('.mark-read-btn');
                        if (readButton) {
                            readButton.remove();
                        }
                    });
                    // Update count and reload dropdown/badge
                    if (this.notifiableType === 'Seller') {
                        this.reloadNotificationDropdown();
                        this.updateNotificationBadge();
                    } else {
                        this.updateNotificationCount(0);
                        this.reloadNotificationDropdown();
                        this.updateNotificationBadge();
                    }
                    this.reloadNotificationListPage();
                } else {
                    console.warn('Mark all as read failed:', data);
                }
            })
            .catch(error => {
                console.error('Error marking all notifications as read:', error);
            });
        }

        reloadNotificationDropdown() {
            const prefix = this.getRolePrefix();
            // Reduced logging to prevent spam
            // console.log('Reloading notification dropdown from:', `${prefix}/notifications/dropdown`);
            fetch(`${prefix}/notifications/dropdown`)
                .then(response => response.text())
                .then(html => {
                    // console.log('Dropdown HTML received:', html.substring(0, 200) + '...');
                    const dropdowns = document.querySelectorAll('.nav-notification-list');
                    if (dropdowns.length === 0) {
                        console.warn('No .nav-notification-list found in DOM when updating dropdown.');
                    }
                    dropdowns.forEach(dropdown => {
                        dropdown.innerHTML = html;
                        // console.log('Dropdown updated successfully');
                    });
                })
                .catch(error => {
                    console.error('Error reloading dropdown:', error);
                });
        }

        reloadNotificationListPage() {
            // If on the full notification list page, reload the list in real time
            const notifList = document.getElementById('notification-list');
            if (notifList) {
                let route = '';
                if (this.notifiableType === 'Admin') {
                    route = '/admin/notifications/list';
                } else if (this.notifiableType === 'Seller') {
                    route = '/seller/notifications/list';
                } else {
                    route = '/user/notifications/list';
                }
                fetch(route, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                })
                .then(response => response.text())
                .then(html => {
                    notifList.innerHTML = html;
                    // console.log('Full notification list reloaded in real time');
                })
                .catch(err => {
                    console.error('Failed to reload notification list:', err);
                });
            }
        }
    } // End of RealTimeNotifications class

    // Create alias for backward compatibility
    if (typeof RealtimeNotifications === 'undefined') {
        window.RealtimeNotifications = RealTimeNotifications;
    }

    // Make class globally available
    window.RealTimeNotifications = RealTimeNotifications;
    
    // Ensure only one instance is created globally
    if (!window.realTimeNotificationsInstance) {
        window.realTimeNotificationsInstance = null;
    }
}

// Add CSS for toast animations (only if not already added)
if (!document.querySelector('#real-time-notifications-style')) {
    const style = document.createElement('style');
    style.id = 'real-time-notifications-style';
    style.textContent = `
        @keyframes slideIn {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOut {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style); 
}

// Initialize when DOM is ready (only if not already initialized)
if (!window.realTimeNotificationsInstance) {
    document.addEventListener('DOMContentLoaded', function() {
        // Double-check to prevent duplicate initialization
        if (!window.realTimeNotificationsInstance && typeof RealTimeNotifications !== 'undefined') {
            try {
                window.realTimeNotificationsInstance = new RealTimeNotifications();
                window.realTimeNotifications = window.realTimeNotificationsInstance;
                console.log('RealTimeNotifications initialized successfully');
            } catch (error) {
                console.error('Error initializing RealTimeNotifications:', error);
            }
        } else {
            console.log('RealTimeNotifications already initialized or class not available');
        }
    });
} else {
    console.log('RealTimeNotifications already exists, skipping DOM ready initialization');
}

// Also check if DOM is already loaded and initialize immediately if needed
if (document.readyState === 'loading') {
    // DOM is still loading, wait for DOMContentLoaded
} else {
    // DOM is already loaded, initialize immediately if not already done
    if (!window.realTimeNotificationsInstance && typeof RealTimeNotifications !== 'undefined') {
        try {
            window.realTimeNotificationsInstance = new RealTimeNotifications();
            window.realTimeNotifications = window.realTimeNotificationsInstance;
            console.log('RealTimeNotifications initialized immediately (DOM already loaded)');
        } catch (error) {
            console.error('Error initializing RealTimeNotifications immediately:', error);
        }
    }
}

// --- Real-time notification system debug logging ---
console.log('real-time-notifications.js loaded'); 