@extends('frontend.layout')

@php $title = __('Edit Subuser'); @endphp

@section('pageHeading')
  {{ $title }}
@endsection

@section('content')
  @includeIf('frontend.partials.breadcrumb', ['breadcrumb' => $breadcrumb, 'title' => $title])

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
      border-color: var(--color-primary);
      box-shadow: 0 0 0 0.2rem rgba(var(--color-primary-rgb), 0.25);
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
      border-color: var(--color-primary);
      color: var(--color-primary);
    }
    
    .btn {
      border-radius: 8px;
      padding: 0.75rem 1.5rem;
      font-weight: 600;
      transition: all 0.3s ease;
    }
    
    .btn-primary {
      background: var(--color-primary);
      border-color: var(--color-primary);
    }
    
    .btn-primary:hover {
      background: var(--color-primary);
      border-color: var(--color-primary);
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(var(--color-primary-rgb), 0.3);
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
  </style>

  <!--====== Start Edit Subuser Section ======-->
  <section class="user-dashboard pt-100 pb-60">
    <div class="container">
      <div class="row">
        @includeIf('frontend.user.side-navbar')

        <div class="col-lg-9">
          <div class="row">
            <div class="col-lg-12">
              <div class="subuser-edit-form">
                <div class="title mb-4">
                  <h4 class="mb-0">{{ __('Edit Subuser') }}: <span class="text-primary">{{ $subuser->username }}</span></h4>
                </div>

                <form action="{{ route('user.subusers.update', $subuser->id) }}" method="POST" enctype="multipart/form-data">
                  @csrf
                  
                  <div class="row">
                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="username">{{ __('Username') }} *</label>
                        <input type="text" 
                               class="form-control @error('username') is-invalid @enderror" 
                               id="username" 
                               name="username" 
                               value="{{ old('username', $subuser->username) }}" 
                               required>
                        @error('username')
                          <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                      </div>
                    </div>

                    <div class="col-md-6">
                      <div class="form-group">
                        <label for="image">{{ __('Profile Image') }}</label>
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
                          <input type="file" 
                                 class="form-control @error('image') is-invalid @enderror" 
                                 id="image" 
                                 name="image" 
                                 accept="image/*">
                          <label for="image" class="file-input-btn">
                            <i class="fas fa-cloud-upload-alt me-2"></i>
                            {{ __('Choose Image') }}
                          </label>
                        </div>
                        @error('image')
                          <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">{{ __('Supported formats: JPEG, PNG, JPG, GIF, SVG') }}</small>
                      </div>
                    </div>

                        <div class="col-md-6">
                          <div class="form-group">
                            <label for="first_name">{{ __('First Name') }} *</label>
                            <input type="text" 
                                   class="form-control @error('first_name') is-invalid @enderror" 
                                   id="first_name" 
                                   name="first_name" 
                                   value="{{ old('first_name', $subuser->first_name) }}" 
                                   required>
                            @error('first_name')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="form-group">
                            <label for="last_name">{{ __('Last Name') }} *</label>
                            <input type="text" 
                                   class="form-control @error('last_name') is-invalid @enderror" 
                                   id="last_name" 
                                   name="last_name" 
                                   value="{{ old('last_name', $subuser->last_name) }}" 
                                   required>
                            @error('last_name')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="form-group">
                            <label for="phone_number">{{ __('Phone Number') }}</label>
                            <input type="text" 
                                   class="form-control @error('phone_number') is-invalid @enderror" 
                                   id="phone_number" 
                                   name="phone_number" 
                                   value="{{ old('phone_number', $subuser->phone_number) }}">
                            @error('phone_number')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="form-group">
                            <label for="country">{{ __('Country') }}</label>
                            <input type="text" 
                                   class="form-control @error('country') is-invalid @enderror" 
                                   id="country" 
                                   name="country" 
                                   value="{{ old('country', $subuser->country) }}">
                            @error('country')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="form-group">
                            <label for="state">{{ __('State/Province') }}</label>
                            <input type="text" 
                                   class="form-control @error('state') is-invalid @enderror" 
                                   id="state" 
                                   name="state" 
                                   value="{{ old('state', $subuser->state) }}">
                            @error('state')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="form-group">
                            <label for="city">{{ __('City') }}</label>
                            <input type="text" 
                                   class="form-control @error('city') is-invalid @enderror" 
                                   id="city" 
                                   name="city" 
                                   value="{{ old('city', $subuser->city) }}">
                            @error('city')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>

                        <div class="col-12">
                          <div class="form-group">
                            <label for="address">{{ __('Address') }}</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                      id="address" 
                                      name="address" 
                                      rows="3">{{ old('address', $subuser->address) }}</textarea>
                            @error('address')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>
                      </div>

                      <div class="form-actions">
                        <button type="submit" class="btn btn-primary me-3">
                          <i class="fas fa-save me-2"></i> {{ __('Update Subuser') }}
                        </button>
                        <a href="{{ route('user.subusers.index') }}" class="btn btn-secondary">
                          <i class="fas fa-arrow-left me-2"></i> {{ __('Back to List') }}
                        </a>
                      </div>
                    </form>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

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