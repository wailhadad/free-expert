@extends('backend.layout')

@section('content')
<style>
  .subuser-edit-form {
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    padding: 2rem;
  }
  
  .form-group {
    margin-bottom: 1.5rem;
  }
  
  .form-group label {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
    display: block;
  }
  
  .form-control {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 0.75rem 1rem;
    transition: all 0.3s ease;
  }
  
  .form-control:focus {
    border-color: #007bff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,0.25);
  }
  
  .profile-image-container {
    position: relative;
    display: inline-block;
    margin-bottom: 1rem;
  }
  
  .profile-image {
    border-radius: 12px;
    border: 3px solid #fff;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transition: transform 0.3s ease;
  }
  
  .profile-image:hover {
    transform: scale(1.05);
  }
  
  .remove-image-btn {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
    transition: all 0.3s ease;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
  }
  
  .remove-image-btn:hover {
    background: #c82333;
    transform: scale(1.1);
  }
  
  .file-input-wrapper {
    position: relative;
    overflow: hidden;
    display: inline-block;
    width: 100%;
  }
  
  .file-input-wrapper input[type=file] {
    font-size: 100px;
    position: absolute;
    left: 0;
    top: 0;
    opacity: 0;
    cursor: pointer;
  }
  
  .file-input-btn {
    display: inline-block;
    padding: 0.75rem 1.5rem;
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 8px;
    color: #6c757d;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    width: 100%;
  }
  
  .file-input-btn:hover {
    background: #e9ecef;
    border-color: #007bff;
    color: #007bff;
  }
  
  .btn {
    border-radius: 8px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    transition: all 0.3s ease;
  }
  
  .btn-primary {
    background: #007bff;
    border-color: #007bff;
  }
  
  .btn-primary:hover {
    background: #0056b3;
    border-color: #0056b3;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,123,255,0.3);
  }
  
  .btn-secondary {
    background: #6c757d;
    border-color: #6c757d;
  }
  
  .btn-secondary:hover {
    background: #5a6268;
    border-color: #545b62;
    transform: translateY(-2px);
  }
  
  .form-actions {
    margin-top: 2rem;
    padding-top: 1.5rem;
    border-top: 1px solid #e9ecef;
  }
  
  .card {
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
  }
  
  .card-header {
    background: #f8f9fa;
    border-bottom: 1px solid #e9ecef;
    border-radius: 12px 12px 0 0 !important;
  }
  
  .card-body {
    padding: 2rem;
  }
</style>

<div class="container mt-4">
  <div class="card">
    <div class="card-header">
      <h4 class="mb-0">{{ __('Edit Subuser') }}: <span class="text-primary">{{ $subuser->username }}</span></h4>
    </div>
    <div class="card-body">
      <form action="{{ route('admin.user_management.subuser.edit', $subuser->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <label>{{ __('Profile Image') }}</label>
              @if($subuser->image)
                <div class="profile-image-container">
                  <img src="{{ asset('assets/img/subusers/' . $subuser->image) }}" 
                       alt="{{ $subuser->username }}" 
                       class="profile-image" 
                       width="100" height="100">
                  <button type="button" 
                          class="remove-image-btn" 
                          onclick="removeProfileImage()" 
                          title="{{ __('Remove image') }}">
                    <i class="fas fa-times"></i>
                  </button>
                </div>
                <small class="d-block text-muted mb-2">{{ __('Current image') }}</small>
              @endif
              <div class="file-input-wrapper">
                <input type="file" class="form-control" name="image" id="image" accept="image/*">
                <label for="image" class="file-input-btn">
                  <i class="fas fa-cloud-upload-alt me-2"></i>
                  {{ __('Choose Image') }}
                </label>
              </div>
              <small class="form-text text-muted">{{ __('Supported formats: JPEG, PNG, JPG, GIF, SVG') }}</small>
            </div>
          </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label>{{ __('Username') }}</label>
              <input type="text" class="form-control" value="{{ $subuser->username }}" readonly>
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label>{{ __('First Name') }}</label>
              <input type="text" class="form-control" name="first_name" value="{{ $subuser->first_name }}">
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label>{{ __('Last Name') }}</label>
              <input type="text" class="form-control" name="last_name" value="{{ $subuser->last_name }}">
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label>{{ __('Phone Number') }}</label>
              <input type="text" class="form-control" name="phone_number" value="{{ $subuser->phone_number }}">
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label>{{ __('Country') }}</label>
              <input type="text" class="form-control" name="country" value="{{ $subuser->country }}">
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label>{{ __('State') }}</label>
              <input type="text" class="form-control" name="state" value="{{ $subuser->state }}">
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label>{{ __('City') }}</label>
              <input type="text" class="form-control" name="city" value="{{ $subuser->city }}">
            </div>
          </div>

          <div class="col-md-6">
            <div class="form-group">
              <label>{{ __('Status') }}</label>
              <select class="form-control" name="status">
                <option value="1" @if($subuser->status) selected @endif>{{ __('Active') }}</option>
                <option value="0" @if(!$subuser->status) selected @endif>{{ __('Inactive') }}</option>
              </select>
            </div>
          </div>

          <div class="col-12">
            <div class="form-group">
              <label>{{ __('Address') }}</label>
              <textarea class="form-control" name="address" rows="3">{{ $subuser->address }}</textarea>
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary me-3">
            <i class="fas fa-save me-2"></i> {{ __('Save Changes') }}
          </button>
          <a href="javascript:history.back()" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i> {{ __('Back') }}
          </a>
        </div>
      </form>
    </div>
  </div>
</div>

  <script>
    function removeProfileImage() {
      // Hide the image container
      const imageContainer = document.querySelector('.profile-image-container');
      if (imageContainer) {
        imageContainer.style.display = 'none';
      }
      
      // Create a hidden input to indicate image removal
      const form = document.querySelector('form');
      const hiddenInput = document.createElement('input');
      hiddenInput.type = 'hidden';
      hiddenInput.name = 'remove_image';
      hiddenInput.value = '1';
      form.appendChild(hiddenInput);
    }

  // File input change handler
  document.getElementById('image').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
      // Update the button text
      const label = document.querySelector('.file-input-btn');
      label.innerHTML = '<i class="fas fa-check me-2"></i>' + file.name;
      label.style.background = '#d4edda';
      label.style.borderColor = '#28a745';
      label.style.color = '#155724';
    }
  });
</script>
@endsection 