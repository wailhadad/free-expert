// direct-chat.js
// Requires Bootstrap 5 modal, Pusher/Echo, and fetch API

// --- Subuser dropdown logic for users ---
if (window.currentUserType === 'user') {
  document.addEventListener('DOMContentLoaded', function() {
    // Populate custom dropdown when modal is shown
    const modalElem = document.getElementById('directChatModal');
    if (modalElem) {
      modalElem.addEventListener('show.bs.modal', function() {
        const dropdownMenu = document.getElementById('subuserDropdownMenu');
        const dropdownBtn = document.getElementById('subuserDropdownBtn');
        const dropdownAvatar = document.getElementById('subuserDropdownAvatar');
        const dropdownName = document.getElementById('subuserDropdownName');
        if (!dropdownMenu || !dropdownBtn || !dropdownAvatar || !dropdownName) return;
        dropdownMenu.innerHTML = '';
        fetch('/user/subusers/json')
          .then(res => res.json())
          .then(data => {
            // Always add "Myself" option
            const myselfLi = document.createElement('li');
            myselfLi.innerHTML = `<a class="dropdown-item d-flex align-items-center" href="#" data-id="" data-avatar="/assets/img/users/profile.jpeg" data-name="Myself">
              <img src="/assets/img/users/profile.jpeg" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
              <span>Myself</span>
            </a>`;
            dropdownMenu.appendChild(myselfLi);
            if (data.subusers && data.subusers.length) {
              data.subusers.forEach(subuser => {
                const avatar = subuser.image ? `/assets/img/subusers/${subuser.image}` : '/assets/img/users/profile.jpeg';
                const li = document.createElement('li');
                li.innerHTML = `<a class="dropdown-item d-flex align-items-center" href="#" data-id="${subuser.id}" data-avatar="${avatar}" data-name="${subuser.username}">
                  <img src="${avatar}" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
                  <span>${subuser.username}</span>
                </a>`;
                dropdownMenu.appendChild(li);
              });
            }
            // Set current selection based on window.currentDirectSubuserId
            function syncSubuserDropdownSelection() {
                let found = false;
                dropdownMenu.querySelectorAll('a.dropdown-item').forEach(a => {
                    if (String(a.getAttribute('data-id')) === String(window.currentDirectSubuserId || '')) {
                        dropdownAvatar.src = a.getAttribute('data-avatar');
                        dropdownName.textContent = a.getAttribute('data-name');
                        window.selectedSubuserId = a.getAttribute('data-id') || null;
                        found = true;
                    }
                });
                if (!found) {
                    dropdownAvatar.src = '/assets/img/users/profile.jpeg';
                    dropdownName.textContent = 'Myself (Main)';
                    window.selectedSubuserId = null;
                }
                console.log('Dropdown sync (after fetch): set to subuserId', window.currentDirectSubuserId, 'found:', found);
            }
            syncSubuserDropdownSelection();
          });
      });
      // Handle dropdown selection
      const dropdownMenu = document.getElementById('subuserDropdownMenu');
      if (dropdownMenu) {
        dropdownMenu.addEventListener('click', function(e) {
          const a = e.target.closest('a.dropdown-item');
          if (!a) return;
          e.preventDefault();
          const selectedSubuserId = a.getAttribute('data-id') || null;
          const selectedName = a.getAttribute('data-name');
          const selectedAvatar = a.getAttribute('data-avatar');
          window.selectedSubuserId = selectedSubuserId;
          document.getElementById('subuserDropdownAvatar').src = selectedAvatar;
          document.getElementById('subuserDropdownName').textContent = selectedName;
          // Show loading state only until AJAX completes
          const container = document.getElementById('direct-chat-messages');
          if (container) container.innerHTML = '<div class="text-center text-muted py-4">Loading...</div>';
          // Always use the last known sellerId, fallback to modal data attribute
          let sellerId = window.currentDirectSellerId;
          if (!sellerId) {
            const modalElem = document.getElementById('directChatModal');
            if (modalElem && modalElem.dataset.sellerId) {
              sellerId = modalElem.dataset.sellerId;
            }
          }
          console.log('Dropdown clicked: subuserId=', selectedSubuserId, 'name=', selectedName, 'sellerId=', sellerId, 'window.currentDirectSellerId=', window.currentDirectSellerId);
          // Always use the global seller name/avatar for the chat header
          startOrGetChatWithSubuser(sellerId, selectedSubuserId, window.currentDirectSellerName, window.currentDirectSellerAvatar);
        });
      }
    }
  });
}

