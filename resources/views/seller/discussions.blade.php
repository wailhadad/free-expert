@extends('seller.layout')
@section('content')
<div class="container py-4" style="min-height: 60vh;">
  <h2 class="mb-4">Your Direct Discussions</h2>
  <div id="seller-discussions-list" class="list-group">
    <!-- JS will populate this list -->
  </div>
</div>
@include('components.direct-chat-modal')
@push('scripts')
<script src="{{ asset('assets/js/direct-chat.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  fetch('/seller/direct-chat/discussions')
    .then(res => res.json())
    .then(data => {
      console.log('Seller discussions data:', data);
      const list = document.getElementById('seller-discussions-list');
      list.innerHTML = '';
      // Only show chats with at least one message
      const nonEmptyChats = data.chats.filter(chat => chat.messages && chat.messages.length > 0);
      if (!nonEmptyChats.length) {
        list.innerHTML = '<div class="text-muted text-center py-4">No discussions yet.</div>';
        return;
      }
      nonEmptyChats.forEach(chat => {
        const user = chat.user;
        const lastMsg = chat.messages && chat.messages.length ? chat.messages[chat.messages.length-1].message : '';
        const item = document.createElement('a');
        item.href = '#';
        item.className = 'list-group-item list-group-item-action d-flex align-items-center gap-3';
        item.innerHTML = `
          <img src="${user?.avatar_url || '/assets/img/default-avatar.png'}" class="rounded-circle" style="width:48px;height:48px;object-fit:cover;">
          <div class="flex-grow-1">
            <div class="fw-bold">${user?.username || 'User'}</div>
            <div class="text-muted small text-truncate">${lastMsg}</div>
          </div>
        `;
        item.addEventListener('click', function(e) {
          e.preventDefault();
          window.openDirectChatModal(chat.id, user?.username, chat.seller?.avatar_url, chat.seller?.id, user?.username);
        });
        list.appendChild(item);
      });
    });
});
</script>
@endpush
@endsection 