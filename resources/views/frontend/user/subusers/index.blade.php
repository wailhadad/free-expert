@extends('frontend.layout')

@php $title = __('Subusers Management'); @endphp

@section('pageHeading')
  {{ $title }}
@endsection


@section('content')

@includeIf('frontend.partials.breadcrumb', ['breadcrumb' => $breadcrumb, 'title' => $title])

  <!-- Grace Period Countdown Alert -->
  @php
    $gracePeriodData = \App\Http\Helpers\GracePeriodHelper::getUserGracePeriodCountdown(auth('web')->id());
  @endphp
  @if($gracePeriodData)
    <div class="container mt-3">
      <div class="alert alert-warning alert-dismissible fade show" role="alert" id="grace-period-alert">
        <div class="d-flex align-items-center">
          <i class="fas fa-clock me-2"></i>
          <div class="flex-grow-1">
            <strong>Membership in Grace Period!</strong>
            <p class="mb-0">Your membership for package "{{ $gracePeriodData['package_title'] }}" is in grace period. 
            Time remaining: <span id="grace-countdown" class="fw-bold text-danger">{{ $gracePeriodData['formatted_time'] }}</span></p>
          </div>
          <a href="{{ route('pricing') }}" class="btn btn-danger btn-sm ms-2">Renew Now</a>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
      </div>
    </div>

    <script>
      // Grace period countdown
      let totalSeconds = {{ $gracePeriodData['total_seconds'] }};
      
      function updateCountdown() {
        if (totalSeconds <= 0) {
          document.getElementById('grace-countdown').innerHTML = 'EXPIRED';
          document.getElementById('grace-period-alert').classList.remove('alert-warning');
          document.getElementById('grace-period-alert').classList.add('alert-danger');
          return;
        }
        
        const days = Math.floor(totalSeconds / 86400);
        const hours = Math.floor((totalSeconds % 86400) / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        
        // Always show all units with leading zeros
        const daysStr = days.toString().padStart(2, '0');
        const hoursStr = hours.toString().padStart(2, '0');
        const minutesStr = minutes.toString().padStart(2, '0');
        const secondsStr = seconds.toString().padStart(2, '0');
        
        const timeString = `${daysStr}d ${hoursStr}h ${minutesStr}m ${secondsStr}s`;
        
        document.getElementById('grace-countdown').innerHTML = timeString;
        totalSeconds--;
      }
      
      updateCountdown();
      setInterval(updateCountdown, 1000);
    </script>
  @endif

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
    
    /* Disabled action buttons */
    .disabled-action {
      opacity: 0.5 !important;
      pointer-events: none !important;
    }
    
    .disabled-action:hover {
      background: none !important;
      color: inherit !important;
      border-color: inherit !important;
    }
    
    .disabled-action.edit-btn {
      border-color: #ffc107 !important;
      color: #ffc107 !important;
    }
    
    .disabled-action.activate-btn {
      border-color: #28a745 !important;
      color: #28a745 !important;
    }
    
    .disabled-action.deactivate-btn {
      border-color: #6c757d !important;
      color: #6c757d !important;
    }
    
    .disabled-action.delete-btn {
      border-color: #dc3545 !important;
      color: #dc3545 !important;
    }
    
    /* Subuser row that exceeds package limit */
    .subuser-exceeds-limit {
      background-color: #fff5f5 !important;
      border-left: 4px solid #dc3545 !important;
      opacity: 0.9;
      transition: all 0.3s ease;
    }
    
    .subuser-exceeds-limit:hover {
      background-color: #ffe6e6 !important;
      cursor: not-allowed;
      transform: translateX(2px);
    }
    
    .subuser-exceeds-limit td {
      position: relative;
    }
    
    /* Warning indicator for exceeds limit rows */
    .subuser-exceeds-limit td:first-child::before {
      content: "⚠️";
      position: absolute;
      left: -30px;
      top: 50%;
      transform: translateY(-50%);
      font-size: 14px;
      z-index: 1;
      animation: pulse 2s infinite;
    }
    
    @keyframes pulse {
      0% { opacity: 1; }
      50% { opacity: 0.5; }
      100% { opacity: 1; }
    }
    
    /* Make the entire row have not-allowed cursor */
    .subuser-exceeds-limit,
    .subuser-exceeds-limit * {
      cursor: not-allowed !important;
    }
    
    /* Exception: allow normal cursor for the status badge and info text */
    .subuser-exceeds-limit .badge,
    .subuser-exceeds-limit small {
      cursor: default !important;
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
                  @php 
                    $totalMaxSubusers = \App\Http\Helpers\UserPermissionHelper::totalMaxSubusers($user->id);
                    $actualSubusersCount = $user->subusers()->count();
                    $isPrioritized = $totalMaxSubusers < $actualSubusersCount;
                  @endphp
                                     @php
                     // Check if user has agency privileges (including grace period)
                     $hasAgencyPrivileges = $user->hasAgencyPrivileges();
                     // Also check if user is in grace period
                     $isInGracePeriod = \App\Http\Helpers\GracePeriodHelper::isUserInGracePeriod($user->id);
                   @endphp
                   @if($hasAgencyPrivileges || $isInGracePeriod)
                     <div class="alert alert-info">
                       <i class="fas fa-users me-2"></i>
                       <strong>{{ __('Subuser Profiles') }}:</strong> 
                       <span class="badge badge-primary">{{ $actualSubusersCount }}</span> 
                       <span class="mx-1 fw-bold" style="font-size: 1.1em;">/</span> 
                       <span class="badge badge-primary">{{ $totalMaxSubusers }}</span><br/><br/>
                       @if($isPrioritized)
                         <span class="text-warning">
                           <i class="fas fa-exclamation-triangle"></i>
                           {{ __('Prioritization active') }}
                         </span>
                         <div class="mt-3 small">
                           <strong>{{ __('Priority Sequence') }}:</strong><br/>
                           <span class="badge badge-light text-dark me-1">1. {{ __('Active orders') }}</span><br/>
                           <span class="badge badge-light text-dark me-1">2. {{ __('Active customer offers') }}</span><br/>
                           <span class="badge badge-light text-dark me-1">3. {{ __('Active customer briefs') }}</span><br/>
                           <span class="badge badge-light text-dark me-1">4. {{ __('Completed orders') }}</span><br/>
                           <span class="badge badge-light text-dark me-1">5. {{ __('Existing Chats') }}</span><br/>
                           <span class="badge badge-light text-dark">6. {{ __('Others') }}</span>
                         </div>
                       @endif
                     </div>
                   @else
                     <div class="alert alert-warning">
                       <strong>{{ __('Agency Privileges Required') }}</strong><br>
                       {{ __('You need to purchase a package with agency privileges to create subusers.') }}
                       <a href="{{ route('user.packages.index') }}" class="btn btn-sm btn-primary ml-2">{{ __('View Packages') }}</a>
                     </div>
                   @endif

                  <div class="card-body">
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
                              @php
                                // Get prioritized subusers to determine which ones are within the limit
                                $prioritizedSubusers = \App\Http\Helpers\UserPermissionHelper::getSubusersWithinLimit($user->id, $totalMaxSubusers);
                                $prioritizedSubuserIds = $prioritizedSubusers->pluck('id')->toArray();
                                $isWithinLimit = in_array($subuser->id, $prioritizedSubuserIds);
                                $canManage = $subuser->status && $isWithinLimit;
                                $disabledClass = $canManage ? '' : 'disabled-action';
                                $disabledAttr = $canManage ? '' : 'disabled';
                                $cursorStyle = $canManage ? '' : 'cursor: not-allowed;';
                                $rowClass = $canManage ? '' : 'subuser-exceeds-limit';
                              @endphp
                              <tr class="{{ $rowClass }}">
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
                                    @if(!$isWithinLimit)
                                      <div class="mt-1">
                                        <small class="text-danger">
                                          <i class="fas fa-exclamation-triangle"></i>
                                          {{ __('Exceeds Limit') }}
                                        </small>
                                      </div>
                                    @endif
                                  @endif
                                </td>
                                <td class="text-center">
                                  <span class="badge badge-primary">{{ $subuser->serviceOrders()->count() }}</span>
                                </td>
                                <td class="text-center">{{ $subuser->created_at->format('M d, Y') }}</td>
                                <td class="text-center">
                                  
                                  <div class="btn-group" role="group">
                                    <a href="{{ $canManage ? route('user.subusers.edit', $subuser->id) : '#' }}" 
                                       class="btn btn-sm btn-outline-warning edit-btn {{ $disabledClass }}" 
                                       title="{{ $canManage ? __('Edit') : __('Cannot edit - exceeds package limit') }}"
                                       style="{{ $cursorStyle }}"
                                       {{ $disabledAttr }}
                                       onclick="{{ $canManage ? '' : 'return false;' }}">
                                      <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <form action="{{ route('user.subusers.toggle_status', $subuser->id) }}" 
                                          method="POST" 
                                          style="display: inline;">
                                      @csrf
                                      <button type="submit" 
                                              class="btn btn-sm {{ $subuser->status ? 'btn-outline-secondary deactivate-btn' : 'btn-outline-success activate-btn' }} {{ $disabledClass }}" 
                                              title="{{ $canManage ? ($subuser->status ? __('Deactivate') : __('Activate')) : __('Cannot modify - exceeds package limit') }}"
                                              style="{{ $cursorStyle }}"
                                              {{ $disabledAttr }}
                                              onclick="{{ $canManage ? '' : 'return false;' }}">
                                        <i class="fas {{ $subuser->status ? 'fa-pause' : 'fa-play' }}"></i>
                                      </button>
                                    </form>
                                    
                                    <button type="button"
                                            class="btn btn-sm btn-outline-danger text-black delete-btn {{ $disabledClass }}" 
                                            title="{{ $canManage ? __('Delete') : __('Cannot delete - exceeds package limit') }}"
                                            style="{{ $cursorStyle }}"
                                            {{ $disabledAttr }}
                                            data-bs-toggle="{{ $canManage ? 'modal' : '' }}" 
                                            data-bs-target="{{ $canManage ? '#deleteModal'.$subuser->id : '' }}"
                                            onclick="{{ $canManage ? '' : 'return false;' }}">
                                      <i class="fas fa-trash"></i>
                                    </button>
                                  </div>
                                  
                                  @if(!$canManage)
                                    <div class="mt-1">
                                      <small class="text-muted">
                                        <i class="fas fa-info-circle"></i>
                                        {{ __('Exceeds package limit') }}
                                      </small>
                                    </div>
                                  @endif
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