// Helper to start or get chat for selected subuser and seller
function startOrGetChatWithSubuser(sellerId, subuserId, partnerName, partnerAvatar) {
  console.log('startOrGetChatWithSubuser called with sellerId=', sellerId);
  window.currentDirectSubuserId = subuserId || null; // Ensure subuser context is set
  fetch('/direct-chat/start', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ seller_id: sellerId, subuser_id: subuserId, user_id: window.currentUserId })
  })
  .then(res => res.json())
  .then(data => {
    console.log('startOrGetChatWithSubuser data:', data);
    if (data.chat && data.chat.id) {
      // Always update sellerId globally and in the modal
      window.currentDirectSellerId = data.chat.seller_id;
      const modalElem = document.getElementById('directChatModal');
      if (modalElem) {
        modalElem.dataset.sellerId = data.chat.seller_id || '';
      }
      window.currentDirectChatId = data.chat.id;
      window.currentDirectSubuserId = subuserId || null;
      console.log('Calling openDirectChatModal for chatId:', data.chat.id, 'subuserId:', subuserId, 'name:', partnerName, 'sellerId:', data.chat.seller_id);
      window.openDirectChatModal(
        data.chat.id,
        partnerName,
        partnerAvatar,
        data.chat.seller_id,
        subuserId
      );
    }
  });
}

window.openDirectChatModal = function(chatId, partnerName, partnerAvatar, sellerId, subuserId) {
    window.currentDirectSellerName = partnerName;
    window.currentDirectSellerAvatar = partnerAvatar;
    console.log('openDirectChatModal: sellerId=', sellerId);
    console.log('openDirectChatModal called with chatId:', chatId, 'subuserId:', subuserId, 'name:', partnerName);
    // Always update modal header
    document.getElementById('direct-chat-partner-name').textContent = partnerName || 'Chat';
    const avatarElem = document.getElementById('direct-chat-partner-avatar');
    if (avatarElem) {
        avatarElem.innerHTML = partnerAvatar
            ? `<img src="${partnerAvatar}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">`
            : '';
    }
    // Store current chat context
    const modalElem = document.getElementById('directChatModal');
    if (modalElem) {
      modalElem.dataset.sellerId = sellerId || '';
    }
    window.currentDirectChatId = chatId;
    window.currentDirectSellerId = sellerId || window.currentDirectSellerId || null;
    window.currentDirectSubuserId = subuserId || null;
    // Set dropdown to match current subuser by ID
    if (window.currentUserType === 'user') {
        const dropdownMenu = document.getElementById('subuserDropdownMenu');
        const dropdownAvatar = document.getElementById('subuserDropdownAvatar');
        const dropdownName = document.getElementById('subuserDropdownName');
        if (dropdownMenu && dropdownAvatar && dropdownName) {
            let found = false;
            dropdownMenu.querySelectorAll('a.dropdown-item').forEach(a => {
                if (String(a.getAttribute('data-id')) === String(window.currentDirectSubuserId || '')) {
                    dropdownAvatar.src = a.getAttribute('data-avatar');
                    dropdownName.textContent = a.getAttribute('data-name');
                    window.selectedSubuserId = a.getAttribute('data-id') || null;
                    found = true;
                }
            });
            if (!found) {
                dropdownAvatar.src = '/assets/img/users/profile.jpeg';
                dropdownName.textContent = 'Myself (Main)';
                window.selectedSubuserId = null;
            }
            console.log('Dropdown sync (openDirectChatModal): set to subuserId', window.currentDirectSubuserId, 'found:', found);
        }
    }
    // Show the modal (Bootstrap 5 or 4)
    if (window.bootstrap && bootstrap.Modal && bootstrap.Modal.getOrCreateInstance) {
        // Bootstrap 5+
    const modal = bootstrap.Modal.getOrCreateInstance(modalElem);
    modal.show();
    } else if (window.$ && window.$.fn && window.$.fn.modal) {
        // Bootstrap 4 fallback
        $(modalElem).modal('show');
    }
    // Clear messages immediately to avoid showing old messages
    const container = document.getElementById('direct-chat-messages');
    if (container) container.innerHTML = '<div class="text-center text-muted py-4">Loading...</div>';
    // Always reload messages, even if modal is already open
    if (typeof window.loadDirectChatMessages === 'function') {
        console.log('Loading messages for chat ID:', chatId);
        window.loadDirectChatMessages(chatId);
    }
};

