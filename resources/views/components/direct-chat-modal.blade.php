@php
  if (auth('web')->check()) {
    $userType = 'user';
    $userId = auth('web')->id();
    $userAvatar = auth('web')->user()->image ? asset('assets/img/users/' . auth('web')->user()->image) : asset('assets/img/profile.jpg');
  } elseif (auth('seller')->check()) {
    $userType = 'seller';
    $userId = auth('seller')->id();
    $userAvatar = '';
  } elseif (auth('admin')->check()) {
    $userType = 'admin';
    $userId = auth('admin')->id();
    $userAvatar = '';
  } else {
    $userType = '';
    $userId = '';
    $userAvatar = '';
  }
@endphp
<script>
window.currentUserType = '{{ $userType }}';
window.currentUserId = '{{ $userId }}';
window.currentUserAvatar = @json($userAvatar);
</script>
@push('styles')
<style>
#direct-chat-subuser-dropdown .dropdown-menu {
  min-width: 200px;
  max-width: 260px;
  max-height: 220px;
  overflow-y: auto;
  padding: 0.25rem 0;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
#direct-chat-subuser-dropdown .dropdown-item {
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 220px;
  padding: 0.5rem 1rem;
}
#direct-chat-subuser-dropdown .dropdown-item img {
  flex-shrink: 0;
}
#direct-chat-subuser-dropdown .dropdown-item span {
  display: inline-block;
  vertical-align: middle;
  max-width: 150px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

/* File preview styling */
.selected-file {
  transition: all 0.2s ease;
}

.selected-file:hover {
  background-color: #f8f9fa !important;
}

.selected-file .btn-outline-danger {
  border-width: 1px;
  padding: 0.25rem 0.5rem;
}

.selected-file .btn-outline-danger:hover {
  background-color: #dc3545;
  border-color: #dc3545;
  color: white;
}

/* Customer Offer styling */
.customer-offer-card {
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  transition: all 0.3s ease;
}

.customer-offer-card:hover {
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.customer-offer-card.accepted {
  background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
  border-color: #28a745;
}

.customer-offer-card.declined {
  background: linear-gradient(135deg, #f8d7da 0%, #f5c6cb 100%);
  border-color: #dc3545;
}

.customer-offer-card.expired {
  background: linear-gradient(135deg, #e2e3e5 0%, #d6d8db 100%);
  border-color: #6c757d;
}

.customer-offer-card .btn {
  border-radius: 20px;
  font-weight: 500;
  transition: all 0.2s ease;
}

.customer-offer-card .btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

/* Enhanced Chat Modal Styling */
#directChatModal .modal-dialog {
  margin: 1.75rem auto;
}

#directChatModal .modal-content {
  border-radius: 12px;
  box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

#directChatModal .modal-header {
  border-bottom: 1px solid #e9ecef;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 12px 12px 0 0;
}

#directChatModal .modal-body {
  background-color: #f8f9fa;
}

#directChatModal .modal-footer {
  border-top: 1px solid #e9ecef;
  background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
  border-radius: 0 0 12px 12px;
}

/* Responsive adjustments for smaller screens */
@media (max-width: 768px) {
  #directChatModal .modal-dialog {
    max-width: 95% !important;
    width: 95% !important;
    margin: 0.5rem auto;
  }
  
  #directChatModal .modal-body {
    height: 400px !important;
  }
}

@media (max-width: 576px) {
  #directChatModal .modal-dialog {
    max-width: 98% !important;
    width: 98% !important;
    margin: 0.25rem auto;
  }
  
  #directChatModal .modal-body {
    height: 350px !important;
  }
}

