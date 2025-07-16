@extends('frontend.layout')

@php $title = __('Create Customer Brief'); @endphp

@section('pageHeading')
  {{ $title }}
@endsection

@section('content')
  @includeIf('frontend.partials.breadcrumb', ['breadcrumb' => $breadcrumb ?? null, 'title' => $title])
  <section class="user-dashboard pt-100 pb-60">
    <div class="container">
      <div class="row">
        @includeIf('frontend.user.side-navbar')
        <div class="col-lg-9">
          <div class="card shadow rounded-4 border-0">
            <div class="card-header bg-white rounded-top-4 border-bottom-0" style="padding: 2rem 2rem 1rem 2rem;">
              <h3 class="mb-0 fw-bold" style="letter-spacing: 1px;">Create Customer Brief</h3>
            </div>
            <div class="card-body" style="padding: 2rem; background: #fcfcfc;">
              <form action="{{ route('user.customer-briefs.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="row g-4">
                  <div class="col-md-6">
                    <label for="profile" class="form-label fw-semibold">Profile</label>
                    <div class="dropdown mb-3">
                      <button class="form-control text-start d-flex align-items-center" type="button" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="height:48px;">
                        <img src="{{ auth()->user()->image ? asset('assets/img/users/' . auth()->user()->image) : asset('assets/img/profile.jpg') }}" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
                        <span id="selectedProfileText">Myself ({{ auth()->user()->username }})</span>
                      </button>
                      <ul class="dropdown-menu w-100" aria-labelledby="profileDropdown" style="max-height:220px;overflow-y:auto;">
                        <li>
                          <a class="dropdown-item d-flex align-items-center profile-option" href="#" data-value="">
                            <img src="{{ auth()->user()->image ? asset('assets/img/users/' . auth()->user()->image) : asset('assets/img/profile.jpg') }}" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
                            Myself ({{ auth()->user()->username }})
                          </a>
                        </li>
                        @foreach(auth()->user()->subusers as $subuser)
                        <li>
                          <a class="dropdown-item d-flex align-items-center profile-option" href="#" data-value="{{ $subuser->id }}">
                            <img src="{{ $subuser->image ? asset('assets/img/subusers/' . $subuser->image) : asset('assets/img/profile.jpg') }}" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
                            {{ $subuser->username }}
                          </a>
                        </li>
                        @endforeach
                      </ul>
                      <input type="hidden" name="subuser_id" id="profile" value="">
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label for="delivery_time" class="form-label fw-semibold">Delivery Time (days)</label>
                    <input type="number" name="delivery_time" id="delivery_time" class="form-control rounded-3" min="1" required style="height:48px;">
                  </div>
                  <div class="col-md-12">
                    <label for="title" class="form-label fw-semibold">Title</label>
                    <input type="text" name="title" id="title" class="form-control rounded-3" required style="height:48px;">
                  </div>
                  <div class="col-md-12">
                    <label for="description" class="form-label fw-semibold">Description</label>
                    <textarea name="description" id="description" class="form-control rounded-3" rows="4" required style="min-height:100px;"></textarea>
                  </div>
                  <div class="row g-4 align-items-end mb-4">
                    <div class="col-md-6">
                      <label for="tags" class="form-label fw-semibold">Tags</label>
                      <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-tags text-muted"></i></span>
                        <input type="text" name="tags" id="tags" class="form-control rounded-3 tagsinput-custom" data-role="tagsinput" required style="height:48px;">
                      </div>
                      <small class="form-text text-muted">Type a tag and press comma or enter.</small>
                    </div>
                    <div class="col-md-6">
                      <label for="price" class="form-label fw-semibold">Price</label>
                      <div id="priceInputWrapper">
                        <input type="number" name="price" id="price" class="form-control rounded-3" step="1" min="0" style="height:48px;">
                      </div>
                      <div class="form-check mt-2">
                        <input type="checkbox" name="request_quote" id="request_quote" class="form-check-input" value="1">
                        <label for="request_quote" class="form-check-label">Request a Quote (leave price empty)</label>
                      </div>
                    </div>
                  </div>
                  
                  <!-- Attachments Section -->
                  <div class="col-md-12">
                    <label for="attachments" class="form-label fw-semibold">Attachments</label>
                    <div class="input-group">
                      <span class="input-group-text bg-white"><i class="fas fa-paperclip text-muted"></i></span>
                      <input type="file" name="attachments[]" id="attachments" class="form-control rounded-3" multiple accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif,.zip,.rar" style="height:48px;">
                    </div>
                    <small class="form-text text-muted">Allowed files: PDF, DOC, DOCX, TXT, JPG, PNG, GIF, ZIP, RAR (Max 5 files, 10MB each)</small>
                    
                    <!-- File Preview Area -->
                    <div id="filePreviewArea" class="mt-3" style="display: none;">
                      <h6 class="fw-semibold mb-2">Selected Files:</h6>
                      <div id="fileList" class="d-flex flex-wrap gap-2"></div>
                    </div>
                  </div>
                  <div class="col-12 d-flex align-items-center gap-3 mt-4">
                    <button type="submit" class="btn create-brief-btn px-4 py-2" style="background: var(--color-primary); border-color: var(--color-primary); color: #fff; font-weight: 600; min-width: 140px; border-radius: 2rem; font-size: 1.1rem;">Create Brief</button>
                    <a href="{{ route('user.customer-briefs.index') }}" class="btn btn-outline-danger px-4 py-2 d-flex align-items-center gap-2 cancel-brief-btn" style="border-radius: 2rem; font-weight: 600; font-size: 1.1rem; border-width: 2px;">
                      <i class="fas fa-times"></i> Cancel
                    </a>
                  </div>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection

