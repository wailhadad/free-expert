@extends('backend.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Registered Customers') }}</h4>
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
        <a href="#">{{ __('Registered Customers') }}</a>
      </li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-lg-4">
              <div class="card-title">{{ __('All Customers') }}</div>
            </div>

            <div class="col-lg-6 offset-lg-2">
              <button type="button" class="btn btn-primary float-right   btn-sm ml-2 mt-1  " data-toggle="modal"
                data-target="#addUserModal"><i class="fas fa-plus"></i>
                {{ __('Add Customer') }}</button>

              <button class="btn btn-danger btn-sm float-right d-none bulk-delete mr-2 ml-3 mt-1"
                data-href="{{ route('admin.user_management.bulk_delete_user') }}">
                <i class="flaticon-interface-5"></i> {{ __('Delete') }}
              </button>
              <form class="float-right" action="{{ route('admin.user_management.registered_users') }}" method="GET">
                <input name="info" type="text" class="form-control min_width_230" placeholder="Search By Username or Email ID"
                  value="{{ !empty(request()->input('info')) ? request()->input('info') : '' }}" >
              </form>



            </div>
          </div>
        </div>

        <div class="card-body">
          <div class="row">
            <div class="col-lg-12">
              @if (count($users) == 0)
                <h3 class="text-center mt-2">{{ __('NO CUSTOMER FOUND') . '!' }}</h3>
              @else
                <div class="table-responsive">
                  <table class="table table-striped mt-3">
                    <thead>
                      <tr>
                        <th scope="col">
                          <input type="checkbox" class="bulk-check" data-val="all">
                        </th>
                        <th scope="col">{{ __('Username') }}</th>
                        <th scope="col">{{ __('Email ID') }}</th>
                        <th scope="col">{{ __('Email Status') }}</th>
                        <th scope="col">{{ __('Phone') }}</th>
                        <th scope="col">{{ __('Account Status') }}</th>
                        <th scope="col">{{ __('Actions') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($users as $user)
                        <tr>
                          <td>
                            <input type="checkbox" class="bulk-check" data-val="{{ $user->id }}">
                          </td>
                          <td>
                            @if (empty($user->username))
                              {{ $user->provider_id }}
                            @else
                              {{ $user->username }}
                            @endif
                          </td>
                          <td>{{ $user->email_address }}</td>
                          <td>
                            <form id="emailStatusForm-{{ $user->id }}" class="d-inline-block"
                              action="{{ route('admin.user_management.user.update_email_status', ['id' => $user->id]) }}"
                              method="post">
                              @csrf
                              <select
                                class="form-control form-control-sm {{ !is_null($user->email_verified_at) ? 'bg-success' : 'bg-danger' }}"
                                name="email_status"
                                onchange="document.getElementById('emailStatusForm-{{ $user->id }}').submit()">
                                <option value="verified" {{ !is_null($user->email_verified_at) ? 'selected' : '' }}>
                                  {{ __('Verified') }}
                                </option>
                                <option value="not verified" {{ is_null($user->email_verified_at) ? 'selected' : '' }}>
                                  {{ __('Not Verified') }}
                                </option>
                              </select>
                            </form>
                          </td>
                          <td>{{ empty($user->phone_number) ? '-' : $user->phone_number }}</td>
                          <td>
                            <form id="accountStatusForm-{{ $user->id }}" class="d-inline-block"
                              action="{{ route('admin.user_management.user.update_account_status', ['id' => $user->id]) }}"
                              method="post">
                              @csrf
                              <select
                                class="form-control form-control-sm {{ $user->status == 1 ? 'bg-success' : 'bg-danger' }}"
                                name="account_status"
                                onchange="document.getElementById('accountStatusForm-{{ $user->id }}').submit()">
                                <option value="1" {{ $user->status == 1 ? 'selected' : '' }}>
                                  {{ __('Active') }}
                                </option>
                                <option value="2" {{ $user->status == 0 ? 'selected' : '' }}>
                                  {{ __('Deactive') }}
                                </option>
                              </select>
                            </form>
                          </td>
                          <td>
                            <div class="dropdown">
                              <button class="btn btn-secondary btn-sm dropdown-toggle" type="button"
                                id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ __('Select') }}
                              </button>

                              <div class="dropdown-menu order-actions-dropdown" aria-labelledby="dropdownMenuButton">
                                <a href="{{ route('admin.user_management.user.details', ['id' => $user->id]) }}"
                                  class="dropdown-item">
                                  {{ __('Details') }}
                                </a>
                                <a href="{{ route('admin.user_management.user.edit', ['id' => $user->id]) }}"
                                  class="dropdown-item">
                                  {{ __('Edit') }}
                                </a>

                                <a href="{{ route('admin.user_management.user.change_password', ['id' => $user->id]) }}"
                                  class="dropdown-item">
                                  {{ __('Change Password') }}
                                </a>

                                <form class=" d-block" target="_blank"
                                  action="{{ route('admin.user_management.user.secretLogin', ['id' => $user->id]) }}"
                                  method="post">
                                  @csrf
                                  <button type="submit" class="dropdown-item">
                                    {{ __('Secret Login') }}
                                  </button>
                                </form>

                                <form class="deleteForm d-block"
                                  action="{{ route('admin.user_management.user.delete', ['id' => $user->id]) }}"
                                  method="post">
                                  @csrf
                                  <button type="submit" class="deleteBtn">
                                    {{ __('Delete') }}
                                  </button>
                                </form>
                              </div>
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

        <div class="card-footer">
          <div class="mt-3 text-center">
            <div class="d-inline-block mx-auto">
              {{ $users->appends(['info' => request()->input('info')])->links() }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal -->
  <div class="modal fade" id="addUserModal" tabindex="-1" role="dialog" aria-labelledby="addUserModalTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLongTitle">{{ __('Add Customer') }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <form action="{{ route('admin.user_management.register_user') }}" method="POST" id="ajaxForm">
            @csrf

            <div class="form-group">
              <label for="">{{ __('Username') }} *</label>
              <input class="form-control" type="text" name="username">
              <p id="err_username" class="text-danger mb-0 em"></p>
            </div>
            <div class="form-group">
              <label for="">{{ __('Email') }} *</label>
              <input class="form-control" type="email" name="email_address">
              <p id="err_email_address" class="text-danger mb-0 em"></p>
            </div>
            <div class="form-group">
              <label for="">{{ __('Password') }} *</label>
              <input class="form-control" type="password" name="password">
              <p id="err_password" class="text-danger mb-0 em"></p>
            </div>
            <div class="form-group">
              <label for="">{{ __('Confirm Password') }} *</label>
              <input class="form-control" type="password" name="password_confirmation">
              <p id="err_password_confirmation" class="text-danger mb-0 em"></p>
            </div>

          </form>
        </div>
        <div class="modal-footer text-center">
          <button id="submitBtn" type="button" class="btn btn-primary">{{ __('Add Customer') }}</button>
        </div>
      </div>
    </div>
  </div>
@endsection
