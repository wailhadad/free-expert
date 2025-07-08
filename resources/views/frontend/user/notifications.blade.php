@extends('frontend.layout')

<!-- Add meta tags for real-time notifications -->
@if(auth()->check())
<meta name="notifiable-type" content="User">
<meta name="notifiable-id" content="{{ auth()->id() }}">
@endif

@php
    // Get Pusher settings from database
    $bs = DB::table('basic_settings')->select('pusher_key', 'pusher_cluster')->first();
@endphp
@section('content')
<style>
    body { background: #f7f9fb; }
    .notif-bg-section {
        background:rgb(196, 202, 208);
        min-height: 60vh;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding-top: 3rem;
        padding-bottom: 3rem;
    }
    .notif-card-main {
        background: #fff;
        border-radius: 1.25rem;
        box-shadow: 0 6px 32px rgba(37,99,235,0.07), 0 1.5px 6px rgba(0,0,0,0.04);
        padding: 2.5rem 2.5rem 2rem 2.5rem;
        max-width: 540px;
        width: 100%;
        margin: 0 auto;
    }
    .notif-page-header {
        margin-bottom: 2.2rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
    }
    .notif-title {
        font-size: 2.2rem;
        font-weight: 800;
        letter-spacing: -1px;
        color: #222;
    }
    .notif-actions {
        display: flex;
        gap: 0.75rem;
        flex-wrap: wrap;
    }
    .notif-btn {
        border-radius: 999px;
        padding: 0.45em 1.2em;
        font-size: 1rem;
        font-weight: 600;
        border: none;
        transition: background 0.18s, color 0.18s;
        cursor: pointer;
    }
    .notif-btn-primary {
        background: #2563eb;
        color: #fff;
    }
    .notif-btn-primary:hover {
        background: #1746a2;
    }
    .notif-btn-danger {
        background: #fff0f0;
        color: #e11d48;
        border: 1.5px solid #e11d48;
    }
    .notif-btn-danger:hover {
        background: #e11d48;
        color: #fff;
    }
    .notif-list {
        margin-top: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1.1rem;
    }
    .notif-card {
        background: #f5f7fa;
        border-radius: 0.75rem;
        box-shadow: 0 1.5px 6px rgba(37,99,235,0.04);
        padding: 1.25rem 1.5rem;
        border-left: 5px solid #2563eb;
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        transition: box-shadow 0.18s;
    }
    .notif-card.read {
        opacity: 0.7;
        border-left-color: #e5e7eb;
    }
    .notif-card .notif-actions {
        margin-top: 0.5rem;
    }
    .notif-empty {
        text-align: center;
        color: #b0b4bb;
        margin-top: 2.5rem;
        font-size: 1.18rem;
        font-weight: 500;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.7rem;
    }
    .notif-empty i {
        font-size: 2.5rem;
        color: #c7d2fe;
    }
    @media (max-width: 700px) {
        .notif-card-main { padding: 1.2rem 0.5rem 1.2rem 0.5rem; }
        .notif-title { font-size: 1.5rem; }
    }
    .notif-clickable:hover {
        background: #e0e7ff !important;
        box-shadow: 0 0 0 2px #2563eb22;
    }
</style>
<div class="notif-bg-section" style="margin-top: 9rem;">
    <div class="notif-card-main">
        <div class="notif-page-header">
            <span class="notif-title">Notifications</span>
            <div class="notif-actions">
                <button type="button" id="dropdown-mark-all-read" class="notif-btn notif-btn-primary">Mark All As Read</button>
                <!-- <form method="POST" action="{{ route('user.notifications.clear_all') }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="notif-btn notif-btn-danger">Clear All</button>
                </form> -->
            </div>
        </div>
        <div class="notif-list" id="notification-list">
            @if($notifications->count() === 0)
                <div class="notif-empty">
                    <i class="bi bi-bell-slash"></i>
                    No notifications found.
                </div>
            @else
                @foreach($notifications as $notification)
                    <div class="notif-card{{ $notification->read_at ? ' read' : '' }} notif-clickable" style="cursor:pointer;" onclick="if(!event.target.closest('form')){window.location.href='{{ $notification->data['url'] ?? '#' }}';}">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold" style="font-size: 1.1rem;">
                                    {{ $notification->data['title'] ?? 'Notification' }}
                                </div>
                                <div style="color: #555; font-size: 0.98rem;">
                                    {{ $notification->data['body'] ?? $notification->data['message'] ?? '' }}
                                </div>
                                <div style="color: #888; font-size: 0.85rem;">
                                    {{ $notification->created_at->diffForHumans() }}
                                </div>
                            </div>
                            <div class="notif-actions">
                                @if(!$notification->read_at)
                                <form method="POST" action="{{ route('user.notifications.mark_as_read', $notification->id) }}">
                                    @csrf
                                    <button type="submit" class="notif-btn notif-btn-primary" title="Mark as Read"><i class="bi bi-check2"></i></button>
                                </form>
                                @endif
                                <!-- <form method="POST" action="{{ route('user.notifications.destroy', $notification->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="notif-btn notif-btn-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form> -->
                            </div>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>
        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    </div>
</div>
@endsection
@push('scripts')
<!-- WebSocket setup for real-time notifications -->
<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<script>
    // Define Pusher variables like in message.js
    let pusherKey = '{{ $bs->pusher_key ?? env('PUSHER_APP_KEY') }}';
    let pusherCluster = '{{ $bs->pusher_cluster ?? env('PUSHER_APP_CLUSTER') }}';
</script>
<script src="{{ asset('assets/js/real-time-notifications.js') }}"></script>

<script>
// Legacy functions for backward compatibility
function removeNotifFromDropdown(notifId) {
    let list = document.getElementById('nav-notification-list');
    if (!list) return;
    let items = list.querySelectorAll('a.dropdown-item');
    items.forEach(function(item) {
        if (item.dataset.id == notifId) {
            item.remove();
        }
    });
    if (list.children.length === 0) {
        list.innerHTML = '<div class="dropdown-item text-muted text-center py-4">No notifications</div>';
    }
}
function updateNotifBadge(change) {
    let badge = document.getElementById('notif-unread-badge');
    if (!badge) return;
    let count = parseInt(window.notifUnreadCount || badge.textContent || 0);
    count = Math.max(count + change, 0);
    window.notifUnreadCount = count;
    badge.textContent = count;
    badge.style.display = count > 0 ? 'inline-block' : 'none';
}
</script>
<script src="/assets/js/notification.js"></script>
@endpush 