@push('scripts')
<script src="{{ asset('assets/js/bootstrap-tagsinput.min.js') }}"></script>
<style>
.create-brief-btn:hover, .create-brief-btn:focus {
  background: #fff !important;
  color: var(--color-primary) !important;
  border: 2px solid var(--color-primary) !important;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.cancel-brief-btn {
  border-color: var(--color-primary) !important;
  color: var(--color-primary) !important;
  background: #fff !important;
  transition: all 0.2s;
}
.cancel-brief-btn:hover, .cancel-brief-btn:focus {
  background: var(--color-primary) !important;
  color: #fff !important;
  border-color: var(--color-primary) !important;
}
.bootstrap-tagsinput {
  width: 100%;
  min-height: 48px;
  border-radius: 1.5rem;
  border: 1.5px solid #e0e0e0;
  background: #fff;
  padding: 6px 12px;
  box-shadow: none;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
}
.bootstrap-tagsinput .tag {
  background: var(--color-primary);
  color: #fff;
  border-radius: 1rem;
  padding: 0.35em 0.9em;
  font-size: 1em;
  margin-right: 0.4em;
  margin-bottom: 0.3em;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
}
.bootstrap-tagsinput .tag [data-role="remove"] {
  margin-left: 0.5em;
  color: #fff;
  opacity: 0.8;
  font-size: 1.1em;
  cursor: pointer;
}
.bootstrap-tagsinput .tag [data-role="remove"]:hover {
  color: #fff;
  opacity: 1;
}
.bootstrap-tagsinput .tag [data-role="remove"]:after {
  content: "×";
  font-weight: bold;
  font-size: 1.2em;
}
.card.shadow.rounded-4 {
  box-shadow: 0 4px 32px rgba(0,0,0,0.08) !important;
  border-radius: 2rem !important;
}
.form-label.fw-semibold {
  font-size: 1.08rem;
  color: #222;
}
input[type="number"]::-webkit-inner-spin-button,
input[type="number"]::-webkit-outer-spin-button {
  opacity: 1;
  width: 10px;
  margin-right: 2px;
}
input[type="number"] {
  -moz-appearance: textfield;
}
</style>
<script>
document.querySelectorAll('.profile-option').forEach(function(option) {
  option.addEventListener('click', function(e) {
    e.preventDefault();
    var value = this.getAttribute('data-value');
    var text = this.innerText.trim();
    var img = this.querySelector('img').outerHTML;
    document.getElementById('profile').value = value;
    document.getElementById('selectedProfileText').innerHTML = text;
    var btn = document.getElementById('profileDropdown');
    btn.innerHTML = img + ' <span id="selectedProfileText">' + text + '</span>';
  });
});

document.addEventListener('DOMContentLoaded', function() {
  var requestQuote = document.getElementById('request_quote');
  var priceWrapper = document.getElementById('priceInputWrapper');
  function togglePriceField() {
    if (requestQuote.checked) {
      priceWrapper.style.display = 'none';
    } else {
      priceWrapper.style.display = '';
    }
  }
  requestQuote.addEventListener('change', togglePriceField);
  
  // File upload preview functionality
  const fileInput = document.getElementById('attachments');
  const filePreviewArea = document.getElementById('filePreviewArea');
  const fileList = document.getElementById('fileList');
  
  fileInput.addEventListener('change', function() {
    const files = Array.from(this.files);
    fileList.innerHTML = '';
    
    if (files.length > 0) {
      filePreviewArea.style.display = 'block';
      
      files.forEach((file, index) => {
        const fileItem = document.createElement('div');
        fileItem.className = 'file-item d-flex align-items-center gap-2 p-2 bg-light rounded border';
        fileItem.style.maxWidth = '300px';
        
        const fileIcon = document.createElement('i');
        fileIcon.className = getFileIcon(file.name);
        fileIcon.style.color = '#666';
        
        const fileInfo = document.createElement('div');
        fileInfo.className = 'flex-grow-1';
        fileInfo.style.minWidth = '0';
        
        const fileName = document.createElement('div');
        fileName.className = 'fw-semibold text-truncate';
        fileName.textContent = file.name;
        fileName.style.maxWidth = '200px';
        
        const fileSize = document.createElement('div');
        fileSize.className = 'text-muted small';
        fileSize.textContent = formatFileSize(file.size);
        
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-outline-danger';
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        removeBtn.onclick = function() {
          fileItem.remove();
          if (fileList.children.length === 0) {
            filePreviewArea.style.display = 'none';
          }
        };
        
        fileInfo.appendChild(fileName);
        fileInfo.appendChild(fileSize);
        fileItem.appendChild(fileIcon);
        fileItem.appendChild(fileInfo);
        fileItem.appendChild(removeBtn);
        fileList.appendChild(fileItem);
      });
    } else {
      filePreviewArea.style.display = 'none';
    }
  });
  
  function getFileIcon(fileName) {
    const ext = fileName.split('.').pop().toLowerCase();
    const iconMap = {
      'pdf': 'fas fa-file-pdf',
      'doc': 'fas fa-file-word',
      'docx': 'fas fa-file-word',
      'txt': 'fas fa-file-alt',
      'jpg': 'fas fa-file-image',
      'jpeg': 'fas fa-file-image',
      'png': 'fas fa-file-image',
      'gif': 'fas fa-file-image',
      'zip': 'fas fa-file-archive',
      'rar': 'fas fa-file-archive'
    };
    return iconMap[ext] || 'fas fa-file';
  }
  
  function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }
  togglePriceField(); // initial state
});
</script>
@endpush 