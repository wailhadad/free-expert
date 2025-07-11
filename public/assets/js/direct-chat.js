// direct-chat.js
// Requires Bootstrap 5 modal, Pusher/Echo, and fetch API

// --- File upload handling ---
let selectedFiles = [];

function handleFileSelection() {
    const fileInput = document.getElementById('direct-chat-attachment');
    const filePreview = document.getElementById('direct-chat-file-preview');
    const selectedFilesContainer = filePreview.querySelector('.selected-files-container');
    
    if (fileInput.files.length > 0) {
        const file = fileInput.files[0];
        
        // Check if file is already selected
        const existingFileIndex = selectedFiles.findIndex(f => f.name === file.name && f.size === file.size);
        if (existingFileIndex !== -1) {
            return; // File already selected
        }
        
        // Add file to selected files array
        selectedFiles.push(file);
        
        // Show file preview
        filePreview.classList.remove('d-none');
        
        // Create file preview element
        const fileElement = document.createElement('div');
        fileElement.className = 'selected-file d-flex align-items-center justify-content-between p-2 mb-2 border rounded bg-light';
        fileElement.dataset.fileName = file.name;
        fileElement.dataset.fileSize = file.size;
        
        const fileInfo = document.createElement('div');
        fileInfo.className = 'd-flex align-items-center';
        
        // File icon based on type
        const fileExt = file.name.split('.').pop().toLowerCase();
        let fileIcon = 'far fa-file';
        if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'].includes(fileExt)) {
            fileIcon = 'far fa-file-image';
        } else if (['pdf'].includes(fileExt)) {
            fileIcon = 'far fa-file-pdf';
        } else if (['doc', 'docx'].includes(fileExt)) {
            fileIcon = 'far fa-file-word';
        } else if (['zip', 'rar'].includes(fileExt)) {
            fileIcon = 'far fa-file-archive';
        } else if (['txt'].includes(fileExt)) {
            fileIcon = 'far fa-file-alt';
        }
        
        fileInfo.innerHTML = `
            <i class="${fileIcon} me-2 text-primary"></i>
            <div>
                <div class="fw-bold small">${file.name}</div>
                <div class="text-muted small">${formatFileSize(file.size)}</div>
            </div>
        `;
        
        const removeBtn = document.createElement('button');
        removeBtn.className = 'btn btn-sm btn-outline-danger';
        removeBtn.innerHTML = '<i class="far fa-times"></i>';
        removeBtn.onclick = function() {
            removeSelectedFile(file.name, file.size);
        };
        
        fileElement.appendChild(fileInfo);
        fileElement.appendChild(removeBtn);
        selectedFilesContainer.appendChild(fileElement);
    }
}

