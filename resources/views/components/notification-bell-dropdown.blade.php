<div class="nav-notification-list" style="max-height:320px;overflow-y:auto;">
    @forelse($notifications as $notification)
        <div class="dropdown-item d-flex align-items-start gap-2 py-2 position-relative @if(is_null($notification->read_at)) fw-bold bg-light @endif" data-id="{{ $notification->id }}" style="cursor:pointer;">
            <span class="mt-1"><i class="bi bi-dot text-primary"></i></span>
            <span class="flex-grow-1 notif-link-area" onclick="window.location.href='{{ $notification->data['url'] ?? '#' }}'" style="min-width:0;">
                <span class="d-block text-truncate">{{ $notification->data['title'] ?? ucfirst($notification->type) }}</span>
                <small class="text-muted d-block text-truncate">{{ $notification->data['message'] ?? '' }}</small>
                <small class="text-secondary">{{ $notification->created_at->diffForHumans() }}</small>
            </span>
            @if(is_null($notification->read_at))
                <button class="btn btn-icon btn-success btn-sm mark-read-btn ms-1" data-id="{{ $notification->id }}" title="Mark as Read">
                    <i class="bi bi-check2"></i>
                </button>
            @endif
        </div>
    @empty
        <div class="dropdown-item text-muted text-center py-4">No notifications</div>
    @endforelse
</div> 