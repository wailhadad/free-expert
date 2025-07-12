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

// Customer Offer functionality
let customerOfferForms = [];

// Load forms for customer offer
function loadCustomerOfferForms() {
  if (window.currentUserType !== 'seller') return;
  
  fetch('/seller/customer-offer/forms')
    .then(res => res.json())
    .then(data => {
      customerOfferForms = data.forms || [];
      const formSelect = document.getElementById('offer-form');
      if (formSelect) {
        formSelect.innerHTML = '<option value="">No form attached</option>';
        customerOfferForms.forEach(form => {
          const option = document.createElement('option');
          option.value = form.id;
          option.textContent = form.name;
          formSelect.appendChild(option);
        });
      }
    })
    .catch(err => console.error('Error loading forms:', err));
}

// Show form preview when form is selected
function showFormPreview(formId) {
  const previewSection = document.getElementById('form-preview-section');
  const previewContent = document.getElementById('form-preview-content');
  
  if (!formId) {
    previewSection.classList.add('d-none');
    return;
  }
  
  const form = customerOfferForms.find(f => f.id == formId);
  if (!form) {
    previewSection.classList.add('d-none');
    return;
  }
  
  previewContent.innerHTML = '';
  form.input.forEach(input => {
    const fieldDiv = document.createElement('div');
    fieldDiv.className = 'mb-3';
    
    let fieldHtml = `<label class="form-label fw-bold">${input.label}`;
    if (input.is_required) fieldHtml += ' <span class="text-danger">*</span>';
    fieldHtml += '</label>';
    
    switch (input.type) {
      case 1: // Text
        fieldHtml += `<input type="text" class="form-control" placeholder="${input.placeholder || ''}" ${input.is_required ? 'required' : ''} disabled>`;
        break;
      case 2: // Email
        fieldHtml += `<input type="email" class="form-control" placeholder="${input.placeholder || ''}" ${input.is_required ? 'required' : ''} disabled>`;
        break;
      case 3: // Select
        const options = JSON.parse(input.options || '[]');
        fieldHtml += `<select class="form-control" ${input.is_required ? 'required' : ''} disabled>`;
        fieldHtml += '<option value="">Select...</option>';
        options.forEach(option => {
          fieldHtml += `<option value="${option}">${option}</option>`;
        });
        fieldHtml += '</select>';
        break;
      case 4: // Checkbox
        const checkboxOptions = JSON.parse(input.options || '[]');
        checkboxOptions.forEach(option => {
          fieldHtml += `<div class="form-check"><input class="form-check-input" type="checkbox" value="${option}" disabled><label class="form-check-label">${option}</label></div>`;
        });
        break;
      case 5: // Radio
        const radioOptions = JSON.parse(input.options || '[]');
        radioOptions.forEach(option => {
          fieldHtml += `<div class="form-check"><input class="form-check-input" type="radio" name="radio_${input.name}" value="${option}" disabled><label class="form-check-label">${option}</label></div>`;
        });
        break;
      case 6: // Textarea
        fieldHtml += `<textarea class="form-control" placeholder="${input.placeholder || ''}" rows="3" ${input.is_required ? 'required' : ''} disabled></textarea>`;
        break;
      case 7: // Number
        fieldHtml += `<input type="number" class="form-control" placeholder="${input.placeholder || ''}" ${input.is_required ? 'required' : ''} disabled>`;
        break;
      case 8: // File
        fieldHtml += `<input type="file" class="form-control" ${input.is_required ? 'required' : ''} disabled>`;
        break;
      default:
        fieldHtml += `<input type="text" class="form-control" placeholder="${input.placeholder || ''}" ${input.is_required ? 'required' : ''} disabled>`;
    }
    
    fieldDiv.innerHTML = fieldHtml;
    previewContent.appendChild(fieldDiv);
  });
  
  previewSection.classList.remove('d-none');
}