function removeSelectedFile(fileName, fileSize) {
    // Remove from selected files array
    selectedFiles = selectedFiles.filter(f => !(f.name === fileName && f.size === fileSize));
    
    // Remove from UI
    const filePreview = document.getElementById('direct-chat-file-preview');
    const selectedFilesContainer = filePreview.querySelector('.selected-files-container');
    const fileElement = selectedFilesContainer.querySelector(`[data-file-name="${fileName}"][data-file-size="${fileSize}"]`);
    if (fileElement) {
        fileElement.remove();
    }
    
    // Hide preview if no files selected
    if (selectedFiles.length === 0) {
        filePreview.classList.add('d-none');
        // Clear the file input
        const fileInput = document.getElementById('direct-chat-attachment');
        fileInput.value = '';
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

function clearFilePreview() {
    selectedFiles = [];
    const filePreview = document.getElementById('direct-chat-file-preview');
    if (filePreview) {
        const selectedFilesContainer = filePreview.querySelector('.selected-files-container');
        if (selectedFilesContainer) selectedFilesContainer.innerHTML = '';
        filePreview.classList.add('d-none');
    }
    // Only try to clear file input if it exists
    const fileInput = document.getElementById('direct-chat-attachment');
    if (fileInput) fileInput.value = '';
}

// --- Subuser dropdown logic for users ---
if (window.currentUserType === 'user') {
  // Helper: sync dropdown selection to current subuser
  window.syncSubuserDropdownSelection = function(forceSubuserId) {
    const dropdownMenu = document.getElementById('subuserDropdownMenu');
    const dropdownAvatar = document.getElementById('subuserDropdownAvatar');
    const dropdownName = document.getElementById('subuserDropdownName');
    let subuserId = typeof forceSubuserId !== 'undefined' ? forceSubuserId : window.currentDirectSubuserId;
    if (!dropdownMenu || !dropdownAvatar || !dropdownName) return;
    let found = false;
    dropdownMenu.querySelectorAll('a.dropdown-item').forEach(a => {
      console.log('Dropdown item data-id:', a.getAttribute('data-id'));
      if (String(a.getAttribute('data-id')) === String(subuserId || '')) {
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
    console.log('Dropdown sync: set to subuserId', subuserId, 'found:', found);
  };

  // Helper: populate dropdown and run callback after
  window.populateSubuserDropdown = function(afterPopulateCallback, syncSubuserId) {
    const dropdownMenu = document.getElementById('subuserDropdownMenu');
    const dropdownBtn = document.getElementById('subuserDropdownBtn');
    const dropdownAvatar = document.getElementById('subuserDropdownAvatar');
    const dropdownName = document.getElementById('subuserDropdownName');
    if (!dropdownMenu || !dropdownBtn || !dropdownAvatar || !dropdownName) return;
    dropdownMenu.innerHTML = '';
    fetch('/user/subusers/json')
      .then(res => res.json())
      .then(data => {
        // Always add "Myself" option (no unread badge)
        const myselfLi = document.createElement('li');
        myselfLi.innerHTML = `<a class="dropdown-item d-flex align-items-center" href="#" data-id="" data-avatar="/assets/img/users/profile.jpeg" data-name="Myself">
          <img src="/assets/img/users/profile.jpeg" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
          <span>Myself</span>
        </a>`;
        dropdownMenu.appendChild(myselfLi);
        if (data.subusers && data.subusers.length) {
          const seen = new Set();
          data.subusers.forEach(subuser => {
            if (seen.has(subuser.id)) return;
            seen.add(subuser.id);
            const avatar = subuser.image ? `/assets/img/subusers/${subuser.image}` : '/assets/img/users/profile.jpeg';
            const li = document.createElement('li');
            li.innerHTML = `<a class="dropdown-item d-flex align-items-center" href="#" data-id="${subuser.id}" data-avatar="${avatar}" data-name="${subuser.username}">
              <img src="${avatar}" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
              <span>${subuser.username}</span>
            </a>`;
            dropdownMenu.appendChild(li);
          });
        }
        if (typeof afterPopulateCallback === 'function') afterPopulateCallback();
        // Do NOT call syncSubuserDropdownSelection here!
      });
  };

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
            let myselfUnread = (window.subuserUnreadCounts && window.subuserUnreadCounts['null']) ? window.subuserUnreadCounts['null'] : 0;
            myselfLi.innerHTML = `<a class="dropdown-item d-flex align-items-center" href="#" data-id="" data-avatar="/assets/img/users/profile.jpeg" data-name="Myself">
              <img src="/assets/img/users/profile.jpeg" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
              <span>Myself</span>
              <span class="discussion-unread-badge bell-badge ms-2" style="position:relative;min-width:18px;height:18px;line-height:18px;font-size:12px;border-radius:50%;font-weight:700;background:#e11d48;color:#fff;display:${myselfUnread > 0 ? 'flex' : 'none'};align-items:center;justify-content:center;z-index:2;border:2px solid #fff;box-shadow:0 2px 8px rgba(220,53,69,0.18);pointer-events:none;padding:0 4px;">${myselfUnread > 0 ? myselfUnread : ''}</span>
            </a>`;
            dropdownMenu.appendChild(myselfLi);
            if (data.subusers && data.subusers.length) {
              data.subusers.forEach(subuser => {
                const avatar = subuser.image ? `/assets/img/subusers/${subuser.image}` : '/assets/img/users/profile.jpeg';
                const li = document.createElement('li');
                let unread = (window.subuserUnreadCounts && window.subuserUnreadCounts[String(subuser.id)]) ? window.subuserUnreadCounts[String(subuser.id)] : 0;
                li.innerHTML = `<a class="dropdown-item d-flex align-items-center" href="#" data-id="${subuser.id}" data-avatar="${avatar}" data-name="${subuser.username}">
                  <img src="${avatar}" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
                  <span>${subuser.username}</span>
                  <span class="discussion-unread-badge bell-badge ms-2" style="position:relative;min-width:18px;height:18px;line-height:18px;font-size:12px;border-radius:50%;font-weight:700;background:#e11d48;color:#fff;display:${unread > 0 ? 'flex' : 'none'};align-items:center;justify-content:center;z-index:2;border:2px solid #fff;box-shadow:0 2px 8px rgba(220,53,69,0.18);pointer-events:none;padding:0 4px;">${unread > 0 ? unread : ''}</span>
                </a>`;
                dropdownMenu.appendChild(li);
              });
            }
            // Set current selection based on window.currentDirectSubuserId
            setTimeout(() => window.syncSubuserDropdownSelection(), 50);
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
    // Show the modal (Bootstrap 5 or 4)
    if (window.bootstrap && bootstrap.Modal && bootstrap.Modal.getOrCreateInstance) {
        // Bootstrap 5+
        const modal = bootstrap.Modal.getOrCreateInstance(modalElem);
        modal.show();
    } else if (window.$ && window.$.fn && window.$.fn.modal) {
        // Bootstrap 4 fallback
        $(modalElem).modal('show');
    }
    // --- Fix: Ensure dropdown selection matches current subuser after modal is shown and dropdown is populated ---
    if (window.currentUserType === 'user') {
        setTimeout(() => window.syncSubuserDropdownSelection(subuserId), 400);
    }
    // Clear messages immediately to avoid showing old messages
    const container = document.getElementById('direct-chat-messages');
    if (container) container.innerHTML = '<div class="text-center text-muted py-4">Loading...</div>';
    
    // Clear file preview when opening modal
    clearFilePreview();
    
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
        .then(res => {
            // Try to parse JSON, but handle errors
            return res.json().catch(err => {
                console.error('Failed to parse JSON from chat messages endpoint:', err);
                return null;
            });
        })
        .then(data => {
            console.log('Direct chat messages response:', data);
            if (data && Array.isArray(data.messages)) {
                renderDirectChatMessages(data.messages);
            } else {
                renderDirectChatMessages([]); // Will show 'No messages yet.'
            }
            if (window.currentUserType === 'user' && data && Array.isArray(data.messages) && data.messages.length) {
                const lastMsg = data.messages[data.messages.length - 1];
                window.currentDirectSubuserId = lastMsg.subuser_id || null;
                if (typeof window.populateSubuserDropdown === 'function') {
                    window.populateSubuserDropdown(function() {
                        if (typeof window.syncSubuserDropdownSelection === 'function') {
                            window.syncSubuserDropdownSelection(window.currentDirectSubuserId);
                        }
                    }, window.currentDirectSubuserId);
                }
            }
            scrollDirectChatToBottom();
            markDirectChatAsRead(chatId);
        })
        .catch(err => {
            console.error('Error fetching direct chat messages:', err);
            const container = document.getElementById('direct-chat-messages');
            if (container) {
                container.innerHTML = '<div class="text-danger text-center py-4">Could not load messages. Please try again later.</div>';
            }
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
                                ${msg.file_name ? renderDirectChatFile(msg, true) : ''}
                                <div class='small text-end mt-1' style='color:#5c4848;'>${formatTime(msg.created_at)}</div>
                            </div>
                       </div>`
                    : `<div class='chat-bubble other-bubble d-flex align-items-end' style='gap:10px;max-width:80%;'>
                            <img src='${msg.avatar || '/assets/img/users/profile.jpeg'}' class='rounded-circle me-2' style='width:40px;height:40px;object-fit:cover;'>
                            <div class='chat-text bg-light p-2 rounded text-dark' style='min-width:60px;max-width:350px;word-break:break-word;'>
                                <div>${escapeHtml(msg.message || '')}</div>
                                ${msg.file_name ? renderDirectChatFile(msg, false) : ''}
                                <div class='small text-end mt-1' style='color:#5c4848;'>${formatTime(msg.created_at)}</div>
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

function renderDirectChatFile(msg, forceBlack) {
    if (!msg.file_name) return '';
    const ext = msg.file_original_name ? msg.file_original_name.split('.').pop().toLowerCase() : '';
    const isImg = ['jpg','jpeg','png','gif','bmp','webp'].includes(ext);
    const fileUrl = `/assets/file/direct-chat/${msg.file_name}`;
    const linkStyle = forceBlack ? "color:#03ff78;" : "";
    if (isImg) {
        return `<div class='mt-2'><a href='${fileUrl}' download='${msg.file_original_name}' style='${linkStyle}'><span class='me-2'><i class='far fa-arrow-alt-circle-down'></i></span>${msg.file_original_name}</a><br><img src='${fileUrl}' alt='image' style='max-width:150px;max-height:150px;'></div>`;
    } else {
        return `<div class='mt-2'><a href='${fileUrl}' download='${msg.file_original_name}' style='${linkStyle}'><span class='me-2'><i class='far fa-arrow-alt-circle-down'></i></span>${msg.file_original_name}</a></div>`;
    }
}

function sendDirectChatMessage(chatId) {
    const input = document.getElementById('direct-chat-input');
    const message = input.value.trim();
    const formData = new FormData();
    formData.append('chat_id', chatId);
    formData.append('message', message);
    
    // Add selected files
    if (selectedFiles.length > 0) {
        formData.append('attachment', selectedFiles[0]);
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
    
    // Show upload progress if file is being uploaded
    const progressBar = document.getElementById('direct-chat-upload-progress');
    const progressBarInner = progressBar.querySelector('.progress-bar');
    const hasFiles = selectedFiles.length > 0;
    if (hasFiles) {
        progressBar.classList.remove('d-none');
        progressBarInner.style.width = '0%';
        progressBarInner.textContent = 'Uploading...';
        progressBarInner.classList.remove('bg-success');
    }
    
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
        clearFilePreview(); // Clear file preview and input
        
        // Show success briefly if file was uploaded
        if (hasFiles) {
            progressBarInner.style.width = '100%';
            progressBarInner.textContent = 'Uploaded!';
            progressBarInner.classList.add('bg-success');
            setTimeout(() => {
                progressBar.classList.add('d-none');
            }, 1000);
        } else {
            progressBar.classList.add('d-none');
        }
        
        loadDirectChatMessages(chatId);
    })
    .catch(error => {
        console.error('Error sending message:', error);
        progressBar.classList.add('d-none'); // Hide progress bar on error
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
    
    // Add file selection event listener
    const fileInput = document.getElementById('direct-chat-attachment');
    if (fileInput) {
        fileInput.addEventListener('change', handleFileSelection);
    }
});

// --- Real-time discussions: Listen for new discussions ---
(function() {
  if (typeof Pusher === 'undefined') return;
  var pusherKeyMeta = document.querySelector('meta[name="pusher-key"]');
  var pusherClusterMeta = document.querySelector('meta[name="pusher-cluster"]');
  var pusherKey = window.pusherKey || (pusherKeyMeta ? pusherKeyMeta.getAttribute('content') : null);
  var pusherCluster = window.pusherCluster || (pusherClusterMeta ? pusherClusterMeta.getAttribute('content') : null);
  if (!pusherKey || !pusherCluster) return;
  var pusher = new Pusher(pusherKey, { cluster: pusherCluster });
  var channel = pusher.subscribe('discussion-channel');
  channel.bind('discussion.started', function(data) {
    // Only add if there is a latest_message (i.e., at least one message)
    if (!data.latest_message) return;
    // Determine which page we're on
    var adminList = document.getElementById('admin-discussions-list');
    var sellerList = document.getElementById('seller-discussions-list');
    var userList = document.getElementById('user-discussions-list');
    function removeNoDiscussionsMsg(list) {
      if (!list) return;
      var emptyMsg = list.querySelector('.text-muted.text-center.py-4');
      if (emptyMsg) emptyMsg.remove();
    }
    if (adminList) {
      removeNoDiscussionsMsg(adminList);
      var user = data.real_user || data.user || {};
      var subuser = data.subuser || null;
      var seller = data.seller || {};
      var latestMsg = data.latest_message || '';
      var item = document.createElement('a');
      item.href = '#';
      item.className = 'list-group-item list-group-item-action d-flex align-items-center gap-3';
      // Always show real user avatar, then subuser avatar (if present), adjacent
      let userHtml = `<img src="${user.avatar_url || '/assets/img/default-avatar.png'}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">`;
      let subuserHtml = '';
      if (subuser) {
        subuserHtml = `<img src="${subuser.avatar_url || '/assets/img/default-avatar.png'}" class="rounded-circle ms-2" style="width:40px;height:40px;object-fit:cover;">`;
      }
      // Usernames: real user, (as) subuser, arrow, seller
      let userLinks = `<a href="/admin/user-management/user/${user.id}/details" class="username-link">${user.username || 'User'}</a>`;
      if (subuser) {
        userLinks += ` <span class='mx-1'>(as)</span> <a href="/admin/user-management/subuser/${subuser.id}/details" class="username-link">${subuser.username}</a>`;
      }
      userLinks += ` ➔ <a href="/admin/seller-management/seller/${seller.id}/details?language=en" class="username-link">${seller.username || 'Seller'}</a>`;
      item.innerHTML = `
        ${userHtml}
        ${subuserHtml}
        <span class="mx-2">➔</span>
        <img src="${seller.avatar_url || '/assets/img/default-avatar.png'}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">
        <div class="flex-grow-1">
          <div class="fw-bold">
            ${userLinks}
          </div>
          <div class="text-muted small text-truncate">${latestMsg}</div>
        </div>
      `;
      item.addEventListener('click', function(e) {
        // If the click was on a username link, let the link work normally
        if (e.target.closest('.username-link')) {
          return;
        }
        e.preventDefault();
        window.openDirectChatModal(data.id, seller.username, seller.avatar_url, seller.id, subuser ? subuser.username : null);
      });
      adminList.insertBefore(item, adminList.firstChild);
    } else if (sellerList) {
      removeNoDiscussionsMsg(sellerList);
      var user = data.user || {};
      var latestMsg = data.latest_message || '';
      var item = document.createElement('a');
      item.href = '#';
      item.className = 'list-group-item list-group-item-action d-flex align-items-center gap-3';
      item.innerHTML = `
        <img src="${user.avatar_url || '/assets/img/default-avatar.png'}" class="rounded-circle" style="width:48px;height:48px;object-fit:cover;">
        <div class="flex-grow-1">
          <div class="fw-bold">${user.username || 'User'}</div>
          <div class="text-muted small text-truncate">${latestMsg}</div>
        </div>
      `;
      item.addEventListener('click', function(e) {
        e.preventDefault();
        window.openDirectChatModal(data.id, user.username, user.avatar_url, data.seller ? data.seller.id : null, user.username);
      });
      sellerList.insertBefore(item, sellerList.firstChild);
    } else if (userList) {
      removeNoDiscussionsMsg(userList);
      var seller = data.seller || {};
      var latestMsg = data.latest_message || '';
      var item = document.createElement('div');
      item.className = 'list-group-item list-group-item-action d-flex align-items-center gap-3 position-relative';
      item.innerHTML = `
        <img src="${seller.avatar_url || '/assets/img/default-avatar.png'}" class="rounded-circle" style="width:48px;height:48px;object-fit:cover;">
        <div class="flex-grow-1">
          <div class="fw-bold">${seller.username || 'Seller'}</div>
          <div class="text-muted small text-truncate">${latestMsg}</div>
        </div>
      `;
      item.addEventListener('click', function(e) {
        e.preventDefault();
        window.openDirectChatModal(data.id, seller.username, seller.avatar_url, seller.id, null);
      });
      userList.insertBefore(item, userList.firstChild);
    }
  });
})();