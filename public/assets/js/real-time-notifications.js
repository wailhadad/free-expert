// Real-time Notifications System
// Using the same approach as the working message.js

class RealTimeNotifications {
    constructor() {
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
            console.log('Notifiable info:', this.notifiableType, this.notifiableId);
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
            console.log('Setting up Pusher with:', pusherKey, pusherCluster);
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
                if (data.notifiable_type === this.notifiableType && data.notifiable_id == this.notifiableId) {
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
        console.log('Real-time notification received!', data);
        console.log('Current timestamp:', new Date().toISOString());
        console.log('Notification type:', data.notification?.type);
        
        // Show toast notification immediately
        this.showToastNotification(data.notification);
        
        // For direct chat notifications, also update the chat if it's open
        if (data.notification?.type === 'direct_chat') {
            console.log('Direct chat notification detected, updating chat if open...');
            if (window.currentDirectChatId && window.loadDirectChatMessages) {
                console.log('Reloading chat messages for chat ID:', window.currentDirectChatId);
                window.loadDirectChatMessages(window.currentDirectChatId);
            }
        }
        
        // Add a small delay to ensure database transaction has committed
        console.log('Scheduling dropdown/badge update in 300ms...');
        setTimeout(() => {
            console.log('Executing delayed dropdown/badge update...');
            if (this.notifiableType === 'Seller') {
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
                    console.log('Updated unread badge:', badge, data.count);
                });
                window.notifUnreadCount = data.count;
                console.log('window.notifUnreadCount set to', data.count);
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
        const pageList = document.querySelector('.notifications-list');
        if (!pageList) return;

        const notificationHtml = this.createNotificationHtml(notification, true);
        pageList.insertAdjacentHTML('afterbegin', notificationHtml);
    }

    updateNotificationInDropdown(notification) {
        const notificationElement = document.querySelector(`[data-notification-id="${notification.id}"]`);
        if (notificationElement) {
            const newHtml = this.createNotificationHtml(notification);
            notificationElement.outerHTML = newHtml;
        }
    }

    updateNotificationInPage(notification) {
        const notificationElement = document.querySelector(`[data-notification-id="${notification.id}"]`);
        if (notificationElement) {
            const newHtml = this.createNotificationHtml(notification, true);
            notificationElement.outerHTML = newHtml;
        }
    }

    createNotificationHtml(notification, isPage = false) {
        const isUnread = !notification.read_at;
        const unreadClass = isUnread ? 'fw-bold bg-light' : '';
        const readButton = isUnread ? `
            <button class="btn btn-icon btn-success btn-sm mark-read-btn ms-1" data-id="${notification.id}" title="Mark as Read"
                style="border-radius:50%;width:2em;height:2em;display:flex;align-items:center;justify-content:center;transition:background 0.15s;margin-right:0.2em;background:#22c55e !important;color:#fff !important;border:none !important;">
                <i class="bi bi-check2"></i>
            </button>
        ` : '';

        return `
            <div class="dropdown-item d-flex align-items-start gap-2 py-2 position-relative ${unreadClass}" 
                 data-notification-id="${notification.id}" 
                 data-id="${notification.id}" 
                 style="cursor:pointer;">
                <span class="mt-1"><i class="bi bi-dot text-primary"></i></span>
                <span class="flex-grow-1 notif-link-area" onclick="window.location.href='${notification.url || '#'}'" style="min-width:0;">
                    <span class="d-block text-truncate">${notification.title || 'Notification'}</span>
                    <small class="text-muted d-block text-truncate">${notification.message || ''}</small>
                    <small class="text-secondary">${this.formatTime(notification.created_at)}</small>
                </span>
                ${readButton}
            </div>
        `;
    }

    showToastNotification(notification) {
        // Create a simple toast notification
        const toast = document.createElement('div');
        toast.className = 'toast-notification';
        toast.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: #2563eb;
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10000;
            max-width: 300px;
            animation: slideIn 0.3s ease;
        `;
        
        toast.innerHTML = `
            <div style="font-weight: 600; margin-bottom: 5px;">${notification.title || 'New Notification'}</div>
            <div style="font-size: 14px;">${notification.message || ''}</div>
        `;
        
        document.body.appendChild(toast);
        
        // Remove after 5 seconds
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                if (toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 300);
        }, 5000);
    }

    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
        if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
        return Math.floor(diff / 86400000) + 'd ago';
    }

    setupEventListeners() {
        // Mark as read functionality
        document.addEventListener('click', (e) => {
            if (e.target.closest('.mark-read-btn')) {
                e.preventDefault();
                e.stopPropagation();
                const button = e.target.closest('.mark-read-btn');
                const notificationId = button.getAttribute('data-id');
                this.markAsRead(notificationId);
            }
        });

        // Mark all as read functionality
        document.addEventListener('click', (e) => {
            if (e.target.closest('#dropdown-mark-all-read')) {
                e.preventDefault();
                e.stopPropagation();
                this.markAllAsRead();
            }
        });
    }

    markAsRead(notificationId) {
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
            },
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update the notification in the UI
                const notificationElement = document.querySelector(`[data-id="${notificationId}"]`);
                if (notificationElement) {
                    notificationElement.classList.remove('fw-bold', 'bg-light');
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
            }
        })
        .catch(error => {
            console.error('Error marking notification as read:', error);
        });
    }

    markAllAsRead() {
        // Determine the correct route based on user type
        let route = '';
        if (this.notifiableType === 'Admin') {
            route = '/admin/notifications/mark-all-as-read';
        } else if (this.notifiableType === 'Seller') {
            route = '/seller/notifications/mark-all-as-read';
        } else {
            route = '/user/notifications/mark-all-as-read';
        }

        fetch(route, {
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
                // Update all notifications in the UI
                const notifications = document.querySelectorAll('.dropdown-item[data-id]');
                notifications.forEach(notification => {
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
            }
        })
        .catch(error => {
            console.error('Error marking all notifications as read:', error);
        });
    }

    reloadNotificationDropdown() {
        const prefix = this.getRolePrefix();
        console.log('Reloading notification dropdown from:', `${prefix}/notifications/dropdown`);
        fetch(`${prefix}/notifications/dropdown`)
            .then(response => response.text())
            .then(html => {
                console.log('Dropdown HTML received:', html.substring(0, 200) + '...');
                const dropdowns = document.querySelectorAll('.nav-notification-list');
                if (dropdowns.length === 0) {
                    console.warn('No .nav-notification-list found in DOM when updating dropdown.');
                }
                dropdowns.forEach(dropdown => {
                    dropdown.innerHTML = html;
                    console.log('Dropdown updated successfully');
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
                console.log('Full notification list reloaded in real time');
            })
            .catch(err => {
                console.error('Failed to reload notification list:', err);
            });
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    new RealTimeNotifications();
});

// Add CSS for toast animations
const style = document.createElement('style');
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

// --- Real-time notification system forced instantiation and debug logging ---
console.log('real-time-notifications.js loaded');
window.realTimeNotifications = new RealTimeNotifications();
console.log('window.realTimeNotifications instantiated:', window.realTimeNotifications); 