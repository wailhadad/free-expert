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
  // Check for URL parameters to auto-open chat
  const urlParams = new URLSearchParams(window.location.search);
  const chatId = urlParams.get('chat_id');
  const subuserId = urlParams.get('subuser_id');
  
  // Also check for && separator (common mistake)
  if (!chatId && window.location.search.includes('&&')) {
    const searchString = window.location.search.replace(/&&/g, '&');
    const tempParams = new URLSearchParams(searchString);
    const tempChatId = tempParams.get('chat_id');
    const tempSubuserId = tempParams.get('subuser_id');
    if (tempChatId) {
      // Use the corrected parameters
      window.location.search = searchString;
      return; // Page will reload with correct URL
    }
  }
  
  // Initial load
  loadSellerDiscussions();
  
  // Set up periodic refresh every 30 seconds, but only if no chat modal is open
  setInterval(() => {
    const modal = document.getElementById('directChatModal');
    // Check multiple ways the modal might be open
    const isModalOpen = modal && (
      modal.classList.contains('show') || 
      modal.style.display === 'block' ||
      document.body.classList.contains('modal-open')
    );
    
    if (!isModalOpen) {
      loadSellerDiscussions();
    } else {
      console.log('Skipping discussions refresh - chat modal is open');
    }
  }, 30000);
  
  function loadSellerDiscussions() {
    // Check if modal is open before refreshing
    const modal = document.getElementById('directChatModal');
    // Check multiple ways the modal might be open
    const isModalOpen = modal && (
      modal.classList.contains('show') || 
      modal.style.display === 'block' ||
      document.body.classList.contains('modal-open')
    );
    
    if (isModalOpen) {
      console.log('Skipping discussions refresh - chat modal is open');
      return;
    }
    
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
      
      let autoOpenChat = null;
      
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
        
        // Check if this is the chat we need to auto-open
        if (chatId && String(chat.id) === String(chatId)) {
          autoOpenChat = chat;
        }
      });
      
      // Auto-open chat after all items are rendered
      if (autoOpenChat) {
        const user = autoOpenChat.user;
        
        // Check if subuser matches (if subuser_id is provided)
        if (subuserId) {
          // Find the specific subuser in the chat
          const subuser = autoOpenChat.subusers ? autoOpenChat.subusers.find(s => String(s.id) === String(subuserId)) : null;
          if (subuser) {
            console.log('Auto-opening chat with subuser:', subuser.username);
            setTimeout(() => {
              window.openDirectChatModal(autoOpenChat.id, subuser.username, subuser.avatar_url, autoOpenChat.seller?.id, subuser.id);
              // Clean up URL after opening chat
              const newUrl = window.location.pathname;
              window.history.replaceState({}, document.title, newUrl);
            }, 1000); // Increased delay to ensure modal and scripts are ready
          } else {
            // Fallback to main user if subuser not found
            console.log('Subuser not found, opening with main user:', user?.username);
            setTimeout(() => {
              window.openDirectChatModal(autoOpenChat.id, user?.username, autoOpenChat.seller?.avatar_url, autoOpenChat.seller?.id, user?.username);
              // Clean up URL after opening chat
              const newUrl = window.location.pathname;
              window.history.replaceState({}, document.title, newUrl);
            }, 1000);
          }
        } else {
          // Open with main user
          console.log('Auto-opening chat with main user:', user?.username);
          setTimeout(() => {
            window.openDirectChatModal(autoOpenChat.id, user?.username, autoOpenChat.seller?.avatar_url, autoOpenChat.seller?.id, user?.username);
            // Clean up URL after opening chat
            const newUrl = window.location.pathname;
            window.history.replaceState({}, document.title, newUrl);
          }, 1000); // Increased delay to ensure modal and scripts are ready
        }
      }
    });
  }
});
</script>
@endpush
@endsection 