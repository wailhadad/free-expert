<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@php
    $guards = ['web', 'seller', 'admin'];
    $notifiable = null;
    $guard = null;
    foreach ($guards as $g) {
        if (Auth::guard($g)->check()) {
            $notifiable = Auth::guard($g)->user();
            $guard = $g;
            break;
        }
    }
    // Fallback: if no guard matched, use default auth()->user()
    if (!$notifiable && auth()->check()) {
        $notifiable = auth()->user();
        $guard = 'web';
    }
    // After forcing the guard by URL prefix, re-fetch the notifiable for that guard
    $currentUrl = request()->path();
    if (str_starts_with($currentUrl, 'admin')) {
        $guard = 'admin';
        $notifiable = Auth::guard('admin')->user();
    } elseif (str_starts_with($currentUrl, 'seller')) {
        $guard = 'seller';
        $notifiable = Auth::guard('seller')->user();
    } elseif (str_starts_with($currentUrl, 'user')) {
        $guard = 'web';
        $notifiable = Auth::guard('web')->user();
    }
    $unreadCount = $notifiable ? $notifiable->unreadNotifications->count() : 0;
    $notifications = $notifiable ? $notifiable->unreadNotifications->take(5) : collect();
    // Set correct 'View All' route for each guard
    if ($guard === 'seller') {
        $viewAllRoute = route('seller.notifications.index');
    } elseif ($guard === 'admin') {
        $viewAllRoute = route('admin.notifications.index');
    } else {
        $viewAllRoute = route('user.notifications');
    }
    // Bulletproof: Use current route name as fallback for 'View All' link
    $routeName = \Illuminate\Support\Facades\Route::currentRouteName();
    // Bootstrap version: 4 for seller/admin, 5 for user
    $isBootstrap4 = in_array($guard, ['seller', 'admin']);
    $dropdownAttr = $isBootstrap4 ? 'data-toggle="dropdown"' : 'data-bs-toggle="dropdown"';
    // Set bell color vars
    $role = $guard === 'web' ? 'user' : $guard;
    $isUser = $role === 'user';
    $isSeller = $role === 'seller';
    $isAdmin = $role === 'admin';
    // Modern blue for seller/admin, black for user
    $mainColor = $isUser ? '#222' : '#2563eb';
    $hoverColor = $isUser ? '#111' : '#3b82f6';
@endphp


