<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
    <div class="container">
        <a class="navbar-brand" href="/">{{ config('app.name', 'Laravel') }}</a>
        <div class="collapse navbar-collapse">
            <ul class="navbar-nav ms-auto align-items-center">
                <!-- @include('components.notification-bell') removed to prevent duplicate bell -->
                <!-- ... existing nav items ... -->
            </ul>
        </div>
    </div>
</nav>

@push('scripts')
<script src="https://js.pusher.com/7.2/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.3/dist/echo.iife.js"></script>
<script>
    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key: '{{ env('PUSHER_APP_KEY') }}',
        cluster: '{{ env('PUSHER_APP_CLUSTER') }}',
        forceTLS: true,
        encrypted: true,
    });
    @if(auth()->check())
    Echo.private('App.Models.User.{{ auth()->id() }}')
        .notification((notification) => {
            // Update badge
            let badge = document.getElementById('nav-unread-badge');
            if (badge) badge.textContent = parseInt(badge.textContent) + 1;
            // Prepend to dropdown
            let list = document.getElementById('nav-notification-list');
            if (list) {
                let item = document.createElement('a');
                item.href = notification.url || '#';
                item.className = 'dropdown-item small fw-bold';
                item.innerHTML = `${notification.title}<br><span class='text-muted'>${notification.message || ''}</span>`;
                list.prepend(item);
                // Limit to 5
                while (list.children.length > 5) list.removeChild(list.lastChild);
            }
            // Toast
            if (window.Swal) {
                Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'info',
                    title: notification.title,
                    text: notification.message,
                    showConfirmButton: false,
                    timer: 4000
                });
            }
        });
    @endif
</script>
@endpush

<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>

<!-- Real-time notifications JS is loaded in the main layout files -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
<style>
.notification-bell .bell-icon-wrapper .notif-unread-badge.bell-badge {
    position: absolute !important;
    top: -6px !important;
    right: -6px !important;
    min-width: 18px !important;
    height: 18px !important;
    line-height: 18px !important;
    font-size: 12px !important;
    border-radius: 50% !important;
    font-weight: 700 !important;
    background: #e11d48 !important;
    color: #fff !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    z-index: 2 !important;
    border: 2px solid #fff !important;
    box-shadow: 0 2px 8px rgba(220,53,69,0.18) !important;
    pointer-events: none !important;
    padding: 0 4px !important;
    outline: 2px solid blue !important; /* TEMP: for debugging */
    transition: background 0.18s, color 0.18s, box-shadow 0.18s !important;
}
@media (max-width: 700px) {
    .notification-bell .bell-icon-wrapper .notif-unread-badge.bell-badge {
        font-size: 10px !important;
        min-width: 14px !important;
        height: 14px !important;
        line-height: 14px !important;
        border-width: 1.5px !important;
        top: -4px !important;
        right: -4px !important;
    }
}
</style> 