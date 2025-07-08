@extends('frontend.layout')

@section('pageHeading')
  {{ __('Subscription Log') }}
@endsection

@section('content')
  @includeIf('frontend.partials.breadcrumb', ['breadcrumb' => $breadcrumb, 'title' => __('Subscription Log')])
  <section class="user-dashboard pt-100 pb-60">
    <div class="container">
      <div class="row">
        @includeIf('frontend.user.side-navbar')
        <div class="col-lg-9">
          <div class="user-profile-details mb-40">
            <div class="account-info">
              <div class="title mb-2 d-flex align-items-center gap-2">
                <h4 class="mb-0">{{ __('Subscription Log') }}</h4>
                <span class="text-muted ml-2" style="font-size: 1rem;">{{ __('Your package purchase & renewal history') }}</span>
              </div>
              <div class="main-info">
                @if (count($memberships) == 0)
                  <div class="d-flex flex-column align-items-center justify-content-center py-5">
                    <i class="fas fa-history fa-3x text-secondary mb-3"></i>
                    <div class="alert alert-info text-center mb-0" style="max-width: 400px;">
                      {{ __('No subscription history found.') }}
                    </div>
                  </div>
                @else
                  <div class="card shadow-sm rounded-lg border-0">
                    <div class="card-body p-0">
                      <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                          <thead class="thead-light">
                            <tr>
                              <th><i class="fas fa-box-open mr-1 text-primary"></i> {{ __('Package') }}</th>
                              <th><i class="fas fa-dollar-sign mr-1 text-success"></i> {{ __('Price') }}</th>
                              <th><i class="fas fa-signal mr-1 text-info"></i> {{ __('Status') }}</th>
                              <th><i class="far fa-calendar-check mr-1 text-secondary"></i> {{ __('Start Date') }}</th>
                              <th><i class="far fa-calendar-times mr-1 text-secondary"></i> {{ __('Expire Date') }}</th>
                              <th><i class="fas fa-file-invoice mr-1 text-danger"></i> {{ __('Invoice') }}</th>
                            </tr>
                          </thead>
                          <tbody>
                            @foreach ($memberships as $membership)
                              <tr>
                                <td>
                                  <span class="d-flex align-items-center gap-2">
                                    <i class="fas fa-box text-primary"></i>
                                    <span>{{ $membership->package ? $membership->package->title : '-' }}</span>
                                  </span>
                                </td>
                                <td><span class="font-weight-bold">{{ $membership->currency_symbol }}{{ $membership->price }}</span></td>
                                <td>
                                  @if ($membership->status == 1)
                                    <span class="badge badge-success text-white px-3 py-2"><i class="fas fa-check-circle mr-1"></i> {{ __('Active') }}</span>
                                  @elseif ($membership->status == 0)
                                    <span class="badge badge-warning text-dark px-3 py-2"><i class="fas fa-hourglass-half mr-1"></i> {{ __('Pending') }}</span>
                                  @else
                                    <span class="badge badge-danger text-white px-3 py-2"><i class="fas fa-times-circle mr-1"></i> {{ __('Expired') }}</span>
                                  @endif
                                </td>
                                <td><i class="far fa-calendar-alt mr-1 text-secondary"></i> {{ $membership->start_date }}</td>
                                <td><i class="far fa-calendar-alt mr-1 text-secondary"></i> {{ $membership->expire_date }}</td>
                                <td>
                                  @if($membership->invoice)
                                    <a href="{{ asset('assets/file/invoices/user-memberships/' . $membership->invoice) }}" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill d-flex align-items-center gap-1">
                                      <i class="fas fa-file-pdf"></i> <span>{{ __('Invoice') }}</span>
                                    </a>
                                  @else
                                    <span class="text-muted">{{ __('N/A') }}</span>
                                  @endif
                                </td>
                              </tr>
                            @endforeach
                          </tbody>
                        </table>
                      </div>
                    </div>
                  </div>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection 