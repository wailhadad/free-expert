@extends('backend.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Customer Details') }}</h4>
    <ul class="breadcrumbs">
      <li class="nav-home">
        <a href="{{ route('admin.dashboard') }}">
          <i class="flaticon-home"></i>
        </a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Customers Management') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="{{ route('admin.user_management.registered_users') }}">{{ __('Registered Customers') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Customer Details') }}</a>
      </li>
    </ul>
    <a href="{{ route('admin.user_management.registered_users') }}" class="btn btn-primary ml-auto">{{ __('Back') }}</a>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="row">
        <div class="col-md-3">
          <div class="card">
            <div class="card-header">
              <div class="h4 card-title">{{ __('Profile Picture') }}</div>
            </div>

            <div class="card-body text-center py-4">
              <img
                src="{{ empty($userInfo->image) ? asset('assets/img/profile.jpg') : asset('assets/img/users/' . $userInfo->image) }}"
                alt="image" width="150">
            </div>
          </div>
        </div>

        <div class="col-md-9">
          <div class="card">
            <div class="card-header">
              <div class="card-title">{{ __('Customer Information') }}</div>
            </div>

            <div class="card-body">
              <div class="user-information">
                <div class="row mb-2">
                  <div class="col-lg-2">
                    <strong>{{ __('Name') . ' :' }}</strong>
                  </div>

                  <div class="col-lg-10">
                    {{ $userInfo->first_name . ' ' . $userInfo->last_name }}
                  </div>
                </div>

                <div class="row mb-2">
                  <div class="col-lg-2">
                    <strong>{{ __('Username') . ' :' }}</strong>
                  </div>

                  <div class="col-lg-10">
                    {{ $userInfo->username }}
                  </div>
                </div>

                <div class="row mb-2">
                  <div class="col-lg-2">
                    <strong>{{ __('Email') . ' :' }}</strong>
                  </div>

                  <div class="col-lg-10">
                    {{ $userInfo->email_address }}
                  </div>
                </div>

                <div class="row mb-2">
                  <div class="col-lg-2">
                    <strong>{{ __('Phone') . ' :' }}</strong>
                  </div>

                  <div class="col-lg-10">
                    {{ $userInfo->phone_number }}
                  </div>
                </div>

                <div class="row mb-2">
                  <div class="col-lg-2">
                    <strong>{{ __('Address') . ' :' }}</strong>
                  </div>

                  <div class="col-lg-10">
                    {{ $userInfo->address }}
                  </div>
                </div>

                <div class="row mb-2">
                  <div class="col-lg-2">
                    <strong>{{ __('City') . ' :' }}</strong>
                  </div>

                  <div class="col-lg-10">
                    {{ $userInfo->city }}
                  </div>
                </div>

                <div class="row mb-2">
                  <div class="col-lg-2">
                    <strong>{{ __('State') . ' :' }}</strong>
                  </div>

                  <div class="col-lg-10">
                    {{ $userInfo->state }}
                  </div>
                </div>

                <div class="row">
                  <div class="col-lg-2">
                    <strong>{{ __('Country') . ' :' }}</strong>
                  </div>

                  <div class="col-lg-10">
                    {{ $userInfo->country }}
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  {{-- Subusers Section --}}
  <div class="row mt-4">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="card-title">{{ __('Subusers') }}</div>
        </div>
        <div class="card-body">
          @if (count($subusers) == 0)
            <div class="text-center text-muted py-4">{{ __('No subusers found for this user.') }}</div>
          @else
            <div class="table-responsive">
              <table class="table table-striped">
                <thead>
                  <tr>
                    <th class="text-center">{{ __('Image') }}</th>
                    <th class="text-center">{{ __('Username') }}</th>
                    <th class="text-center">{{ __('Name') }}</th>
                    <th class="text-center">{{ __('Status') }}</th>
                    <th class="text-center">{{ __('Created') }}</th>
                    <th class="text-center">{{ __('Actions') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($subusers as $subuser)
                  <tr>
                    <td class="text-center">
                      @if($subuser->image)
                        <img src="{{ asset('assets/img/subusers/' . $subuser->image) }}" alt="{{ $subuser->username }}" class="rounded-circle" width="40" height="40">
                      @else
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                          <i class="fas fa-user text-black"></i>
                        </div>
                      @endif
                    </td>
                    <td class="text-center"><strong>{{ $subuser->username }}</strong></td>
                    <td class="text-center">{{ $subuser->full_name }}</td>
                    <td class="text-center">
                      @if($subuser->status)
                        <span class="badge badge-success text-black">{{ __('Active') }}</span>
                      @else
                        <span class="badge badge-danger text-black">{{ __('Inactive') }}</span>
                      @endif
                    </td>
                    <td class="text-center">{{ $subuser->created_at->format('M d, Y') }}</td>
                    <td class="text-center">
                      <div class="btn-group" role="group">
                        <a href="{{ route('admin.user_management.subuser.details', $subuser->id) }}" class="btn btn-sm btn-info" title="{{ __('Details') }}"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('admin.user_management.subuser.edit', $subuser->id) }}" class="btn btn-sm btn-warning" title="{{ __('Edit') }}"><i class="fas fa-edit"></i></a>
                        <button type="button" class="btn btn-sm btn-danger" title="{{ __('Delete') }}" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $subuser->id }}">
                          <i class="fas fa-trash"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Delete Confirmation Modals -->
  @foreach ($subusers as $subuser)
    <div class="modal fade" id="deleteModal{{ $subuser->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel{{ $subuser->id }}" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
          <div class="modal-header bg-danger text-white border-0">
            <div class="d-flex align-items-center">
              <div class="modal-icon me-3">
                <i class="fas fa-exclamation-triangle fa-2x"></i>
              </div>
              <div>
                <h5 class="modal-title mb-0" id="deleteModalLabel{{ $subuser->id }}">
                  {{ __('Delete Subuser') }}
                </h5>
                <small class="opacity-75">{{ __('This action cannot be undone') }}</small>
              </div>
            </div>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <div class="text-center mb-4">
              <div class="delete-avatar mb-3">
                @if($subuser->image)
                  <img src="{{ asset('assets/img/subusers/' . $subuser->image) }}" 
                       alt="{{ $subuser->username }}" 
                       class="rounded-circle border border-3 border-danger" 
                       width="80" height="80">
                @else
                  <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center border border-3 border-danger" 
                       style="width: 80px; height: 80px; margin: 0 auto;">
                    <i class="fas fa-user text-white fa-2x"></i>
                  </div>
                @endif
              </div>
              <h6 class="text-dark mb-2">{{ __('Are you sure you want to delete this subuser?') }}</h6>
              <p class="text-muted mb-0">
                <strong>"{{ $subuser->username }}"</strong> 
                ({{ $subuser->first_name }} {{ $subuser->last_name }})
              </p>
            </div>
            
            @if($subuser->serviceOrders()->count() > 0)
              <div class="alert alert-warning border-0" style="background-color: #fff3cd; border-left: 4px solid #ffc107 !important;">
                <div class="d-flex align-items-start">
                  <i class="fas fa-exclamation-triangle text-warning me-3 mt-1"></i>
                  <div>
                    <strong class="text-warning">{{ __('Warning!') }}</strong>
                    <p class="mb-0 mt-1">{{ __('This subuser has') }} 
                      <span class="badge bg-warning text-dark">{{ $subuser->serviceOrders()->count() }}</span> 
                      {{ __('orders that will also be deleted.') }}
                    </p>
                  </div>
                </div>
              </div>
            @endif
            
            <div class="delete-summary bg-light rounded p-3">
              <h6 class="text-dark mb-3">{{ __('What will be deleted:') }}</h6>
              <ul class="list-unstyled mb-0">
                <li class="d-flex align-items-center mb-2">
                  <i class="fas fa-user text-danger me-2"></i>
                  <span>{{ __('Subuser profile and data') }}</span>
                </li>
                <li class="d-flex align-items-center mb-2">
                  <i class="fas fa-shopping-cart text-danger me-2"></i>
                  <span>{{ $subuser->serviceOrders()->count() }} {{ __('service orders') }}</span>
                </li>
                <li class="d-flex align-items-center mb-2">
                  <i class="fas fa-comments text-danger me-2"></i>
                  <span>{{ \App\Models\DirectChat::where('subuser_id', $subuser->id)->count() }} {{ __('chats') }}</span>
                </li>
                <li class="d-flex align-items-center">
                  <i class="fas fa-file-alt text-danger me-2"></i>
                  <span>{{ __('All related files and documents') }}</span>
                </li>
              </ul>
            </div>
          </div>
          <div class="modal-footer border-0 bg-light p-4">
            <button type="button" class="btn btn-light btn-lg px-4" data-bs-dismiss="modal">
              <i class="fas fa-times me-2"></i>{{ __('Cancel') }}
            </button>
            <form action="{{ route('admin.user_management.subuser.destroy', $subuser->id) }}" method="POST" style="display: inline;">
              @csrf
              @method('DELETE')
              <button type="submit" class="btn btn-danger btn-lg px-4">
                <i class="fas fa-trash me-2"></i>{{ __('Delete Subuser') }}
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  @endforeach

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Initialize all modals properly
      const modals = document.querySelectorAll('.modal');
      modals.forEach(function(modalElement) {
        const modal = new bootstrap.Modal(modalElement, {
          backdrop: 'static',
          keyboard: false
        });
        
        // Ensure proper cleanup when modal is hidden
        modalElement.addEventListener('hidden.bs.modal', function() {
          // Remove any loading states
          const submitBtn = this.querySelector('button[type="submit"]');
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-trash me-2"></i>{{ __("Delete Subuser") }}';
          }
        });
      });
      
      // Handle form submission with loading state
      document.querySelectorAll('form[action*="destroy"]').forEach(function(form) {
        form.addEventListener('submit', function(e) {
          const submitBtn = this.querySelector('button[type="submit"]');
          if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>{{ __("Deleting...") }}';
          }
        });
      });
      
      // Ensure cancel buttons work properly
      document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(function(button) {
        button.addEventListener('click', function(e) {
          e.preventDefault();
          const modalElement = this.closest('.modal');
          if (modalElement) {
            const modal = bootstrap.Modal.getInstance(modalElement);
            if (modal) {
              modal.hide();
            }
          }
        });
      });
    });
  </script>
@endsection
