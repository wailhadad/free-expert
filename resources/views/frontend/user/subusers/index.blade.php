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
                              <th>{{ __('Image') }}</th>
                              <th>{{ __('Username') }}</th>
                              <th>{{ __('Name') }}</th>
                              <th>{{ __('Status') }}</th>
                              <th>{{ __('Orders') }}</th>
                              <th>{{ __('Created') }}</th>
                              <th>{{ __('Actions') }}</th>
                            </tr>
                          </thead>
                          <tbody>
                            @foreach ($subusers as $subuser)
                              <tr>
                                <td>
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
                                <td><strong>{{ $subuser->username }}</strong></td>
                                <td>{{ $subuser->full_name }}</td>
                                <td>
                                  @if($subuser->status)
                                    <span class="badge badge-success text-black">{{ __('Active') }}</span>
                                  @else
                                    <span class="badge badge-danger text-black">{{ __('Inactive') }}</span>
                                  @endif
                                </td>
                                <td>
                                  <span class="badge badge-primary">{{ $subuser->serviceOrders()->count() }}</span>
                                </td>
                                <td>{{ $subuser->created_at->format('M d, Y') }}</td>
                                <td>
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
                                    @if($subuser->serviceOrders()->count() == 0)
                                      <form action="{{ route('user.subusers.destroy', $subuser->id) }}" 
                                            method="POST" 
                                            style="display: inline;"
                                            onsubmit="return confirm('{{ __('Are you sure you want to delete this subuser?') }}')">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger text-black delete-btn" 
                                                title="{{ __('Delete') }}">
                                          <i class="fas fa-trash"></i>
                                        </button>
                                      </form>
                                    @endif
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
@endsection 