<style>
.notification-bell {    
    position: relative;
    margin: 0 0.5em;
    display: flex;
    align-items: center;
}
.notification-bell .bell-icon-wrapper {
    position: relative;
    display: inline-flex;
    align-items: center;
    height: 32px;
    width: 32px;
    justify-content: center;
}
.notification-bell .dropdown-toggle {
    font-size: 1.25em;
    background: none;
    border: none;
    box-shadow: none;
    padding: 0.12em 0.3em;
    border-radius: 50%;
    outline: none;
    display: flex;
    align-items: center;
}
.notification-bell .fa-bell,
.notification-bell .notif-chevron {
    color: {{ $mainColor }} !important;
    transition: color 0.2s;
    font-size: 1.15em;
}
.notification-bell .dropdown-toggle:hover .fa-bell,
.notification-bell .dropdown-toggle:focus .fa-bell,
.notification-bell .dropdown-toggle:hover .notif-chevron,
.notification-bell .dropdown-toggle:focus .notif-chevron {
    color: {{ $hoverColor }} !important;
}
.notification-bell .fa-bell {
    font-weight: 400;
    filter: none;
    text-shadow: none;
    font-size: 1.15em;
    width: 1.35em;
    height: 1.35em;
}
.notification-bell .notif-chevron {
    margin-left: 0.12em;
    font-size: 0.85em;
}
.notification-bell .dropdown-menu, .notification-bell .notif-dropdown {
    background: #fff !important;
    color: #222 !important;
    opacity: 1 !important;
    box-shadow: 0 4px 24px rgba(0,0,0,0.13);
    border: 1.5px solid #e0e7ef !important;
}
@media (prefers-color-scheme: dark) {
    .notification-bell .dropdown-menu, .notification-bell .notif-dropdown {
        background: #232a3d !important;
        color: #fff !important;
        opacity: 1 !important;
        border: 1.5px solid #334155 !important;
    }
    .notification-bell .dropdown-menu .dropdown-item,
    .notification-bell .notif-dropdown .dropdown-item {
        color: #fff !important;
    }
    .notification-bell .dropdown-menu .dropdown-item:focus,
    .notification-bell .dropdown-menu .dropdown-item:hover,
    .notification-bell .notif-dropdown .dropdown-item:focus,
    .notification-bell .notif-dropdown .dropdown-item:hover {
        background: #181c2a !important;
        color: #3b82f6 !important;
    }
}
.notification-bell .dropdown-menu .dropdown-item,
.notification-bell .notif-dropdown .dropdown-item {
    background: #fff !important;
    color: #222 !important;
    border-radius: 0.25em;
    margin: 0.1em 0.2em;
    padding: 0.55em 1em 0.55em 0.7em;
    font-size: 0.97em;
    transition: background 0.15s, color 0.15s;
}
.notification-bell .dropdown-menu .dropdown-item:focus,
.notification-bell .dropdown-menu .dropdown-item:hover,
.notification-bell .notif-dropdown .dropdown-item:focus,
.notification-bell .notif-dropdown .dropdown-item:hover {
    background: #f5f5f5 !important;
    color: #3b82f6 !important;
}
.notification-bell .dropdown-divider {
    margin: 0.2em 0;
    border-color: #eee;
}
.notification-bell .bell-badge {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 18px;
    height: 18px;
    line-height: 18px;
    font-size: 12px;
    border-radius: 50%;
    font-weight: 700;
    background: #e11d48;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 2;
    border: 2px solid #fff;
    box-shadow: 0 2px 8px rgba(220,53,69,0.18);
    pointer-events: none;
    padding: 0 4px;
    transition: background 0.18s, color 0.18s, box-shadow 0.18s;
}
@media (max-width: 700px) {
    .notification-bell .bell-badge {
        font-size: 10px;
        min-width: 14px;
        height: 14px;
        line-height: 14px;
        border-width: 1.5px;
        top: -4px;
        right: -4px;
    }
}
/* Move seller/admin dropdown to the left */
.notification-bell-wrapper.seller-admin .notif-dropdown {
    right: 50px !important;
}
/* Always blue header for dropdown */
.notification-bell .notif-dropdown .dropdown-header,
.notification-bell .dropdown-menu .dropdown-header {
    background: #2563eb !important;
    color: #ffffff !important;
    border-radius: 0.5em 0.5em 0 0 !important;
}
/* Notification dropdown action button states */
.notif-dropdown .btn-icon {
    box-shadow: none;
    outline: none;
    border-radius: 50% !important;
    transition: background 0.15s, color 0.15s, border 0.15s;
}
/* Check (green) button */
.notif-dropdown .btn-success {
    background: #22c55e !important;
    color: #fff !important;
    border: none !important;
}
.notif-dropdown .btn-success:hover, .notif-dropdown .btn-success:focus, .notif-dropdown .btn-success:active {
    background: #15803d !important;
    color: #fff !important;
    border: none !important;
}
/* Delete (red) button */
.notif-dropdown .btn-outline-danger {
    background: #fff !important;
    color: #e11d48 !important;
    border: 2px solid #e11d48 !important;
}
.notif-dropdown .btn-outline-danger:hover, .notif-dropdown .btn-outline-danger:focus, .notif-dropdown .btn-outline-danger:active {
    background: #e11d48 !important;
    color: #fff !important;
    border: 2px solid #e11d48 !important;
}
</style>

<style id="notif-bell-override">
li.notification-bell-wrapper .notif-dropdown,
li.notification-bell-wrapper .dropdown-menu {
    background: #fff !important;
    color: #222 !important;
    opacity: 1 !important;
    box-shadow: 0 4px 24px rgba(0,0,0,0.13) !important;
    border: 1.5px solid #e0e7ef !important;
    z-index: 99999 !important;
    right: 0 !important;
    padding: 0.5em 0.5em 1em 0.5em !important;
    border-radius: 0.75em !important;
    min-width: 320px !important;
    max-width: 420px !important;
    list-style: none !important;
}
li.notification-bell-wrapper.seller-admin .notif-dropdown,
li.notification-bell-wrapper.seller-admin .dropdown-menu {
    right: 40px !important;
}
@media (prefers-color-scheme: dark) {
    li.notification-bell-wrapper .notif-dropdown,
    li.notification-bell-wrapper .dropdown-menu {
        background: #fff !important;
        color: #222 !important;
        border: 1.5px solid #e0e7ef !important;
    }
}
li.notification-bell-wrapper .notif-dropdown .dropdown-header,
li.notification-bell-wrapper .dropdown-menu .dropdown-header {
    background: #2563eb !important;
    color: #fff !important;
    border-radius: 0.75em 0.75em 0 0 !important;
    padding: 1em 1.2em !important;
    font-size: 1.1em !important;
    font-weight: 600 !important;
    margin-bottom: 0.5em !important;
}
li.notification-bell-wrapper .notif-dropdown .dropdown-item,
li.notification-bell-wrapper .dropdown-menu .dropdown-item {
    border-radius: 0.35em !important;
    margin: 0.15em 0.2em !important;
    padding: 0.7em 1.1em 0.7em 0.9em !important;
    font-size: 1em !important;
    background: transparent !important;
    color: #222 !important;
    transition: background 0.15s, color 0.15s;
    list-style: none !important;
}
li.notification-bell-wrapper .notif-dropdown .dropdown-item:focus,
li.notification-bell-wrapper .notif-dropdown .dropdown-item:hover,
li.notification-bell-wrapper .dropdown-menu .dropdown-item:focus,
li.notification-bell-wrapper .dropdown-menu .dropdown-item:hover {
    background: #f5f5f5 !important;
    color: #2563eb !important;
}
li.notification-bell-wrapper .notif-dropdown .dropdown-divider,
li.notification-bell-wrapper .dropdown-menu .dropdown-divider {
    margin: 0.3em 0 !important;
    border-color: #eee !important;
}
</style>

