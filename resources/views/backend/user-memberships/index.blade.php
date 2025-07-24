@extends('backend.layout')

@section('content')
  <div class="page-header">
    <h4 class="page-title">Customer Subscription Log</h4>
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
        <a href="#">{{ __('Payment') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">Customer Subscription Log</a>
      </li>
    </ul>
  </div>
  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-lg-4">
              <div class="card-title d-inline-block">Customer Subscription Log</div>
            </div>
            <div class="col-lg-3"></div>
            <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0 justify-content-end">
              <form action="{{ url()->current() }}" class="d-inline-block d-flex">
                <input class="form-control mr-2" type="text" name="search"
                  placeholder="{{ __('Search by Transaction ID') }}"
                  value="{{ request()->input('search') ? request()->input('search') : '' }}">
                <input class="form-control" type="text" name="username" placeholder="{{ __('Search by Username') }}"
                  value="{{ request()->input('username') ? request()->input('username') : '' }}">
                <button class="d-none" type="submit"></button>
              </form>
            </div>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-lg-12">
              @if (count($memberships) == 0)
                <h3 class="text-center">{{ __('NO MEMBERSHIP FOUND') }}</h3>
              @else
                <div class="table-responsive">
                  <table class="table table-striped mt-3">
                    <thead>
                      <tr>
                        <th scope="col">{{ __('Transaction Id') }}</th>
                        <th scope="col">{{ __('Username') }}</th>
                        <th scope="col">{{ __('Amount') }}</th>
                        <th scope="col">{{ __('Payment Status') }}</th>
                        <th scope="col">{{ __('Payment Method') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col">{{ __('Receipt') }}</th>
                        <th scope="col">{{ __('Invoice') }}</th>
                        <th scope="col">{{ __('Actions') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($memberships as $membership)
                        <tr>
                          <td>
                            {{ strlen($membership->transaction_id) > 30 ? mb_substr($membership->transaction_id, 0, 30, 'UTF-8') . '...' : $membership->transaction_id }}
                          </td>
                          <td>
                            @if ($membership->user)
                              <a target="_blank" href="#">{{ $membership->user->username }}</a>
                            @endif
                          </td>
                          <td>
                            @if ($membership->price == 0)
                              {{ __('Free') }}
                            @else
                              {{ $membership->currency_symbol }}{{ number_format($membership->price, 2) }}
                            @endif
                          </td>
                          <td>
                            <form method="POST" action="{{ route('admin.user_membership.update_status', $membership->id) }}" style="display:inline;">
                              @csrf
                              <select name="status" class="form-control form-control-sm d-inline w-auto status-select"
                                style="
                                  color: #fff;
                                  font-weight: 600;
                                  min-width: 110px;
                                  text-align: center;
                                  background-color:
                                    {{ $membership->status == 0 ? '#f6c23e' : '' }}
                                    {{ $membership->status == 1 ? '#1cc88a' : '' }}
                                    {{ $membership->status == 2 ? '#e74a3b' : '' }};
                                "
                                onchange="this.form.submit()">
                                <option value="0" style="background-color:#f6c23e; color:#fff;" {{ $membership->status == 0 ? 'selected' : '' }}>{{ __('Pending') }}</option>
                                <option value="1" style="background-color:#1cc88a; color:#fff;" {{ $membership->status == 1 ? 'selected' : '' }}>{{ __('Success') }}</option>
                                <option value="2" style="background-color:#e74a3b; color:#fff;" {{ $membership->status == 2 ? 'selected' : '' }}>{{ __('Rejected') }}</option>
                              </select>
                            </form>
                          </td>
                          <td>{{ $membership->payment_method }}</td>
                          <td>
                            @php
                              $now = \Carbon\Carbon::now();
                              $expireDate = \Carbon\Carbon::parse($membership->expire_date);
                              $isExpired = $expireDate->lt($now);
                            @endphp
                            @if ($membership->status == 1 && !$isExpired)
                              <span class="badge badge-success">{{ __('Activated') }}</span>
                            @elseif ($membership->status == 1 && $isExpired)
                              <span class="badge badge-warning">{{ __('Expired') }}</span>
                            @elseif ($membership->status == 0)
                              <span class="badge badge-info">{{ __('Pending') }}</span>
                            @elseif ($membership->status == 2)
                              <span class="badge badge-secondary">{{ __('Expired') }}</span>
                            @endif
                          </td>
                          <td>
                            @if (!empty($membership->receipt_name))
                              <a class="btn btn-sm btn-info" href="#" data-bs-toggle="modal"
                                data-bs-target="#receiptModal{{ $membership->id }}">{{ __('Show') }}</a>
                            @else
                              -
                            @endif
                          </td>
                          <td>
                            @if (!empty($membership->invoice))
                              <a href="{{ asset('assets/file/invoices/user-memberships/' . $membership->invoice) }}" 
                                 target="_blank" 
                                 class="btn btn-sm btn-success">
                                <i class="fas fa-file-pdf"></i> {{ __('View') }}
                              </a>
                            @else
                              -
                            @endif
                          </td>
                          <td>
                            <a class="btn btn-sm btn-info" href="#" data-bs-toggle="modal"
                              data-bs-target="#detailsModal{{ $membership->id }}">{{ __('Detail') }}</a>
                          </td>
                        </tr>
                        <div class="modal fade" id="receiptModal{{ $membership->id }}" tabindex="-1" role="dialog"
                          aria-labelledby="exampleModalLabel" aria-hidden="true">
                          <div class="modal-dialog" role="document">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">{{ __('Receipt Image') }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                @if (!empty($membership->receipt_name))
                                  <img src="{{ asset('assets/front/img/user-packages/receipt/' . $membership->receipt_name) }}"
                                    alt="Receipt" width="100%">
                                @endif
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Close') }}
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                        <div class="modal fade" id="detailsModal{{ $membership->id }}" tabindex="-1" role="dialog"
                          aria-labelledby="exampleModalLabel" aria-hidden="true">
                          <div class="modal-dialog" role="document">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title" id="exampleModalLabel">{{ __('User Details') }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                              </div>
                              <div class="modal-body">
                                <label>{{ __('Username') }}</label>
                                <p>{{ $membership->user->username ?? '-' }}</p>
                                <label class="font-weight-bold text-primary">{{ __('Email') }}</label>
                                <p class="mb-2" style="font-size:1.1em; font-weight:600;">
                                  {{ $membership->user && ($membership->user->email ?? $membership->user->email_address ?? null) ? ($membership->user->email ?: $membership->user->email_address) : '-' }}
                                </p>
                                <label>{{ __('Package') }}</label>
                                <p>{{ $membership->package->title ?? '-' }}</p>
                                <label>{{ __('Start Date') }}</label>
                                <p>{{ $membership->start_date }}</p>
                                <label>{{ __('Expire Date') }}</label>
                                <p>{{ $membership->expire_date }}</p>
                                <label>{{ __('Payment Method') }}</label>
                                <p>{{ $membership->payment_method }}</p>
                                <label>{{ __('Price') }}</label>
                                <p>{{ $membership->currency_symbol }}{{ number_format($membership->price, 2) }}</p>

                                @if($membership->status == 1 && !empty($membership->invoice))
                                  <div class="mt-3">
                                    <a href="{{ asset('assets/file/invoices/user-memberships/' . $membership->invoice) }}" 
                                       target="_blank" 
                                       class="btn btn-outline-success">
                                      <i class="fas fa-file-pdf"></i> {{ __('Download Invoice') }}
                                    </a>
                                  </div>
                                @endif
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                  {{ __('Close') }}
                                </button>
                              </div>
                            </div>
                          </div>
                        </div>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            </div>
          </div>
        </div>
        <div class="card-footer">
          <div class="row">
            <div class="d-inline-block mx-auto">
              {{ $memberships->appends(['search' => request()->input('search'), 'username' => request()->input('username')])->links() }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection 