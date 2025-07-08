@extends('backend.layout')
@section('content')
<div class="container py-4">
  <h2 class="mb-4">All Direct Discussions</h2>
  <div id="admin-discussions-list" class="list-group">
    <!-- JS will populate this list -->
  </div>
</div>
@include('components.direct-chat-modal')
@push('scripts')
<script src="{{ asset('assets/js/direct-chat.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  fetch('/admin/direct-chat/discussions')
    .then(res => res.json())
    .then(data => {
      console.log('Admin discussions data:', data);
      const list = document.getElementById('admin-discussions-list');
      list.innerHTML = '';
      if (!data.chats.length) {
        list.innerHTML = '<div class="text-muted text-center py-4">No discussions yet.</div>';
        return;
      }
      data.chats.forEach(chat => {
        const user = chat.user;
        const seller = chat.seller;
        const lastMsg = chat.messages && chat.messages.length ? chat.messages[chat.messages.length-1].message : '';
        const item = document.createElement('a');
        item.href = '#';
        item.className = 'list-group-item list-group-item-action d-flex align-items-center gap-3';
        item.innerHTML = `
          <img src="${user?.avatar_url || '/assets/img/default-avatar.png'}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">
          <span class="mx-2">➔</span>
          <img src="${seller?.avatar_url || '/assets/img/default-avatar.png'}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">
          <div class="flex-grow-1">
            <div class="fw-bold">
              <a href="/admin/user-management/user/${user?.id}/details" class="username-link" target="_blank">${user?.username || 'User'}</a>
              ➔
              <a href="/admin/seller-management/seller/${seller?.id}/details?language=en" class="username-link" target="_blank">${seller?.username || 'Seller'}</a>
            </div>
            <div class="text-muted small text-truncate">${lastMsg}</div>
          </div>
        `;
        item.addEventListener('click', function(e) {
          // If the click was on a username link, let the link work normally
          if (e.target.closest('.username-link')) {
            return;
          }
          e.preventDefault();
          window.openDirectChatModal(chat.id, (user?.username || 'User') + ' ➔ ' + (seller?.username || 'Seller'), user?.avatar_url);
        });
        list.appendChild(item);
      });
    });
});
</script>
@endpush
@endsection 