// Create customer offer
function createCustomerOffer() {
  const form = document.getElementById('customer-offer-form');
  const formData = new FormData(form);
  
  // Add chat_id
  formData.append('chat_id', window.currentDirectChatId);
  
  // Add currency symbol
  formData.append('currency_symbol', '$');
  
  const createBtn = document.getElementById('create-offer-btn');
  const originalText = createBtn.innerHTML;
  createBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
  createBtn.disabled = true;
  
  fetch('/seller/customer-offer/create', {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      // Close modal
      const modal = document.getElementById('customerOfferModal');
      if (window.bootstrap && bootstrap.Modal) {
        bootstrap.Modal.getInstance(modal).hide();
      } else if (window.$ && window.$.fn && window.$.fn.modal) {
        $(modal).modal('hide');
      }
      
      // Reset form
      form.reset();
      document.getElementById('form-preview-section').classList.add('d-none');
      
      // Reload messages to show the new offer
      // if (typeof window.loadDirectChatMessages === 'function') {
      //   window.loadDirectChatMessages(window.currentDirectChatId);
      // }
      
      // Show success message
      alert('Customer offer created successfully!');
    } else {
      alert('Error creating offer: ' + (data.message || 'Unknown error'));
    }
  })
  .catch(err => {
    console.error('Error creating offer:', err);
    alert('Error creating offer. Please try again.');
  })
  .finally(() => {
    createBtn.innerHTML = originalText;
    createBtn.disabled = false;
  });
}

// Load customer offers for a chat
function loadCustomerOffers(chatId) {
  let endpoint = '';
  if (window.currentUserType === 'seller') {
    endpoint = `/seller/customer-offer/${chatId}/offers`;
  } else if (window.currentUserType === 'admin') {
    endpoint = `/admin/customer-offer/${chatId}/offers`;
  } else {
    endpoint = `/customer-offer/${chatId}/offers`;
  }
  
  return fetch(endpoint)
    .then(res => res.json())
    .then(data => {
      window.currentCustomerOffers = data.offers || [];
      return data;
    })
    .catch(err => {
      console.error('Error loading offers:', err);
      return { offers: [] };
    });
}

// Accept customer offer
function acceptCustomerOffer(offerId) {
  if (!confirm('Are you sure you want to accept this offer?')) return;
  // Optimistically disable buttons
  setOfferActionButtonsState(offerId, true, 'Accepting...');
  fetch(`/customer-offer/${offerId}/accept`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    }
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      // Optimistically update offer status (will be corrected by real-time event)
      updateOfferStatusLocally(offerId, 'accepted');
      alert('Offer accepted! Redirecting to checkout...');
      if (data.redirect_url) {
        window.location.href = data.redirect_url;
      }
    } else {
      alert('Error accepting offer: ' + (data.message || 'Unknown error'));
      setOfferActionButtonsState(offerId, false);
    }
  })
  .catch(err => {
    console.error('Error accepting offer:', err);
    alert('Error accepting offer. Please try again.');
    setOfferActionButtonsState(offerId, false);
  });
}

// Decline customer offer
function declineCustomerOffer(offerId) {
  if (!confirm('Are you sure you want to decline this offer?')) return;
  setOfferActionButtonsState(offerId, true, 'Declining...');
  fetch(`/customer-offer/${offerId}/decline`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    }
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      updateOfferStatusLocally(offerId, 'declined');
      alert('Offer declined successfully!');
    } else {
      alert('Error declining offer: ' + (data.message || 'Unknown error'));
      setOfferActionButtonsState(offerId, false);
    }
  })
  .catch(err => {
    console.error('Error declining offer:', err);
    alert('Error declining offer. Please try again.');
    setOfferActionButtonsState(offerId, false);
  });
}

// Helper: disable/enable accept/decline buttons for an offer
function setOfferActionButtonsState(offerId, disabled, loadingText) {
  const offerDiv = document.querySelector(`.customer-offer-card button[onclick*='acceptCustomerOffer(${offerId})']`)?.closest('.customer-offer-card');
  if (!offerDiv) return;
  const acceptBtn = offerDiv.querySelector('.btn-accept');
  const declineBtn = offerDiv.querySelector('.btn-decline');
  if (acceptBtn) {
    acceptBtn.disabled = disabled;
    if (loadingText && disabled) {
      acceptBtn.innerHTML = `<span class='spinner-border spinner-border-sm me-1'></span> ${loadingText}`;
    } else {
      acceptBtn.innerHTML = `<i class="fas fa-check"></i> Accept`;
    }
  }
  if (declineBtn) {
    declineBtn.disabled = disabled;
    if (loadingText && disabled) {
      declineBtn.innerHTML = `<span class='spinner-border spinner-border-sm me-1'></span> ${loadingText}`;
    } else {
      declineBtn.innerHTML = `<i class="fas fa-times"></i> Decline`;
    }
  }
}

