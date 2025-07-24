@extends('frontend.layout')

@php $title = __('Dashboard'); @endphp

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

  <!--====== Start Dashboard Section ======-->
  <section class="user-dashboard pt-100 pb-60">
    <div class="container">
      <div class="row">
        @includeIf('frontend.user.side-navbar')

        <div class="col-lg-9">
          <div class="row">
            <div class="col-lg-12">
              <div class="user-profile-details mb-30">
                <div class="account-info">
                  <div class="title">
                    <h4>{{ __('Account Information') }}</h4>
                  </div>

                  <div class="main-info">
                    <ul class="list list-unstyled">

                      @if ($authUser->first_name != null && $authUser->last_name != null)
                      <li>
                        <span>{{ __('Name') . ':' }}</span>
                        <span>{{ $authUser->first_name . ' ' . $authUser->last_name }}</span>
                      </li>
                      @endif

                      @if ($authUser->username != null)
                      <li>
                        <span>{{ __('Username') . ':' }}</span>
                        <span>{{ $authUser->username }}</span>
                      </li>
                      @endif

                      <li>
                        <span>{{ __('Email Address') . ':' }}</span>
                        <span>{{ $authUser->email_address }}</span>
                      </li>

                      @if ($authUser->phone_number != null)
                      <li>
                        <span>{{ __('Phone') . ':' }}</span>
                        <span>{{ $authUser->phone_number }}</span>
                      </li>
                      @endif

                      @if ($authUser->address != null)
                      <li>
                        <span>{{ __('Address') . ':' }}</span>
                        <span>{{ $authUser->address }}</span>
                      </li>
                      @endif

                      @if ($authUser->city != null)
                      <li>
                        <span>{{ __('City') . ':' }}</span>
                        <span>{{ $authUser->city }}</span>
                      </li>
                      @endif

                      @if ($authUser->state != null)
                      <li>
                        <span>{{ __('State') . ':' }}</span>
                        <span>{{ $authUser->state }}</span>
                      </li>
                      @endif

                      @if ($authUser->country != null)
                      <li>
                        <span>{{ __('Country') . ':' }}</span>
                        <span>{{ $authUser->country }}</span>
                      </li>
                      @endif
                    </ul>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="row pb-10">
            @if ($basicInfo->is_service)
              <div class="col-md-4">
                <div class="mb-30">
                  <a href="{{ route('user.service_orders') }}" class="d-block">
                    <div class="card card-box radius-md box-1">
                      <div class="card-info">
                        <h5>{{ __('Service Orders') }}</h5>
                        <p>{{ $numOfServiceOrders }}</p>
                      </div>
                    </div>
                  </a>
                </div>
              </div>

              <div class="col-md-4">
                <div class="mb-30">
                  <a href="{{ route('user.service_wishlist') }}" class="d-block">
                    <div class="card card-box radius-md box-2">
                      <div class="card-info">
                        <h5>{{ __('Wishlisted Services') }}</h5>
                        <p>{{ $numOfWishlistedServices }}</p>
                      </div>
                    </div>
                  </a>
                </div>
              </div>
            @endif
            @if ($basicInfo->support_ticket_status == 1)
              <div class="col-md-4">
                <div class="mb-30">
                  <a href="{{ route('user.support_tickets') }}" class="d-block">
                    <div class="card card-box radius-md box-5">
                      <div class="card-info">
                        <h5>{{ __('Support Tickets') }}</h5>
                        <p>{{ $numOfsupportTicket }}</p>
                      </div>
                    </div>
                  </a>
                </div>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </section>
  <!--====== End Dashboard Section ======-->
@endsection
