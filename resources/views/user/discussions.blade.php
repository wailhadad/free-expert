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
  loadUserDiscussions();
  
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
      loadUserDiscussions();
    } else {
      console.log('Skipping discussions refresh - chat modal is open');
    }
  }, 30000);
  
  function loadUserDiscussions() {
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
    
    fetch('/direct-chat/discussions')
      .then(res => res.json())
      .then(data => {
        console.log('User discussions data:', data);
        const list = document.getElementById('user-discussions-list');
        list.innerHTML = '';
        
        if (!data.chats.length) {
          list.innerHTML = '<div class="text-muted text-center py-4">No discussions yet.</div>';
          return;
        }
        
        // Deduplicate by seller.id, keeping only the most recent chat per seller
        const seenSellers = new Set();
        const uniqueChats = [];
        for (const chat of data.chats) {
          const sellerId = chat.seller && chat.seller.id;
          if (sellerId && !seenSellers.has(sellerId)) {
            seenSellers.add(sellerId);
            uniqueChats.push(chat);
          }
        }
        
        let autoOpenChat = null;
        
        uniqueChats.forEach(chat => {
          const seller = chat.seller;
          const lastMsg = chat.messages && chat.messages.length ? chat.messages[chat.messages.length-1].message : '';
          const item = document.createElement('div');
          item.className = 'list-group-item list-group-item-action d-flex align-items-center gap-3 position-relative';
          
          // Subuser dropdown
          let subuserDropdown = '';
          if (chat.subusers && chat.subusers.length > 1) {
            subuserDropdown = `<div class="dropdown ms-2">
              <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">Subusers</button>
              <ul class="dropdown-menu">`;
            chat.subusers.forEach(subuser => {
              subuserDropdown += `<li><a class="dropdown-item d-flex align-items-center gap-2 subuser-chat-link" href="#" data-chat-id="${chat.id}" data-subuser-id="${subuser.id}">
                <img src="${subuser.avatar_url}" class="rounded-circle" style="width:28px;height:28px;object-fit:cover;">
                <span>${subuser.username}</span>
                <span class="discussion-unread-badge bell-badge ms-2" id="discussion-unread-${chat.id}-${subuser.id}" style="position:relative;min-width:18px;height:18px;line-height:18px;font-size:12px;border-radius:50%;font-weight:700;background:#e11d48;color:#fff;display:${subuser.unread_count > 0 ? 'flex' : 'none'};align-items:center;justify-content:center;z-index:2;border:2px solid #fff;box-shadow:0 2px 8px rgba(220,53,69,0.18);pointer-events:none;padding:0 4px;">${subuser.unread_count > 0 ? subuser.unread_count : ''}</span>
              </a></li>`;
            });
            subuserDropdown += '</ul></div>';
          }
          
          item.innerHTML = `
            <span style="position:relative;display:inline-block;">
            <img src="${seller.avatar_url || '/assets/img/default-avatar.png'}" class="rounded-circle" style="width:48px;height:48px;object-fit:cover;">
            </span>
            <div class="flex-grow-1">
              <div class="fw-bold">${seller.username || 'Seller'}</div>
            </div>
            ${subuserDropdown}
          `;
          
          // Main click opens main user chat
          item.addEventListener('click', function(e) {
            if (e.target.closest('.subuser-chat-link')) return; // handled below
            e.preventDefault();
            window.currentDirectSubuserId = null;
            // Set unread counts for modal dropdown
            window.subuserUnreadCounts = {};
            if (chat.subusers) {
              chat.subusers.forEach(subuser => {
                window.subuserUnreadCounts[String(subuser.id)] = subuser.unread_count;
              });
              // Also handle null for "Myself"
              window.subuserUnreadCounts['null'] = (chat.subusers.find(s => !s.id) || {}).unread_count || 0;
            }
            if (typeof markSubuserRead === 'function') {
              markSubuserRead(chat.id, null);
            }
            window.openDirectChatModal(chat.id, seller.username, seller.avatar_url, seller.id, null);
          });
          
          // Subuser dropdown click
          item.querySelectorAll('.subuser-chat-link').forEach(link => {
            link.addEventListener('click', function(e) {
              e.preventDefault();
              const subuserId = this.getAttribute('data-subuser-id');
              // Set unread counts for modal dropdown
              window.subuserUnreadCounts = {};
              if (chat.subusers) {
                chat.subusers.forEach(subuser => {
                  window.subuserUnreadCounts[String(subuser.id)] = subuser.unread_count;
                });
                window.subuserUnreadCounts['null'] = (chat.subusers.find(s => !s.id) || {}).unread_count || 0;
              }
              if (typeof markSubuserRead === 'function') {
                markSubuserRead(chat.id, subuserId);
              }
              window.currentDirectSubuserId = subuserId;
              window.openDirectChatModal(chat.id, seller.username, seller.avatar_url, seller.id, subuserId);
            });
          });
          
          list.appendChild(item);
          
          // Check if this is the chat we need to auto-open
          if (chatId && String(chat.id) === String(chatId)) {
            autoOpenChat = chat;
          }
        });
        
        // Mark as read function
        window.markSubuserRead = function(chatId, subuserId) {
          fetch('/direct-chat/mark-subuser-read', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({ chat_id: chatId, subuser_id: subuserId })
          }).then(res => res.json()).then(data => {
            // After marking as read, refetch discussions to update badges
            fetch('/direct-chat/discussions')
              .then(res => res.json())
              .then(data => {
                // Update all badges
                data.chats.forEach(chat => {
                  const globalBadge = document.getElementById(`discussion-unread-${chat.id}`);
                  if (globalBadge) {
                    globalBadge.textContent = chat.unread_count > 0 ? chat.unread_count : '';
                    globalBadge.style.display = chat.unread_count > 0 ? 'flex' : 'none';
                  }
                  if (chat.subusers) {
                    chat.subusers.forEach(subuser => {
                      const subBadge = document.getElementById(`discussion-unread-${chat.id}-${subuser.id}`);
                      if (subBadge) {
                        subBadge.textContent = subuser.unread_count > 0 ? subuser.unread_count : '';
                        subBadge.style.display = subuser.unread_count > 0 ? 'flex' : 'none';
                      }
                    });
                  }
                });
                // Update envelope badge
                if (typeof updateDiscussionBadge === 'function') setTimeout(updateDiscussionBadge, 200);
              });
          });
        };
        
        // Auto-open chat after all items are rendered
        if (autoOpenChat) {
          const seller = autoOpenChat.seller;
          
          // Set up subuser unread counts for the auto-opening chat
          window.subuserUnreadCounts = {};
          if (autoOpenChat.subusers) {
            autoOpenChat.subusers.forEach(subuser => {
              window.subuserUnreadCounts[String(subuser.id)] = subuser.unread_count;
            });
            window.subuserUnreadCounts['null'] = (autoOpenChat.subusers.find(s => !s.id) || {}).unread_count || 0;
          }
          
          // Check if subuser matches (if subuser_id is provided)
          if (subuserId) {
            // Find the specific subuser in the chat
            const subuser = autoOpenChat.subusers ? autoOpenChat.subusers.find(s => String(s.id) === String(subuserId)) : null;
            if (subuser) {
              console.log('Auto-opening chat with subuser:', subuser.username);
              setTimeout(() => {
                window.currentDirectSubuserId = subuser.id;
                if (typeof window.markSubuserRead === 'function') {
                  window.markSubuserRead(autoOpenChat.id, subuser.id);
                }
                window.openDirectChatModal(autoOpenChat.id, seller.username, seller.avatar_url, seller.id, subuser.id);
                // Clean up URL after opening chat
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
              }, 1000); // Increased delay to ensure modal and scripts are ready
            } else {
              // Fallback to main user if subuser not found
              console.log('Subuser not found, opening with main user');
              setTimeout(() => {
                window.currentDirectSubuserId = null;
                if (typeof window.markSubuserRead === 'function') {
                  window.markSubuserRead(autoOpenChat.id, null);
                }
                window.openDirectChatModal(autoOpenChat.id, seller.username, seller.avatar_url, seller.id, null);
                // Clean up URL after opening chat
                const newUrl = window.location.pathname;
                window.history.replaceState({}, document.title, newUrl);
              }, 1000);
            }
          } else {
            // Open with main user
            console.log('Auto-opening chat with main user');
            setTimeout(() => {
              window.currentDirectSubuserId = null;
              if (typeof window.markSubuserRead === 'function') {
                window.markSubuserRead(autoOpenChat.id, null);
              }
              window.openDirectChatModal(autoOpenChat.id, seller.username, seller.avatar_url, seller.id, null);
              // Clean up URL after opening chat
              const newUrl = window.location.pathname;
              window.history.replaceState({}, document.title, newUrl);
            }, 1000); // Increased delay to ensure modal and scripts are ready
          }
        }
      })
      .catch(err => {
        console.error('Error loading user discussions:', err);
        const list = document.getElementById('user-discussions-list');
        if (list) {
          list.innerHTML = '<div class="text-danger text-center py-4">Error loading discussions. Please try again.</div>';
        }
      });
  }
});
</script>
@endpush
@endsection 