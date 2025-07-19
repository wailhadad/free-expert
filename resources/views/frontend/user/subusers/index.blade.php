@extends('frontend.layout')

@php $title = __('Subusers Management'); @endphp

@section('pageHeading')
  {{ $title }}
@endsection


@section('content')

@includeIf('frontend.partials.breadcrumb', ['breadcrumb' => $breadcrumb, 'title' => $title])

  <style>
    .delete-btn {
      background: none !important;
      border-color: #dc3545 !important;
      color: #dc3545 !important;
      transition: background 0.2s, color 0.2s;
    }
    .delete-btn:hover, .delete-btn:focus {
      background: #dc3545 !important;
      color: #fff !important;
    }
    .edit-btn {
      background: none !important;
      border-color: #ffc107 !important;
      color: #ffc107 !important;
      transition: background 0.2s, color 0.2s;
    }
    .edit-btn:hover, .edit-btn:focus {
      background: #ffc107 !important;
      color: #fff !important;
    }
    .activate-btn {
      background: none !important;
      border-color: #28a745 !important;
      color: #28a745 !important;
      transition: background 0.2s, color 0.2s;
    }
    .activate-btn:hover, .activate-btn:focus {
      background: #28a745 !important;
      color: #fff !important;
    }
    .deactivate-btn {
      background: none !important;
      border-color: #6c757d !important;
      color: #6c757d !important;
      transition: background 0.2s, color 0.2s;
    }
    .deactivate-btn:hover, .deactivate-btn:focus {
      background: #6c757d !important;
      color: #fff !important;
    }

  </style>


  <!--====== Start Subusers Management Section ======-->
  <section class="user-dashboard pt-100 pb-60">
    <div class="container">
      <div class="row">
        @includeIf('frontend.user.side-navbar')

        <div class="col-lg-9">
          <div class="row">
            <div class="col-lg-12">
              <div class="user-profile-details mb-40">
                <div class="account-info">
                  <div class="title d-flex justify-content-between align-items-center">
                    <h4>{{ __('Subusers Management') }}</h4>
                    @if($user->canCreateSubuser())
                      <a href="{{ route('user.subusers.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> {{ __('Create Subuser') }}
                      </a>
                    @endif
                  </div>

                  <!-- Agency Privileges Info -->
                  @php $totalMaxSubusers = \App\Http\Helpers\UserPermissionHelper::totalMaxSubusers($user->id); @endphp
                  @if($user->hasAgencyPrivileges())
                    <div class="alert alert-info">
                      <strong>{{ __('Agency Privileges Active') }}</strong><br>
                      {{ __('You can create up to') }} <strong>{{ $totalMaxSubusers }}</strong> {{ __('subusers') }}.<br>
                      {{ __('Currently using') }}: <strong>{{ $user->current_subusers_count }}</strong> / {{ $totalMaxSubusers }}
                    </div>
                  @else
                    <div class="alert alert-warning">
                      <strong>{{ __('Agency Privileges Required') }}</strong><br>
                      {{ __('You need to purchase a package with agency privileges to create subusers.') }}
                      <a href="{{ route('user.packages.index') }}" class="btn btn-sm btn-primary ml-2">{{ __('View Packages') }}</a>
                    </div>
                  @endif

                  <div class="main-info">
                    @if (count($subusers) == 0)
                      <div class="row text-center mt-2">
                        <div class="col">
                          <h4>{{ __('No Subusers Found') . '!' }}</h4>
                          <p>{{ __('You haven\'t created any subusers yet.') }}</p>
                        </div>
                      </div>
                    @else
                      <div class="table-responsive">
                        <table class="table table-striped">
                          <thead>
                            <tr>
                              <th class="text-center">{{ __('Image') }}</th>
                              <th class="text-center">{{ __('Username') }}</th>
                              <th class="text-center">{{ __('Name') }}</th>
                              <th class="text-center">{{ __('Status') }}</th>
                              <th class="text-center">{{ __('Orders') }}</th>
                              <th class="text-center">{{ __('Created') }}</th>
                              <th class="text-center">{{ __('Actions') }}</th>
                            </tr>
                          </thead>
                          <tbody>
                            @foreach ($subusers as $subuser)
                              <tr>
                                <td class="text-center">
                                  @if($subuser->image)
                                    <img src="{{ asset('assets/img/subusers/' . $subuser->image) }}" 
                                         alt="{{ $subuser->username }}" 
                                         class="rounded-circle" 
                                         width="40" height="40">
                                  @else
                                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                         style="width: 40px; height: 40px;">
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
                                <td class="text-center">
                                  <span class="badge badge-primary">{{ $subuser->serviceOrders()->count() }}</span>
                                </td>
                                <td class="text-center">{{ $subuser->created_at->format('M d, Y') }}</td>
                                <td class="text-center">
                                  <div class="btn-group" role="group">
                                    <a href="{{ route('user.subusers.edit', $subuser->id) }}" 
                                       class="btn btn-sm btn-outline-warning edit-btn" 
                                       title="{{ __('Edit') }}">
                                      <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('user.subusers.toggle_status', $subuser->id) }}" 
                                          method="POST" 
                                          style="display: inline;">
                                      @csrf
                                      <button type="submit" 
                                              class="btn btn-sm {{ $subuser->status ? 'btn-outline-secondary deactivate-btn' : 'btn-outline-success activate-btn' }}" 
                                              title="{{ $subuser->status ? __('Deactivate') : __('Activate') }}">
                                        <i class="fas {{ $subuser->status ? 'fa-pause' : 'fa-play' }}"></i>
                                      </button>
                                    </form>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger text-black delete-btn" 
                                            title="{{ __('Delete') }}"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal{{ $subuser->id }}">
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
          </div>
        </div>
      </div>
    </div>
  </section>

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
            <form action="{{ route('user.subusers.destroy', $subuser->id) }}" method="POST" style="display: inline;">
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