@if($isUser)
<style>
.user-notif-dropdown .btn-success {
    background: #22c55e !important;
    color: #fff !important;
    border: none !important;
}
.user-notif-dropdown .btn-success:hover, .user-notif-dropdown .btn-success:focus, .user-notif-dropdown .btn-success:active, .user-notif-dropdown .btn-success.active {
    background: #15803d !important;
    color: #fff !important;
    border: none !important;
    box-shadow: 0 0 0 0.15em #bbf7d0 !important;
}
.user-notif-dropdown .btn-outline-danger {
    background: #fff !important;
    color: #e11d48 !important;
    border: 2px solid #e11d48 !important;
}
.user-notif-dropdown .btn-outline-danger:hover, .user-notif-dropdown .btn-outline-danger:focus, .user-notif-dropdown .btn-outline-danger:active, .user-notif-dropdown .btn-outline-danger.active {
    background: #e11d48 !important;
    color: #fff !important;
    border: 2px solid #e11d48 !important;
    box-shadow: 0 0 0 0.15em #fecdd3 !important;
}
</style>
@endif

@if($notifiable)
    @php
        // Get Pusher settings from database
        $bs = DB::table('basic_settings')->select('pusher_key', 'pusher_cluster')->first();
    @endphp
<!-- Notification bell and dropdown, Alpine.js only, no Bootstrap dropdown attributes -->
<li class="nav-item notification-bell-wrapper notification-bell @if(in_array($guard, ['seller','admin'])) seller-admin @endif" style="list-style:none;" x-data="{ open: false }" @click.away="open = false">
    <a class="nav-link position-relative d-flex align-items-center" href="#" @click.prevent="open = !open" aria-expanded="false" tabindex="0">
        <span class="bell-icon-wrapper">
            @if($isUser)
                <svg xmlns="http://www.w3.org/2000/svg" width="1.15em" height="1.15em" fill="none" stroke="{{ $mainColor }}" stroke-width="1.7" class="bi bi-bell fs-5" viewBox="0 0 16 16" style="vertical-align:middle;">
                    <path d="M8 16a2 2 0 0 0 1.985-1.75H6.015A2 2 0 0 0 8 16zm.104-14.804A1.5 1.5 0 0 0 5.5 2c0 .628-.134 1.197-.356 1.684C4.21 4.68 3 6.07 3 8v3.086l-.707.707A1 1 0 0 0 3 13h10a1 1 0 0 0 .707-1.707L13 11.086V8c0-1.93-1.21-3.32-2.144-4.316A3.01 3.01 0 0 0 10.5 2a1.5 1.5 0 0 0-2.396-.804z"/>
                </svg>
            @else
                <svg xmlns="http://www.w3.org/2000/svg" width="1.5em" height="1.5em" fill="{{ $mainColor }}" class="bi bi-bell fs-4" viewBox="0 0 16 16" style="vertical-align:middle;"><path d="M8 16a2 2 0 0 0 1.985-1.75H6.015A2 2 0 0 0 8 16zm.104-14.804A1.5 1.5 0 0 0 5.5 2c0 .628-.134 1.197-.356 1.684C4.21 4.68 3 6.07 3 8v3.086l-.707.707A1 1 0 0 0 3 13h10a1 1 0 0 0 .707-1.707L13 11.086V8c0-1.93-1.21-3.32-2.144-4.316A3.01 3.01 0 0 0 10.5 2a1.5 1.5 0 0 0-2.396-.804z"/></svg>
            @endif
            <span class="notif-unread-badge bell-badge" x-show="window.notifUnreadCount ?? {{ $unreadCount }} > 0" x-text="window.notifUnreadCount ?? {{ $unreadCount }}"></span>
        </span>
        <span class="notif-chevron-wrapper ms-1 d-flex align-items-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="1.1em" height="1.1em" fill="{{ $mainColor }}" class="bi bi-chevron-down transition-arrow" viewBox="0 0 16 16" :style="'transform: ' + (open ? 'rotate(180deg)' : 'rotate(0deg)')"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/></svg>
        </span>
    </a>
    <ul class="notif-dropdown shadow border-0 @if($isUser) user-notif-dropdown @endif"
        x-show="open"
        x-transition
        style="min-width:340px;max-width:420px;position:absolute;right:0;top:100%;z-index:99999;">
        <li class="dropdown-header bg-primary text-white fw-bold py-2 px-3 rounded-top">Notifications</li>
        <li>
            <div class="nav-notification-list" style="max-height:320px;overflow-y:auto;">
                @php $unreadNotifications = $notifiable ? $notifiable->unreadNotifications->take(5) : collect(); @endphp
                @forelse($unreadNotifications as $notification)
                    <div class="dropdown-item d-flex align-items-start gap-2 py-2 position-relative fw-bold bg-light" data-id="{{ $notification->id }}" style="cursor:pointer;">
                        <span class="mt-1"><i class="bi bi-dot text-primary"></i></span>
                        <span class="flex-grow-1 notif-link-area" onclick="window.location.href='{{ $notification->data['url'] ?? '#' }}'" style="min-width:0;">
                            <span class="d-block text-truncate">{{ $notification->data['title'] ?? ucfirst($notification->type) }}</span>
                            <small class="text-muted d-block text-truncate">{{ $notification->data['message'] ?? '' }}</small>
                            <small class="text-secondary">{{ $notification->created_at->diffForHumans() }}</small>
                        </span>
                                <button class="btn btn-icon btn-success btn-sm mark-read-btn ms-1" data-id="{{ $notification->id }}" title="Mark as Read"
                                    style="border-radius:50%;width:2em;height:2em;display:flex;align-items:center;justify-content:center;transition:background 0.15s;margin-right:0.2em;background:#22c55e !important;color:#fff !important;border:none !important;">
                                    <i class="bi bi-check2"></i>
                                </button>
                    </div>
                @empty
                    <div class="dropdown-item text-muted text-center py-4">No notifications</div>
                @endforelse
            </div>
        </li>
        <li><hr class="dropdown-divider my-1"></li>
        <li class="d-flex justify-content-center px-2 pb-2 text-center">
            <button class="btn btn-sm btn-outline-primary w-100 notif-btn notif-btn-primary" id="dropdown-mark-all-read">Mark All as Read</button>
        </li>
        <li><a class="dropdown-item text-center fw-bold text-primary" href="{{ $viewAllRoute }}">View All <i class="bi bi-arrow-right"></i></a></li>
    </ul>