window.loadDirectChatMessages = function(chatId) {
    let endpoint = '';
    if (window.currentUserType === 'seller') {
        endpoint = `/seller/direct-chat/${chatId}/messages`;
    } else if (window.currentUserType === 'admin') {
        endpoint = `/admin/direct-chat/${chatId}/messages`;
    } else {
        endpoint = `/direct-chat/${chatId}/messages`;
    }
    fetch(endpoint)
        .then(res => res.json())
        .then(data => {
            console.log('Direct chat messages response:', data); // Debug log
            if (data.error) {
                renderDirectChatMessages([]); // Show 'No messages yet.'
                return;
            }
            renderDirectChatMessages(Array.isArray(data.messages) ? data.messages : []);
            scrollDirectChatToBottom();
            markDirectChatAsRead(chatId);
        })
        .catch(err => {
            console.error('Direct chat messages fetch error:', err);
            renderDirectChatMessages([]); // Show 'No messages yet.'
        });
}

function renderDirectChatMessages(messages) {
    const container = document.getElementById('direct-chat-messages');
    console.log('Rendering messages:', messages); // Debug log
    try {
        container.innerHTML = '';
        if (!messages.length) {
            container.innerHTML = '<div class="text-center text-muted py-4">No messages yet.</div>';
            return;
        }
        const isAdmin = window.currentUserType === 'admin';
        messages.forEach(msg => {
            let isMe;
            if (isAdmin) {
                isMe = msg.sender_type === 'user';
            } else {
                isMe = (msg.sender_type === window.currentUserType && String(msg.sender_id) === String(window.currentUserId));
            }
            const msgDiv = document.createElement('div');
            msgDiv.className = 'd-flex mb-2 ' + (isMe ? 'justify-content-end' : 'justify-content-start');
            msgDiv.innerHTML = `
                ${isMe
                    ? `<div class='chat-bubble me-bubble d-flex align-items-end flex-row-reverse' style='gap:10px;max-width:80%;'>
                            <img src='${msg.avatar || '/assets/img/users/profile.jpeg'}' class='rounded-circle ms-2' style='width:40px;height:40px;object-fit:cover;'>
                            <div class='chat-text bg-primary text-white p-2 rounded' style='min-width:60px;max-width:350px;word-break:break-word;'>
                                <div>${escapeHtml(msg.message || '')}</div>
                                ${msg.file_name ? renderDirectChatFile(msg) : ''}
                                <div class='small text-end text-muted mt-1'>${formatTime(msg.created_at)}</div>
                            </div>
                       </div>`
                    : `<div class='chat-bubble other-bubble d-flex align-items-end' style='gap:10px;max-width:80%;'>
                            <img src='${msg.avatar || '/assets/img/users/profile.jpeg'}' class='rounded-circle me-2' style='width:40px;height:40px;object-fit:cover;'>
                            <div class='chat-text bg-light p-2 rounded' style='min-width:60px;max-width:350px;word-break:break-word;'>
                                <div>${escapeHtml(msg.message || '')}</div>
                                ${msg.file_name ? renderDirectChatFile(msg) : ''}
                                <div class='small text-end text-muted mt-1'>${formatTime(msg.created_at)}</div>
                            </div>
                       </div>`
                }
            `;
            container.appendChild(msgDiv);
        });
    } catch (e) {
        console.error('Error rendering chat messages:', e);
        container.innerHTML = '<div class="text-danger text-center py-4">Error displaying messages.</div>';
    }
}

