@extends('frontend.layout')
@section('content')
{{-- Breadcrumb --}}
@include('frontend.partials.breadcrumb', ['title' => 'Your Discussions', 'breadcrumb' => $breadcrumb])
<div class="container py-4" style="margin-top: 40px;">
  <div class="row">
    @include('frontend.user.side-navbar')
    <div class="col-lg-9">
      <h3 class="mb-4">Your Discussions</h3>
      <div id="user-discussions-list" class="list-group">
        <!-- JS will populate this list -->
      </div>
    </div>
  </div>
</div>
@include('components.direct-chat-modal')
@push('scripts')
<script src="{{ asset('assets/js/direct-chat.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  fetch('/direct-chat/discussions')
    .then(res => res.json())
    .then(data => {
      const list = document.getElementById('user-discussions-list');
      list.innerHTML = '';
      if (!data.chats.length) {
        list.innerHTML = '<div class="text-muted text-center py-4">No discussions yet.</div>';
        return;
      }
      data.chats.forEach(chat => {
        const seller = chat.seller;
        const lastMsg = chat.messages && chat.messages.length ? chat.messages[chat.messages.length-1].message : '';
        const item = document.createElement('a');
        item.href = '#';
        item.className = 'list-group-item list-group-item-action d-flex align-items-center gap-3';
        item.innerHTML = `
          <img src="${seller.avatar_url || '/assets/img/default-avatar.png'}" class="rounded-circle" style="width:48px;height:48px;object-fit:cover;">
          <div class="flex-grow-1">
            <div class="fw-bold">${seller.username || 'Seller'}</div>
            <div class="text-muted small text-truncate">${lastMsg}</div>
          </div>
        `;
        item.addEventListener('click', function(e) {
          e.preventDefault();
          window.openDirectChatModal(chat.id, seller.username, seller.avatar_url);
        });
        list.appendChild(item);
      });
    });
});
</script>
@endpush
@endsection 