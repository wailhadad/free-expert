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
    if (!$notifiable && auth()->check()) {
        $notifiable = auth()->user();
        $guard = 'web';
    }
    $unreadChatCount = 0;
    if ($notifiable) {
        $unreadChatCount = \App\Models\DirectChatMessage::whereHas('chat', function($q) use ($notifiable, $guard) {
            if ($guard === 'web') {
                $q->where('user_id', $notifiable->id);
            } elseif ($guard === 'seller') {
                $q->where('seller_id', $notifiable->id);
            } elseif ($guard === 'admin') {
                // Admin logic if needed
            }
        })->whereNull('read_at')->where('sender_type', '!=', $guard)->count();
    }
    // Set envelope color vars (matching bell colors)
    $role = $guard === 'web' ? 'user' : $guard;
    $isUser = $role === 'user';
    $isSeller = $role === 'seller';
    $isAdmin = $role === 'admin';
    // Modern blue for seller/admin, black for user
    $mainColor = $isUser ? '#111' : '#2563eb';
    $hoverColor = $isUser ? '#000' : '#3b82f6';
@endphp

@if($notifiable)
<style>
    .discussion-envelope-wrapper .envelope-icon-wrapper {
        position: relative;
        display: inline-flex;
        align-items: center;
        line-height: 1;
        height: 32px;
        width: 32px;
        justify-content: center;
    }
    .discussion-envelope-wrapper .envelope-svg {
        color: {{ $mainColor }};
        font-size: 1.15em;
        vertical-align: middle;
        display: block;
        width: 1.35em;
        height: 1.35em;
        transition: color 0.2s;
    }
    .discussion-envelope-wrapper .envelope-icon-wrapper:hover .envelope-svg {
        color: {{ $hoverColor }};
    }
    .discussion-envelope-wrapper .discussion-unread-badge {
        position: absolute;
        top: -0.38em;
        right: -0.38em;
        min-width: 1.25em;
        height: 1.25em;
        line-height: 1.25em;
        font-size: 0.85em;
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
        transition: background 0.18s, color 0.18s, box-shadow 0.18s;
    }
    @media (max-width: 700px) {
        .discussion-envelope-wrapper .envelope-icon-wrapper {
            height: 28px;
            width: 28px;
        }
        .discussion-envelope-wrapper .discussion-unread-badge {
            font-size: 0.7em;
            min-width: 1em;
            height: 1em;
            line-height: 1em;
            border-width: 1.5px;
            top: -0.2em;
            right: -0.15em;
        }
    }
</style>
<li class="nav-item discussion-envelope-wrapper" style="list-style:none;" x-data="{ open: false }">
    <a class="nav-link position-relative d-flex align-items-center" href="{{ route($guard === 'web' ? 'user.discussions' : ($guard === 'seller' ? 'seller.discussions' : 'admin.discussions')) }}" aria-expanded="false" tabindex="0">
        <span class="envelope-icon-wrapper">
            <svg class="envelope-svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="3"/><polyline points="22,6 12,13 2,6"/></svg>
            @if($guard !== 'web')
            {{-- <span class="discussion-unread-badge bell-badge" id="discussion-unread-badge" x-show="window.discussionUnreadCount ?? {{ $unreadChatCount }} > 0" x-text="window.discussionUnreadCount ?? {{ $unreadChatCount }}"></span> --}}
            @endif
        </span>
    </a>
</li>
<script>
    window.discussionUnreadCount = {{ $unreadChatCount }};
    document.addEventListener('DOMContentLoaded', function() {
        function updateDiscussionBadge() {
            // Determine the correct endpoint based on guard
            let endpoint = '/direct-chat/unread-count';
            if ('{{ $guard }}' === 'seller') {
                endpoint = '/seller/direct-chat/unread-count';
            } else if ('{{ $guard }}' === 'admin') {
                endpoint = '/admin/direct-chat/unread-count';
            }
            
            fetch(endpoint)
                .then(response => response.json())
                .then(data => {
                    document.querySelectorAll('.discussion-unread-badge').forEach(badge => {
                        badge.textContent = data.count;
                        badge.style.display = data.count > 0 ? 'flex' : 'none';
                    });
                    window.discussionUnreadCount = data.count;
                });
        }
        updateDiscussionBadge();
        if (typeof RealTimeNotifications !== 'undefined') {
            if (!window.realTimeDiscussionNotifications) {
                window.realTimeDiscussionNotifications = new RealTimeNotifications();
            }
            const origHandle = window.realTimeDiscussionNotifications.handleNewNotification;
            window.realTimeDiscussionNotifications.handleNewNotification = function(data) {
                origHandle.call(this, data);
                if (data.notification?.type === 'direct_chat') {
                    setTimeout(updateDiscussionBadge, 300);
                }
            };
        } else {
            setInterval(updateDiscussionBadge, 10000);
        }
    });
</script>
@endif 