function renderDirectChatFile(msg) {
    if (!msg.file_name) return '';
    const ext = msg.file_original_name ? msg.file_original_name.split('.').pop().toLowerCase() : '';
    const isImg = ['jpg','jpeg','png','gif','bmp','webp'].includes(ext);
    const fileUrl = `/assets/file/direct-chat/${msg.file_name}`;
    if (isImg) {
        return `<div class='mt-2'><a href='${fileUrl}' download='${msg.file_original_name}'><span class='me-2'><i class='far fa-arrow-alt-circle-down'></i></span>${msg.file_original_name}</a><br><img src='${fileUrl}' alt='image' style='max-width:150px;max-height:150px;'></div>`;
    } else {
        return `<div class='mt-2'><a href='${fileUrl}' download='${msg.file_original_name}'><span class='me-2'><i class='far fa-arrow-alt-circle-down'></i></span>${msg.file_original_name}</a></div>`;
    }
}

function sendDirectChatMessage(chatId) {
    const input = document.getElementById('direct-chat-input');
    const fileInput = document.getElementById('direct-chat-attachment');
    const message = input.value.trim();
    const formData = new FormData();
    formData.append('chat_id', chatId);
    formData.append('message', message);
    if (fileInput.files.length > 0) {
        formData.append('attachment', fileInput.files[0]);
    }
    // Add subuser_id if user and selected
    if (window.currentUserType === 'user' && window.selectedSubuserId) {
        formData.append('subuser_id', window.selectedSubuserId);
    }
    let endpoint = '';
    if (window.currentUserType === 'seller') {
        endpoint = `/seller/direct-chat/${chatId}/send?v=${Date.now()}`;
    } else if (window.currentUserType === 'admin') {
        endpoint = `/admin/direct-chat/${chatId}/send?v=${Date.now()}`;
    } else {
        endpoint = `/direct-chat/${chatId}/send?v=${Date.now()}`;
    }
    console.log('Direct chat send endpoint:', endpoint);
    fetch(endpoint, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        input.value = '';
        fileInput.value = '';
        loadDirectChatMessages(chatId);
    });
}

function subscribeToDirectChatChannel(chatId) {
    if (!window.Echo) return;
    if (window.currentDirectChatEchoChannel) {
        window.currentDirectChatEchoChannel.stopListening('.direct-chat.message');
    }
    window.currentDirectChatEchoChannel = window.Echo.private('direct-chat.' + chatId)
        .listen('.direct-chat.message', (data) => {
            console.log('Received real-time chat event:', data);
            if (window.currentDirectChatId == chatId) {
                loadDirectChatMessages(chatId);
            }
        });
}

function markDirectChatAsRead(chatId) {
    fetch(`/direct-chat/${chatId}/read`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),   
        },
    });
}

function isCurrentUser(senderType, senderId) {
    // You may want to set window.currentUserType and window.currentUserId in your Blade layout
    return senderType === window.currentUserType && senderId == window.currentUserId;
}

function scrollDirectChatToBottom() {
    const container = document.getElementById('direct-chat-messages');
    container.scrollTop = container.scrollHeight;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

document.addEventListener('DOMContentLoaded', function() {
    const chatForm = document.getElementById('direct-chat-form');
    if (chatForm) {
        chatForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (window.currentDirectChatId) {
                sendDirectChatMessage(window.currentDirectChatId);
            }
        });
    }
});