</li>
@endif

@push('scripts')
<!-- Add meta tags for real-time notifications -->
@if($notifiable)
<meta name="notifiable-type" content="{{ class_basename($notifiable) }}">
<meta name="notifiable-id" content="{{ $notifiable->id }}">
<meta name="csrf-token" content="{{ csrf_token() }}">
@endif

<script>
    // Removed all Bootstrap/jQuery dropdown JS. Alpine.js controls dropdown.
    document.querySelectorAll('.notification-bell-wrapper .dropdown-item').forEach(function(item) {
        item.addEventListener('click', function(e) {
            if (this.getAttribute('href') === '#') {
                e.preventDefault();
            }
        });
    });
</script>
<script>
    // Define Pusher variables like in message.js
    let pusherKey = '{{ $bs->pusher_key ?? env('PUSHER_APP_KEY') }}';
    let pusherCluster = '{{ $bs->pusher_cluster ?? env('PUSHER_APP_CLUSTER') }}';
    window.notifUnreadCount = {{ $unreadCount }};
</script>
<script>
// Debug: Check if real-time notifications are initialized
console.log('Notification bell component loaded');
console.log('Pusher variables:', { pusherKey, pusherCluster });
console.log('Notifiable info:', {
    type: '{{ class_basename($notifiable) }}',
    id: '{{ $notifiable->id ?? "none" }}'
});

// Initialize real-time notifications manually if needed
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, checking for real-time notifications initialization');
    if (typeof RealTimeNotifications !== 'undefined') {
        console.log('RealTimeNotifications class found');
        // Initialize the real-time notifications system only if not already initialized
        if (!window.realTimeNotifications) {
        window.realTimeNotifications = new RealTimeNotifications();
        console.log('RealTimeNotifications initialized');
        } else {
            console.log('RealTimeNotifications already initialized, skipping');
        }
    } else {
        console.warn('RealTimeNotifications class not found');
    }
});
</script>
<script>
document.querySelectorAll('.notif-link-area').forEach(function(area) {
    area.addEventListener('click', function(e) {
        // Prevent click if clicking on a button
        if (e.target.closest('button')) return;
        let url = this.getAttribute('onclick').match(/window.location.href='([^']+)'/);
        if (url && url[1] && url[1] !== '#') {
            window.location.href = url[1];
        }
    });
});
</script>
<!-- Removed old notification.js to prevent conflicts with real-time-notifications.js -->
@endpush 