// Helper: update offer status locally (optimistic UI)
function updateOfferStatusLocally(offerId, status) {
  if (!window.currentCustomerOffers) return;
  const idx = window.currentCustomerOffers.findIndex(o => o.id === offerId);
  if (idx !== -1) {
    window.currentCustomerOffers[idx].status = status;
    mergeAndRenderChatTimeline();
  }
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
    // Only reload messages if switching to a different chat or if never loaded
    const isSwitchingChat = window.currentDirectChatId !== chatId;
    const needsInitialLoad = (typeof window.lastLoadedDirectChatId === 'undefined' || window.lastLoadedDirectChatId !== chatId || !window.currentDirectChatMessages || window.currentDirectChatMessages.length === 0);
    window.currentDirectChatId = chatId;
    window.currentDirectSellerId = sellerId || window.currentDirectSellerId || null;
    window.currentDirectSubuserId = subuserId || null;
    // Subscribe to real-time chat and offer events
    if (typeof subscribeToDirectChatChannel === 'function') {
        subscribeToDirectChatChannel(chatId);
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
    // --- Fix: Ensure dropdown selection matches current subuser after modal is shown and dropdown is populated ---
    if (window.currentUserType === 'user') {
        setTimeout(() => window.syncSubuserDropdownSelection(subuserId), 400);
    }
    // Clear messages immediately to avoid showing old messages
    const container = document.getElementById('direct-chat-messages');
    if (container) container.innerHTML = '<div class="text-center text-muted py-4">Loading...</div>';
    // Clear file preview when opening modal
    clearFilePreview();
    // Only reload messages if switching to a different chat or never loaded
    if ((isSwitchingChat || needsInitialLoad) && typeof window.loadDirectChatMessages === 'function') {
        window.lastLoadedDirectChatId = chatId;
        console.log('Loading messages for chat ID:', chatId);
        window.loadDirectChatMessages(chatId);
    } else {
        // If not switching chat, just re-render the timeline
        mergeAndRenderChatTimeline();
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
    // Load messages and offers in parallel
    Promise.all([
        fetch(endpoint).then(res => res.json().catch(err => {
                console.error('Failed to parse JSON from chat messages endpoint:', err);
            return { messages: [] };
        })),
        loadCustomerOffers(chatId)
    ])
    .then(([messageData, offerData]) => {
        window.currentDirectChatMessages = (messageData && Array.isArray(messageData.messages)) ? messageData.messages : [];
        window.currentCustomerOffers = offerData.offers || [];
        // --- Set chat header avatar and name to match the latest message from the other party ---
        const messages = window.currentDirectChatMessages || [];
        let partnerMsg = messages.slice().reverse().find(msg => msg.sender_type !== window.currentUserType);
        if (partnerMsg) {
          const avatarElem = document.getElementById('direct-chat-partner-avatar');
          if (avatarElem) {
            avatarElem.innerHTML = `<img src="${partnerMsg.avatar}" class="rounded-circle" style="width:40px;height:40px;object-fit:cover;">`;
          }
          const nameElem = document.getElementById('direct-chat-partner-name');
          if (nameElem) {
            nameElem.textContent = partnerMsg.name || 'Chat';
          }
        }
        // Only call mergeAndRenderChatTimeline for main chat rendering
        mergeAndRenderChatTimeline();
        if (window.currentUserType === 'user' && window.currentDirectChatMessages.length) {
            const lastMsg = window.currentDirectChatMessages[window.currentDirectChatMessages.length - 1];
                window.currentDirectSubuserId = lastMsg.subuser_id || null;
                if (typeof window.populateSubuserDropdown === 'function') {
                    window.populateSubuserDropdown(function() {
                        if (typeof window.syncSubuserDropdownSelection === 'function') {
                            window.syncSubuserDropdownSelection(window.currentDirectSubuserId);
                        }
                    }, window.currentDirectSubuserId);
                }
            }
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
        // Remove: Render customer offers first (handled by mergeAndRenderChatTimeline)
        // if (window.currentCustomerOffers && window.currentCustomerOffers.length > 0) {
        //     window.currentCustomerOffers.forEach(offer => {
        //         renderCustomerOffer(offer);
        //     });
        // }
        if (!messages.length) {
            // Only show empty if there are no messages and no offers
            if (!window.currentCustomerOffers || window.currentCustomerOffers.length === 0) {
            container.innerHTML = '<div class="text-center text-muted py-4">No messages yet.</div>';
            }
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

// Render customer offer
function renderCustomerOffer(offer) {
    const container = document.getElementById('direct-chat-messages');
    // Determine side: right for seller's own offers, left otherwise
    let isMe = false;
    if (window.currentUserType === 'seller' && String(offer.seller_id) === String(window.currentUserId)) {
        isMe = true;
    }
    const offerDiv = document.createElement('div');
    offerDiv.className = 'd-flex mb-3 ' + (isMe ? 'justify-content-end' : 'justify-content-start');
    // Use correct seller avatar (admin img path if photo exists)
    let sellerAvatar = '/assets/img/users/profile.jpeg';
    if (offer.seller && offer.seller.photo) {
        sellerAvatar = '/assets/admin/img/seller-photo/' + offer.seller.photo;
    } else if (offer.seller && offer.seller.avatar_url) {
        sellerAvatar = offer.seller.avatar_url;
    }
    // Create avatar img with fallback
    const img = document.createElement('img');
    img.src = sellerAvatar;
    img.alt = 'Seller';
    img.className = 'rounded-circle me-2';
    img.style.width = '40px';
    img.style.height = '40px';
    img.onerror = function() {
        this.onerror = null;
        this.src = '/assets/img/users/profile.jpeg';
    };
    // Ensure bgColor and borderColor are defined before use
    let bgColor = 'bg-light';
    let borderColor = 'border-secondary';
    if (offer.status === 'accepted') {
        bgColor = 'bg-success bg-gradient text-white';
        borderColor = 'border-success';
    } else if (offer.status === 'declined') {
        bgColor = 'bg-danger bg-gradient text-white';
        borderColor = 'border-danger';
    } else if (offer.status === 'expired') {
        bgColor = 'bg-secondary bg-gradient';
        borderColor = 'border-secondary';
    } else if (offer.status === 'checkout_pending') {
        bgColor = 'bg-warning bg-gradient';
        borderColor = 'border-warning';
    } else if (offer.status === 'pending') {
        bgColor = 'bg-primary bg-gradient';
        borderColor = 'border-primary';
    }
    let actionButtons = '';
    if (window.currentUserType === 'user' && (offer.status === 'pending' || offer.status === 'checkout_pending')) {
        actionButtons = `
            <div class="d-flex gap-2 mt-3">
                <button class="btn btn-accept btn-success" onclick="acceptCustomerOffer(${offer.id})">
                    <i class="fas fa-check"></i> Accept
                </button>
                <button class="btn btn-decline btn-danger" onclick="declineCustomerOffer(${offer.id})">
                    <i class="fas fa-times"></i> Decline
                </button>
            </div>
        `;
    }
    let statusBadge = '';
    // Always reserve space for the badge to prevent resizing
    let statusText = '';
    let badgeClass = 'badge-secondary';
    if (offer.status === 'accepted') {
        badgeClass = 'badge-success';
        statusText = 'Accepted';
    } else if (offer.status === 'declined') {
        badgeClass = 'badge-danger';
        statusText = 'Declined';
    } else if (offer.status === 'expired') {
        badgeClass = 'badge-secondary';
        statusText = 'Expired';
    } else if (offer.status === 'checkout_pending') {
        badgeClass = 'badge-warning';
        statusText = 'Checkout Pending';
    } else if (offer.status === 'pending') {
        badgeClass = 'badge-primary';
        statusText = 'waiting...';
    }
    // Always render the badge container with a fixed width
    statusBadge = `<span class="badge ${badgeClass} float-end" style="min-width:120px;display:inline-block;text-align:center;">${statusText}</span>`;
    let formInfo = '';
    if (offer.form) {
        formInfo = `<div class="small text-muted mt-2"><i class="fas fa-file-alt"></i> Form attached: ${offer.form.name}</div>`;
    }
    let expirationInfo = '';
    if (offer.expires_at) {
        const expiresDate = new Date(offer.expires_at);
        expirationInfo = `<div class="small text-muted mt-1"><i class="fas fa-clock"></i> Expires: ${expiresDate.toLocaleString()}</div>`;
    }
    offerDiv.innerHTML = `
        <div class='chat-bubble ${isMe ? 'me-bubble flex-row-reverse' : 'other-bubble'} d-flex align-items-start' style='gap:10px;max-width:80%;'>
            <img src='${img.src}' class='rounded-circle me-2' style='width:40px;height:40px;object-fit:cover;'>
            <div class='chat-text customer-offer-card ${bgColor} p-3 rounded border ${borderColor} ${offer.status}' style='min-width:300px;max-width:400px;word-break:break-word;'>
                <div class="d-flex justify-content-between align-items-start">
                    <h6 class="mb-2"><i class="fas fa-gift text-success"></i> ${escapeHtml(offer.title)}</h6>
                    ${statusBadge}
                </div>
                <div class="mb-2 offer-description">${escapeHtml(offer.description)}</div>
                <div class="fw-bold text-success mb-2">${offer.currency_symbol}${offer.price}</div>
                ${formInfo}
                ${expirationInfo}
                <div class="small text-muted mt-2">
                    <i class="fas fa-user"></i> ${escapeHtml(offer.seller ? offer.seller.username : 'Seller')}
                    <span class="ms-2"><i class="fas fa-calendar"></i> ${formatTime(offer.created_at)}</span>
                </div>
                ${actionButtons}
            </div>
        </div>
    `;
    container.appendChild(offerDiv);
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
    if (typeof Pusher === 'undefined') return;
    if (window.currentDirectChatPusherChannel) {
        window.currentDirectChatPusherChannel.unbind_all();
        window.currentDirectChatPusherChannel.unsubscribe();
    }
    var pusherKeyMeta = document.querySelector('meta[name="pusher-key"]');
    var pusherClusterMeta = document.querySelector('meta[name="pusher-cluster"]');
    var pusherKey = window.pusherKey || (pusherKeyMeta ? pusherKeyMeta.getAttribute('content') : null);
    var pusherCluster = window.pusherCluster || (pusherClusterMeta ? pusherClusterMeta.getAttribute('content') : null);
    if (!pusherKey || !pusherCluster) return;
    if (!window.directChatPusherInstance) {
        window.directChatPusherInstance = new Pusher(pusherKey, {
            cluster: pusherCluster
        });
    }
    var channelName = 'offer-channel.' + chatId;
    console.log('Subscribing to Pusher channel:', channelName);
    window.currentDirectChatPusherChannel = window.directChatPusherInstance.subscribe(channelName);
    window.currentDirectChatPusherChannel.bind('customer-offer.created', function(data) {
        console.log('Received real-time offer CREATED event (classic Pusher):', data);
        if (!window.currentCustomerOffers) window.currentCustomerOffers = [];
        if (!window.currentCustomerOffers.some(o => o.id === data.offer.id)) {
            window.currentCustomerOffers.push(data.offer);
            mergeAndRenderChatTimeline();
        }
    });
    window.currentDirectChatPusherChannel.bind('customer-offer.accepted', function(data) {
        console.log('Received real-time offer ACCEPTED event (classic Pusher):', data);
        if (!window.currentCustomerOffers) window.currentCustomerOffers = [];
        const idx = window.currentCustomerOffers.findIndex(o => o.id === data.offer.id);
        if (idx !== -1) {
            window.currentCustomerOffers[idx] = data.offer;
        } else {
            window.currentCustomerOffers.push(data.offer);
        }
        mergeAndRenderChatTimeline();
    });
    window.currentDirectChatPusherChannel.bind('customer-offer.declined', function(data) {
        console.log('Received real-time offer DECLINED event (classic Pusher):', data);
        if (!window.currentCustomerOffers) window.currentCustomerOffers = [];
        const idx = window.currentCustomerOffers.findIndex(o => o.id === data.offer.id);
        if (idx !== -1) {
            window.currentCustomerOffers[idx] = data.offer;
        } else {
            window.currentCustomerOffers.push(data.offer);
        }
        mergeAndRenderChatTimeline();
    });
    window.currentDirectChatPusherChannel.bind('customer-offer.checkout_pending', function(data) {
        console.log('Received real-time offer CHECKOUT_PENDING event (classic Pusher):', data);
        if (!window.currentCustomerOffers) window.currentCustomerOffers = [];
        const idx = window.currentCustomerOffers.findIndex(o => o.id === data.offer.id);
        if (idx !== -1) {
            window.currentCustomerOffers[idx] = data.offer;
        } else {
            window.currentCustomerOffers.push(data.offer);
        }
        mergeAndRenderChatTimeline();
    });
}

// Merge offers and messages by timestamp and render in order
function mergeAndRenderChatTimeline() {
    const container = document.getElementById('direct-chat-messages');
    if (!container) return;
    // Get messages and offers
    const messages = window.currentDirectChatMessages || [];
    const offers = window.currentCustomerOffers || [];
    // Tag each with type and timestamp
    const timeline = [];
    messages.forEach(msg => {
        const ts = new Date(msg.created_at).getTime();
        console.log('[DEBUG] Message created_at:', msg.created_at, '->', ts);
        timeline.push({ ...msg, _type: 'message', _ts: ts });
    });
    offers.forEach(offer => {
        const ts = new Date(offer.created_at).getTime();
        console.log('[DEBUG] Offer created_at:', offer.created_at, '->', ts);
        timeline.push({ ...offer, _type: 'offer', _ts: ts });
    });
    // Sort by timestamp ascending
    timeline.sort((a, b) => a._ts - b._ts);
    console.log('[DEBUG] Merged timeline:', timeline.map(item => ({ type: item._type, created_at: item.created_at, _ts: item._ts, id: item.id })));
    // Render
    container.innerHTML = '';
    timeline.forEach(item => {
        if (item._type === 'message') {
            renderDirectChatMessage(item);
        } else if (item._type === 'offer') {
            renderCustomerOffer(item);
        }
    });
    scrollDirectChatToBottom();
}

// Helper: render a single direct chat message (for timeline merge)
function renderDirectChatMessage(msg) {
    // Render a single message bubble (do not clear chat)
    const container = document.getElementById('direct-chat-messages');
    if (!container) return;
    const isAdmin = window.currentUserType === 'admin';
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
    
    // Customer Offer event listeners
    const customerOfferBtn = document.getElementById('customer-offer-btn');
    if (customerOfferBtn) {
        customerOfferBtn.addEventListener('click', function() {
            // Load forms when opening the modal
            loadCustomerOfferForms();
            
            // Show the modal
            const modal = document.getElementById('customerOfferModal');
            if (window.bootstrap && bootstrap.Modal) {
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            } else if (window.$ && window.$.fn && window.$.fn.modal) {
                $(modal).modal('show');
            }
        });
    }
    
    // Form selection change event
    const offerFormSelect = document.getElementById('offer-form');
    if (offerFormSelect) {
        offerFormSelect.addEventListener('change', function() {
            showFormPreview(this.value);
        });
    }
    
    // Create offer button event
    const createOfferBtn = document.getElementById('create-offer-btn');
    if (createOfferBtn) {
        createOfferBtn.addEventListener('click', createCustomerOffer);
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
        var avatarUrl = '/assets/img/users/profile.jpeg';
        if (user && user.avatar_url) {
          avatarUrl = user.avatar_url;
        } else if (user && user.image && user.is_subuser) {
          avatarUrl = '/assets/img/subusers/' + user.image;
        } else if (user && user.image) {
          avatarUrl = '/assets/img/users/' + user.image;
        }
        window.openDirectChatModal(data.id, user.username, avatarUrl, data.seller ? data.seller.id : null, user.username);
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
        var sellerAvatarUrl = seller.avatar_url || '/assets/img/sellers/profile.jpeg';
        window.openDirectChatModal(data.id, seller.username, sellerAvatarUrl, seller.id, null);
      });
      userList.insertBefore(item, userList.firstChild);
    }
  });
})();

// Add custom styles for the Customer Offer button and form preview
(function() {
  const style = document.createElement('style');
  style.innerHTML = `
    .customer-offer-btn {
      font-size: 1rem;
      padding: 0.45rem 1.3rem;
      font-weight: 600;
      border-radius: 22px;
      box-shadow: 0 2px 8px rgba(40,167,69,0.10);
      margin-left: 0.75rem !important;
      margin-right: 0.25rem;
      margin-bottom: 0.1rem;
      margin-top: 0.1rem;
      border: 1.5px solid #28a745;
      background: linear-gradient(90deg, #28a745 80%, #34ce57 100%);
      color: #fff !important;
      letter-spacing: 0.5px;
      transition: background 0.18s, box-shadow 0.18s, border 0.18s;
    }
    .customer-offer-btn:hover, .customer-offer-btn:focus {
      background: linear-gradient(90deg, #218838 80%, #43e97b 100%) !important;
      box-shadow: 0 4px 16px rgba(40,167,69,0.18);
      border-color: #218838;
      color: #fff !important;
    }
    #direct-chat-form {
      gap: 0.5rem !important;
      align-items: center;
    }
    #direct-chat-input {
      min-width: 0;
      font-size: 1rem;
      border-radius: 16px;
      padding: 0.5rem 1rem;
      margin-right: 0.25rem;
      margin-left: 0.1rem;
      background: #181f2a;
      color: #fff;
      border: 1.5px solid #232b3b;
      box-shadow: none;
      transition: border 0.18s;
    }
    #direct-chat-input:focus {
      border: 1.5px solid #28a745;
      background: #232b3b;
      color: #fff;
    }
    #direct-chat-form .btn-primary {
      border-radius: 50%;
      width: 44px;
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.25rem;
      margin-left: 0.1rem;
      margin-right: 0.1rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      padding: 0;
      background: linear-gradient(90deg, #007bff 80%, #00c6ff 100%);
      border: none;
      transition: background 0.18s, box-shadow 0.18s;
    }
    #direct-chat-form .btn-primary:hover, #direct-chat-form .btn-primary:focus {
      background: linear-gradient(90deg, #0056b3 80%, #00c6ff 100%);
      box-shadow: 0 4px 16px rgba(0,123,255,0.18);
    }
    .modal-dialog.modal-xl {
      min-width: 540px !important;
      max-width: 900px;
    }
    @media (max-width: 700px) {
      .modal-dialog.modal-xl {
        min-width: 98vw !important;
        max-width: 100vw;
      }
    }
    #form-preview-section label.form-label, #form-preview-section label.form-label.fw-bold {
      color: #222 !important;
      font-weight: 600 !important;
      font-size: 1rem;
    }
    #form-preview-section .text-danger {
      color: #e3342f !important;
      font-weight: bold;
      margin-left: 2px;
    }
    #form-preview-section input, #form-preview-section textarea, #form-preview-section select {
      color: #111 !important;
      background: #fff !important;
      border: 1px solid #d1d5db;
      font-size: 1rem;
    }
    .me-bubble .chat-text, .chat-bubble.me-bubble .chat-text {
      background: #e6f0fa !important;
      color: #1a2330 !important;
      border: 1.5px solid #b3d4fc;
      box-shadow: 0 2px 8px rgba(0,123,255,0.06);
    }
    .me-bubble .chat-text .small {
      color: #5c4848 !important;
    }
    /* Offer Card Styling */
    .customer-offer-card {
      border-radius: 18px !important;
      box-shadow: 0 4px 24px rgba(40,167,69,0.08), 0 1.5px 6px rgba(0,0,0,0.04);
      border: 1.5px solid #e3e9ef !important;
      background: #f8fafc !important;
      padding: 2rem 2.2rem 1.5rem 2.2rem !important;
      margin-bottom: 1.5rem;
      transition: box-shadow 0.18s, border 0.18s;
    }
    .customer-offer-card.accepted {
      border-color: #43e97b !important;
      background: linear-gradient(90deg, #e6ffe6 80%, #f8fafc 100%) !important;
      box-shadow: 0 4px 24px rgba(40,167,69,0.13);
    }
    .customer-offer-card.declined {
      border-color: #ffb3b3 !important;
      background: linear-gradient(90deg, #fff0f0 80%, #f8fafc 100%) !important;
      box-shadow: 0 4px 24px rgba(220,53,69,0.10);
    }
    .customer-offer-card.expired {
      border-color: #d6d8db !important;
      background: linear-gradient(90deg, #f4f4f4 80%, #f8fafc 100%) !important;
      box-shadow: 0 4px 24px rgba(108,117,125,0.10);
    }
    .customer-offer-card h6 {
      font-size: 1.25rem;
      font-weight: 700;
      color: #218838;
      margin-bottom: 0.5rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .customer-offer-card .fw-bold.text-success {
      color: #218838 !important;
      font-size: 1.15rem;
      margin-bottom: 0.5rem;
    }
    .customer-offer-card .small, .customer-offer-card .text-muted {
      color: #6c757d !important;
      font-size: 0.97rem;
    }
    .customer-offer-card .btn {
      border-radius: 22px !important;
      font-weight: 600;
      font-size: 1rem;
      padding: 0.45rem 1.5rem;
      box-shadow: 0 2px 8px rgba(0,0,0,0.07);
      border: none;
      margin-right: 0.5rem;
      margin-top: 0.5rem;
      transition: background 0.18s, box-shadow 0.18s, color 0.18s;
    }
    .customer-offer-card .btn-success, .customer-offer-card .btn-accept {
      background: linear-gradient(90deg, #28a745 80%, #43e97b 100%) !important;
      color: #fff !important;
      border: none;
    }
    .customer-offer-card .btn-success:hover, .customer-offer-card .btn-accept:hover {
      background: linear-gradient(90deg, #218838 80%, #43e97b 100%) !important;
      color: #fff !important;
      box-shadow: 0 4px 16px rgba(40,167,69,0.13);
    }
    .customer-offer-card .btn-danger, .customer-offer-card .btn-decline {
      background: linear-gradient(90deg, #dc3545 80%, #ff7675 100%) !important;
      color: #fff !important;
      border: none;
    }
    .customer-offer-card .btn-danger:hover, .customer-offer-card .btn-decline:hover {
      background: linear-gradient(90deg, #b71c1c 80%, #ff7675 100%) !important;
      color: #fff !important;
      box-shadow: 0 4px 16px rgba(220,53,69,0.13);
    }
    .customer-offer-card .btn:active {
      box-shadow: 0 2px 8px rgba(0,0,0,0.10) !important;
    }
    .customer-offer-card .badge {
      font-size: 0.95rem;
      border-radius: 8px;
      padding: 0.3em 0.8em;
      font-weight: 600;
      margin-left: 0.5rem;
    }
    .customer-offer-card .d-flex.gap-2.mt-3 {
      gap: 1rem !important;
      margin-top: 1.2rem !important;
    }
    .customer-offer-card .d-flex.align-items-start {
      gap: 1.2rem !important;
    }
    .customer-offer-card .d-flex.justify-content-between.align-items-start {
      margin-bottom: 0.5rem;
    }
    .customer-offer-card .offer-description {
      color: #1a1a1a !important;
      font-size: 1.08rem;
      font-weight: 500;
      letter-spacing: 0.01em;
      text-shadow: 0 1px 2px rgba(255,255,255,0.12);
    }
    .customer-offer-card .badge-success {
      background: linear-gradient(90deg, #28a745 80%, #43e97b 100%) !important;
      color: #fff !important;
      border: 2px solid #218838 !important;
      box-shadow: 0 2px 8px rgba(40,167,69,0.18);
      font-weight: 700;
      text-shadow: 0 1px 2px rgba(0,0,0,0.12);
    }
    .customer-offer-card .badge-danger {
      background: linear-gradient(90deg, #dc3545 80%, #ff7675 100%) !important;
      color: #fff !important;
      border: 2px solid #b71c1c !important;
      box-shadow: 0 2px 8px rgba(220,53,69,0.18);
      font-weight: 700;
      text-shadow: 0 1px 2px rgba(0,0,0,0.12);
    }
  `;
  document.head.appendChild(style);
})();