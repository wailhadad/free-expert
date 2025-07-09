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
                    <th>{{ __('Image') }}</th>
                    <th>{{ __('Username') }}</th>
                    <th>{{ __('Name') }}</th>
                    <th>{{ __('Status') }}</th>
                    <th>{{ __('Created') }}</th>
                    <th>{{ __('Actions') }}</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($subusers as $subuser)
                  <tr>
                    <td>
                      @if($subuser->image)
                        <img src="{{ asset('assets/img/subusers/' . $subuser->image) }}" alt="{{ $subuser->username }}" class="rounded-circle" width="40" height="40">
                      @else
                        <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
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
                    <td>{{ $subuser->created_at->format('M d, Y') }}</td>
                    <td>
                      <div class="btn-group" role="group">
                        <a href="{{ route('admin.user_management.subuser.details', $subuser->id) }}" class="btn btn-sm btn-info" title="{{ __('Details') }}"><i class="fas fa-eye"></i></a>
                        <a href="{{ route('admin.user_management.subuser.edit', $subuser->id) }}" class="btn btn-sm btn-warning" title="{{ __('Edit') }}"><i class="fas fa-edit"></i></a>
                        <form action="{{ route('admin.user_management.subuser.destroy', $subuser->id) }}" method="POST" style="display:inline;" onsubmit="return confirm('{{ __('Are you sure you want to delete this subuser?') }}')">
                          @csrf
                          <button type="submit" class="btn btn-sm btn-danger" title="{{ __('Delete') }}"><i class="fas fa-trash"></i></button>
                        </form>
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
@endsection
