@php
  if (auth('web')->check()) {
    $userType = 'user';
    $userId = auth('web')->id();
  } elseif (auth('seller')->check()) {
    $userType = 'seller';
    $userId = auth('seller')->id();
  } elseif (auth('admin')->check()) {
    $userType = 'admin';
    $userId = auth('admin')->id();
  } else {
    $userType = '';
    $userId = '';
  }
@endphp
<script>
window.currentUserType = '{{ $userType }}';
window.currentUserId = '{{ $userId }}';
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
</style>
@endpush
<div class="modal fade" id="directChatModal" tabindex="-1" aria-labelledby="directChatModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl"> <!-- Changed to modal-xl for larger chat window -->
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="directChatModalLabel">
          <span id="direct-chat-partner-avatar" class="rounded-circle me-2" style="width:40px;height:40px;overflow:hidden;display:inline-block;background:#eee;"></span>
          <span id="direct-chat-partner-name">Chat</span>
        </h5>
        <button type="button" class="close btn-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-0" style="height:400px;overflow-y:auto;">
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
              <img src="/assets/img/users/profile.jpeg" id="subuserDropdownAvatar" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
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