/* Status badge styles for dropdowns */
.badge-sm {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.dropdown-item .badge {
    font-weight: 500;
}

.dropdown-item:hover .badge {
    opacity: 0.8;
}

/* Ensure proper spacing in dropdown items */
.dropdown-item {
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    padding: 0.5rem 1rem !important;
}

.dropdown-item .flex-grow-1 {
    flex: 1;
    min-width: 0;
}

.dropdown-item img {
    flex-shrink: 0;
}

.dropdown-item .badge {
    flex-shrink: 0;
}
</style>
@endpush
<div class="modal fade" id="directChatModal" tabindex="-1" aria-labelledby="directChatModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" style="max-width: 90%; width: 1200px;"> <!-- Custom larger size for bigger chat window -->
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="directChatModalLabel">
          @if ($userType === 'admin')
            <span id="admin-chat-header" style="display:flex;align-items:center;gap:10px;">
              <span id="real-user-avatar" class="rounded-circle" style="width:40px;height:40px;overflow:hidden;display:inline-block;background:#eee;"></span>
              <span id="real-user-name" style="font-weight:600;"></span>
              <span id="as-subuser-block" style="display:none;align-items:center;gap:5px;">
                <span class="mx-1">(as)</span>
                <span id="subuser-avatar" class="rounded-circle" style="width:40px;height:40px;overflow:hidden;display:inline-block;background:#eee;"></span>
                <span id="subuser-name" style="font-weight:600;"></span>
              </span>
              <span class="mx-2">&#8594;</span>
              <span id="seller-avatar" class="rounded-circle" style="width:40px;height:40px;overflow:hidden;display:inline-block;background:#eee;"></span>
              <span id="seller-name" style="font-weight:600;"></span>
            </span>
          @else
          <span id="direct-chat-partner-avatar" class="rounded-circle me-2" style="width:40px;height:40px;overflow:hidden;display:inline-block;background:#eee;"></span>
          <span id="direct-chat-partner-name">Chat</span>
          @endif
        </h5>
        <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-0" style="height:600px;overflow-y:auto;"> <!-- Increased height from 400px to 600px -->
        <div id="direct-chat-messages" class="p-3 chat-wrapper" style="height:100%;overflow-y:auto;display:flex;flex-direction:column;gap:0.5rem;"></div>
      </div>
      <div class="modal-footer">
        @if ($userType !== 'admin')
        <form id="direct-chat-form" class="w-100 d-flex align-items-center gap-2" enctype="multipart/form-data" autocomplete="off">
          <label class="helper-form mb-0">
            <input type="file" name="attachment" id="direct-chat-attachment" class="d-none">
            <i class="far fa-paperclip" style="cursor:pointer;" title="Allowed: .jpg, .jpeg, .png, .rar, .zip, .txt, .doc, .docx, .pdf"></i>
          </label>
          <input type="text" id="direct-chat-input" class="form-control" placeholder="Type a message..." autocomplete="off" required>
          <button type="submit" class="btn btn-primary"><i class="far fa-paper-plane"></i></button>
          
          @if ($userType === 'seller')
          <button type="button" id="customer-offer-btn" class="btn btn-success ms-2 customer-offer-btn" title="Create Customer Offer">
            <i class="fas fa-gift"></i> Customer Offer
          </button>
          @endif
          
          @if ($userType === 'user')
          <div class="dropdown ms-2" id="direct-chat-subuser-dropdown" style="min-width:180px;">
            <button class="btn btn-outline-secondary dropdown-toggle d-flex align-items-center" type="button" id="subuserDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
              <img src="{{ $userAvatar }}" id="subuserDropdownAvatar" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
              <span id="subuserDropdownName">Myself</span>
            </button>
            <ul class="dropdown-menu w-100" aria-labelledby="subuserDropdownBtn" id="subuserDropdownMenu">
              <!-- Subuser options will be populated by JS -->
            </ul>
          </div>
          @endif
        </form>
        @endif
        <!-- File preview area -->
        <div id="direct-chat-file-preview" class="w-100 mt-2 d-none">
          <div class="selected-files-container">
            <!-- Selected files will be displayed here -->
          </div>
        </div>
        <div class="progress mt-2 d-none" id="direct-chat-upload-progress">
          <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
        </div>
      </div>
    </div>
  </div>
</div>

@include('components.customer-offer-modal') 

@push('scripts')
<script>
// Global flag to prevent multiple initializations
window.subuserDropdownInitialized = false;

// Load prioritized subusers for dropdown
function loadPrioritizedSubusers() {
    if (window.currentUserType !== 'user') {
        return; // Only for users
    }

    // Prevent multiple initializations
    if (window.subuserDropdownInitialized) {
        console.log('Subuser dropdown already initialized, skipping...');
        return;
    }

    const dropdownMenu = document.getElementById('subuserDropdownMenu');
    if (!dropdownMenu) {
        console.error('Dropdown menu element not found');
        return;
    }

    // Check if dropdown is already populated
    const existingItems = dropdownMenu.querySelectorAll('.dropdown-item');
    if (existingItems.length > 0) {
        console.log('Dropdown already populated, skipping...');
        window.subuserDropdownInitialized = true;
        return;
    }

    console.log('Loading prioritized subusers...');

    fetch('/subusers/prioritized', {
        method: 'GET',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        }
    })
    .then(response => response.json())
    .then(data => {
        console.log('API Response:', data);
        
        // Double-check if dropdown is still empty (prevent race conditions)
        const currentItems = dropdownMenu.querySelectorAll('.dropdown-item');
        if (currentItems.length > 0) {
            console.log('Dropdown was populated by another process, skipping...');
            return;
        }

        // Clear existing items (safety check)
        dropdownMenu.innerHTML = '';
        
        // Add a loading indicator
        const loadingItem = document.createElement('li');
        loadingItem.innerHTML = '<div class="dropdown-item-text small text-muted">Loading...</div>';
        dropdownMenu.appendChild(loadingItem);

        // Remove loading indicator
        dropdownMenu.innerHTML = '';
        
        // Add "Myself" option first
        const myselfOption = document.createElement('li');
        myselfOption.innerHTML = `
            <a class="dropdown-item d-flex align-items-center gap-2" href="#" data-id="" data-avatar="${window.currentUserAvatar}" data-name="Myself" data-status="true">
                <img src="${window.currentUserAvatar}" class="rounded-circle" style="width:24px;height:24px;object-fit:cover;">
                <span class="flex-grow-1">Myself</span>
                <span class="badge bg-success badge-sm ms-auto">Active</span>
            </a>
        `;
        dropdownMenu.appendChild(myselfOption);

        // Add subuser options (only if they exist and are unique)
        if (data.subusers && data.subusers.length > 0) {
            const seenIds = new Set();
            data.subusers.forEach(subuser => {
                // Prevent duplicate entries
                if (seenIds.has(subuser.id)) {
                    console.log('Skipping duplicate subuser:', subuser.id);
                    return;
                }
                seenIds.add(subuser.id);
                
                const subuserOption = document.createElement('li');
                const statusBadge = subuser.status ? 
                    '<span class="badge bg-success badge-sm ms-auto">Active</span>' : 
                    '<span class="badge bg-secondary badge-sm ms-auto">Inactive</span>';
                
                // Add disabled class and data attribute for inactive subusers
                const isActive = Boolean(subuser.status);
                const disabledClass = isActive ? '' : 'disabled-subuser';
                const disabledAttr = isActive ? '' : 'data-disabled="true"';
                
                subuserOption.innerHTML = `
                    <a class="dropdown-item d-flex align-items-center gap-2 ${disabledClass}" href="#" data-id="${subuser.id}" data-avatar="${subuser.image}" data-name="${subuser.username}" data-status="${isActive ? 'true' : 'false'}" ${disabledAttr}>
                        <img src="${subuser.image}" class="rounded-circle" style="width:24px;height:24px;object-fit:cover;">
                        <span class="flex-grow-1">${subuser.username}</span>
                        ${statusBadge}
                    </a>
                `;
                dropdownMenu.appendChild(subuserOption);
            });
        }

        // Show prioritization info if applicable (only once)
        if (data.is_prioritized && !dropdownMenu.querySelector('.dropdown-item-text')) {
            const infoDiv = document.createElement('li');
            infoDiv.innerHTML = `
                <div class="dropdown-item-text small text-muted">
                    <i class="fas fa-info-circle"></i>
                    Showing all ${data.actual_count} subusers (${data.total_max_subusers} can be used for orders/chats)
                </div>
            `;
            dropdownMenu.appendChild(infoDiv);
        }

        // Add click handlers only once
        dropdownMenu.querySelectorAll('.dropdown-item').forEach((item, index) => {
            // Remove any existing click handlers to prevent duplicates
            const newItem = item.cloneNode(true);
            item.parentNode.replaceChild(newItem, item);
            
            newItem.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const subuserId = this.getAttribute('data-id');
                const subuserName = this.getAttribute('data-name');
                const subuserImage = this.getAttribute('data-avatar');

                // Update dropdown button
                const dropdownName = document.getElementById('subuserDropdownName');
                const dropdownAvatar = document.getElementById('subuserDropdownAvatar');
                
                if (dropdownName) {
                    dropdownName.textContent = subuserName;
                }
                
                if (dropdownAvatar) {
                    dropdownAvatar.src = subuserImage;
                }

                // Store selected subuser
                window.selectedSubuserId = subuserId || null;

                // Check if this subuser is active/inactive and update chat input state
                const statusAttr = this.getAttribute('data-status');
                const isActive = statusAttr === 'true' || statusAttr === true;
                window.currentSubuserStatus = isActive;
                
                // Update chat input state based on subuser status
                if (typeof window.updateChatInputState === 'function') {
                    window.updateChatInputState();
                }
                
                // Switch to the appropriate chat for this subuser
                if (window.currentDirectSellerId && typeof window.startOrGetChatWithSubuser === 'function') {
                    window.startOrGetChatWithSubuser(
                        window.currentDirectSellerId, 
                        subuserId, 
                        window.currentDirectSellerName, 
                        window.currentDirectSellerAvatar
                    );
                }

                // Add visual feedback
                this.style.backgroundColor = '#e3f2fd';
                setTimeout(() => {
                    this.style.backgroundColor = '';
                }, 500);

                // Close dropdown using Bootstrap 5 method
                const dropdownToggle = document.getElementById('subuserDropdownBtn');
                if (dropdownToggle && window.bootstrap) {
                    const dropdown = bootstrap.Dropdown.getInstance(dropdownToggle);
                    if (dropdown) {
                        dropdown.hide();
                    } else {
                        // Fallback: manually hide dropdown
                        const dropdownMenu = document.getElementById('subuserDropdownMenu');
                        if (dropdownMenu) {
                            dropdownMenu.classList.remove('show');
                            dropdownToggle.setAttribute('aria-expanded', 'false');
                        }
                    }
                }
            });
        });

        // Initialize dropdown with current chat's subuser if available
        if (typeof window.syncSubuserDropdownSelection === 'function') {
            window.syncSubuserDropdownSelection();
        }
        
        // Set initial subuser status to active (Myself is default)
        window.currentSubuserStatus = true;
        
        // Update chat input state based on current subuser
        if (typeof window.updateChatInputState === 'function') {
            window.updateChatInputState();
        }

        // Mark as initialized to prevent re-initialization
        window.subuserDropdownInitialized = true;
        console.log('Subuser dropdown initialized successfully');
    })
    .catch(error => {
        console.error('Error loading subusers:', error);
    });
}

// Load subusers when modal is shown - only once per page load
if (!window.modalInitializationComplete) {
    window.modalInitializationComplete = true;
    
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('directChatModal');
        if (modal) {
            // Use a flag to prevent multiple event listeners
            if (!modal.hasAttribute('data-subuser-listener-added')) {
                modal.setAttribute('data-subuser-listener-added', 'true');
                
                modal.addEventListener('shown.bs.modal', function() {
                    console.log('Direct chat modal shown, loading subusers...');
                    loadPrioritizedSubusers();
                    
                    // Ensure dropdown is properly initialized
                    const dropdownToggle = document.getElementById('subuserDropdownBtn');
                    if (dropdownToggle && window.bootstrap) {
                        // Initialize Bootstrap dropdown if not already done
                        if (!bootstrap.Dropdown.getInstance(dropdownToggle)) {
                            new bootstrap.Dropdown(dropdownToggle);
                        }
                    }
                });
                
                // Reset the flag when modal is hidden
                modal.addEventListener('hidden.bs.modal', function() {
                    console.log('Direct chat modal hidden, resetting subusers flag...');
                    window.subuserDropdownInitialized = false;
                });
            }
        }
    });
}
</script>
@endpush 