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
      // Only show chats with at least one message
      const nonEmptyChats = data.chats.filter(chat => chat.messages && chat.messages.length > 0);
      if (!nonEmptyChats.length) {
        list.innerHTML = '<div class="text-muted text-center py-4">No discussions yet.</div>';
        return;
      }
      nonEmptyChats.forEach(chat => {
        const user = chat.user;
        const subuser = chat.subuser;
        const seller = chat.seller;
        const lastMsg = chat.messages && chat.messages.length ? chat.messages[chat.messages.length-1].message : '';
        const item = document.createElement('a');
        item.href = '#';
        item.className = 'list-group-item list-group-item-action d-flex align-items-center gap-3';
        let userHtml = `<img src="${user?.avatar_url || '/assets/img/default-avatar.png'}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">`;
        let subuserHtml = '';
        if (subuser) {
          subuserHtml = `<img src="${subuser.avatar_url || '/assets/img/default-avatar.png'}" class="rounded-circle ms-2" style="width:40px;height:40px;object-fit:cover;">`;
        }
        let userLinks = `<a href="/admin/user-management/user/${user?.id}/details" class="username-link">${user?.username || 'User'}</a>`;
        if (subuser) {
          userLinks += ` <span class='mx-1'>(as)</span> <a href="/admin/user-management/subuser/${subuser.id}/details" class="username-link">${subuser.username}</a>`;
        }
        item.innerHTML = `
          ${userHtml}
          ${subuserHtml}
          <span class="mx-2">➔</span>
          <img src="${seller?.avatar_url || '/assets/img/default-avatar.png'}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">
          <div class="flex-grow-1">
            <div class="fw-bold">
              ${userLinks}
              ➔
              <a href="/admin/seller-management/seller/${seller?.id}/details?language=en" class="username-link">${seller?.username || 'Seller'}</a>
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
          window.openDirectChatModal(chat.id, seller?.username, seller?.avatar_url, seller?.id, subuser ? subuser.username : null);
        });
        list.appendChild(item);
      });

      // Auto-open chat modal if chat_id is present in URL
      const urlParams = new URLSearchParams(window.location.search);
      const chatIdParam = urlParams.get('chat_id');
      const subuserIdParam = urlParams.get('subuser_id');
      if (chatIdParam) {
        const chatToOpen = nonEmptyChats.find(chat => String(chat.id) === String(chatIdParam));
        if (chatToOpen) {
          // Find seller and subuser info
          const seller = chatToOpen.seller;
          const subuser = chatToOpen.subuser;
          // Use username for subuser if present
          window.openDirectChatModal(
            chatToOpen.id,
            seller?.username,
            seller?.avatar_url,
            seller?.id,
            subuserIdParam ? (subuser && String(subuser.id) === String(subuserIdParam) ? subuser.username : null) : null
          );
        }
      }
    });
});
</script>
@endpush
@endsection 