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
</style>
@endpush
<div class="modal fade" id="directChatModal" tabindex="-1" aria-labelledby="directChatModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
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
        <div class="progress mt-2 d-none" id="direct-chat-upload-progress">
          <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
        </div>
      </div>
    </div>
  </div>
</div> 