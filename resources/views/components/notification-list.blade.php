@foreach($notifications as $notification)
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
                @if(isset($guard) && $guard === 'seller')
                <button type="button" class="btn btn-sm btn-success mark-read-btn" data-id="{{ $notification->id }}" title="Mark as Read"><i class="bi bi-check2"></i></button>
                @else
                <button type="button" class="notif-btn notif-btn-primary mark-read-btn" data-id="{{ $notification->id }}" title="Mark as Read"><i class="bi bi-check2"></i></button>
                @endif
                @endif
            </div>
        </div>
    </div>
@endforeach
@if($notifications->isEmpty())
    <div class="notif-empty">No notifications found.</div>
@endif 