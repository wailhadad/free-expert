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

<script src="{{ asset('assets/js/real-time-notifications.js') }}"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> 