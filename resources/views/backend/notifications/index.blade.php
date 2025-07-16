@extends('backend.layout')

<!-- Add meta tags for real-time notifications -->
@if(auth()->guard('admin')->check())
<meta name="notifiable-type" content="Admin">
<meta name="notifiable-id" content="{{ auth()->guard('admin')->id() }}">
@endif

@php
    // Get Pusher settings from database
    $bs = DB::table('basic_settings')->select('pusher_key', 'pusher_cluster')->first();
@endphp
@section('content')
<meta name="csrf-token" content="{{ csrf_token() }}">
<style>
    .notif-page-header {
        margin-top: 2.5rem;
        margin-bottom: 1.5rem;
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
    }
    .notif-actions {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
    }
    .notif-list {
        margin-top: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .notif-card {
        background: #fff;
        border-radius: 0.75rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        padding: 1.25rem 1.5rem;
        border-left: 5px solid #2563eb;
        position: relative;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
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
        color: #888;
        margin-top: 2rem;
        font-size: 1.1rem;
    }
    @media (max-width: 600px) {
        .notif-card { padding: 1rem; }
        .notif-page-header { flex-direction: column; align-items: flex-start; gap: 1rem; }
    }
    .notif-clickable:hover {
        background: #f5faff;
        box-shadow: 0 4px 16px rgba(37,99,235,0.07);
        transition: background 0.15s, box-shadow 0.15s;
    }
</style>
<div class="container" style="margin-top: 2rem; margin-bottom: 2rem;">
    <div class="notif-page-header">
        <h2 class="fw-bold" style="font-size: 2rem;">Notifications</h2>
        <div class="notif-actions">
            <button type="button" id="dropdown-mark-all-read" class="btn btn-sm btn-outline-primary">Mark All as Read</button>
            <!-- <form method="POST" action="{{ route('admin.notifications.clear_all') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-sm btn-outline-danger">Clear All</button>
            </form> -->
        </div>
    </div>
    <div class="notif-list" id="notification-list">
        @if(isset($notifications) && $notifications->count() === 0)
            <div class="notif-empty">No notifications found.</div>
        @endif
            @foreach($notifications ?? [] as $notification)
                <div class="notif-card{{ $notification->read_at ? ' read' : '' }} notif-clickable" style="cursor:pointer;"
                onclick="if(!event.target.closest('button')){window.location.href='{{ $notification->data['url'] ?? '#' }}';}">
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
                        <button type="button" class="btn btn-sm btn-success mark-read-btn" data-id="{{ $notification->id }}" title="Mark as Read"><i class="bi bi-check2"></i></button>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
    </div>
    <div class="mt-4">
        @if(isset($notifications))
            {{ $notifications->links() }}
        @endif
    </div>
</div>
@endsection
@push('scripts')
<script>
    // Define Pusher variables like in message.js
    let pusherKey = '{{ $bs->pusher_key ?? env('PUSHER_APP_KEY') }}';
    let pusherCluster = '{{ $bs->pusher_cluster ?? env('PUSHER_APP_CLUSTER') }}';
</script>

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