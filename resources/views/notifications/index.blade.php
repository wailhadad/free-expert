@php
    $notifRoutePrefix = $guard === 'seller'
        ? '/seller/notifications'
        : ($guard === 'admin' ? '/admin/notifications' : '/user/notifications');
@endphp

@if($guard === 'web')
@extends('frontend.layout')
@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            @include('frontend.user.side-navbar')
        </div>
        <div class="col-md-9">
            <h2 class="mb-3 text-center">Your Notifications</h2>
            <div class="card">
                <div class="card-body">
                    @forelse($notifications as $notification)
                        <div class="border-bottom py-2 notif-clickable" style="cursor:pointer;"
                            onclick="if(!event.target.closest('form') && !event.target.closest('button')){window.location.href='{{ $notification->data['url'] ?? '#' }}';}">
                            {!! $notification->data['message'] !!}
                            <small class="text-muted float-end">{{ $notification->created_at->format('d M Y, H:i') }}</small>
                        </div>
                    @empty
                        <p class="text-center">No notifications found.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@elseif($guard === 'seller')
@extends('seller.layout')
@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            @include('seller.partials.side-navbar')
        </div>
        <div class="col-md-9">
            <h2 class="mb-3 text-center">Your Notifications</h2>
            <div class="card">
                <div class="card-body">
                    @forelse($notifications as $notification)
                        <div class="border-bottom py-2 notif-clickable" style="cursor:pointer;"
                            onclick="if(!event.target.closest('form') && !event.target.closest('button')){window.location.href='{{ $notification->data['url'] ?? '#' }}';}">
                            {!! $notification->data['message'] !!}
                            <small class="text-muted float-end">{{ $notification->created_at->format('d M Y, H:i') }}</small>
                        </div>
                    @empty
                        <p class="text-center">No notifications found.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@elseif($guard === 'admin')
@extends('backend.layout')
@section('content')
<div class="container mt-4">
    <div class="row">
        <div class="col-md-3">
            @include('backend.partials.side-navbar')
        </div>
        <div class="col-md-9">
            <h2 class="mb-3 text-center">Your Notifications</h2>
            <div class="card">
                <div class="card-body">
                    @forelse($notifications as $notification)
                        <div class="border-bottom py-2 notif-clickable" style="cursor:pointer;"
                            onclick="if(!event.target.closest('form') && !event.target.closest('button')){window.location.href='{{ $notification->data['url'] ?? '#' }}';}">
                            {!! $notification->data['message'] !!}
                            <small class="text-muted float-end">{{ $notification->created_at->format('d M Y, H:i') }}</small>
                        </div>
                    @empty
                        <p class="text-center">No notifications found.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@endif
@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
  var loader = document.querySelector('.request-loader');
  if(loader) loader.style.display = 'none';
});
</script>
@endsection
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function updateUnreadBadge(delta) {
        let badge = document.getElementById('unread-badge');
        let bellBadge = document.getElementById('notif-unread-badge');
        let count = parseInt(badge.textContent) || 0;
        count = Math.max(count + delta, 0);
        badge.textContent = count;
        if (bellBadge) {
            bellBadge.textContent = count;
            bellBadge.style.display = count > 0 ? 'inline-block' : 'none';
        }
        window.notifUnreadCount = count;
    }
    document.addEventListener('DOMContentLoaded', function () {
        // Mark as read
        document.querySelectorAll('.mark-read-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                fetch(`{{ $notifRoutePrefix }}/${this.dataset.id}/mark-as-read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                }).then(() => {
                    this.closest('.list-group-item').classList.remove('bg-light');
                    this.closest('.list-group-item').querySelector('.fw-bold').classList.remove('fw-bold');
                    this.remove();
                    updateUnreadBadge(-1);
                });
            });
        });
        // Delete
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                if (confirm('Delete this notification?')) {
                    fetch(`{{ $notifRoutePrefix }}/${this.dataset.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        },
                    }).then(() => {
                        let wasUnread = this.closest('.list-group-item').classList.contains('bg-light');
                        this.closest('.list-group-item').remove();
                        if (wasUnread) updateUnreadBadge(-1);
                    });
                }
            });
        });
        // Mark all as read
        document.getElementById('mark-all-read').addEventListener('click', function () {
            fetch(`{{ $notifRoutePrefix }}/mark-all-as-read`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
            }).then(() => {
                document.querySelectorAll('.list-group-item.bg-light').forEach(item => {
                    item.classList.remove('bg-light');
                    let bold = item.querySelector('.fw-bold');
                    if (bold) bold.classList.remove('fw-bold');
                    let btn = item.querySelector('.mark-read-btn');
                    if (btn) btn.remove();
                });
                updateUnreadBadge(-window.notifUnreadCount);
            });
        });
        // Clear all
        document.getElementById('clear-all').addEventListener('click', function () {
            if (confirm('Clear all notifications?')) {
                fetch(`{{ $notifRoutePrefix }}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                }).then(() => {
                    document.getElementById('notification-list').innerHTML = '<div class="text-center text-muted py-5">No notifications found.</div>';
                    updateUnreadBadge(-window.notifUnreadCount);
                });
            }
        });
    });
</script>
<style>
.notif-clickable:hover {
    background: #f5faff;
    box-shadow: 0 4px 16px rgba(37,99,235,0.07);
    transition: background 0.15s, box-shadow 0.15s;
}
</style>
@endpush 