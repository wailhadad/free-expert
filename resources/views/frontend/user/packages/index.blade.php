@extends('frontend.layout')

@php $title = __('Agency Packages'); @endphp

@section('pageHeading')
  {{ $title }}
@endsection

@section('content')
  @includeIf('frontend.partials.breadcrumb', ['breadcrumb' => $breadcrumb, 'title' => $title])

  <style>
    .package-card {
      height: 500px;
      display: flex;
      flex-direction: column;
    }
    
    .package-card .card-body {
      display: flex;
      flex-direction: column;
      height: 100%;
    }
    
    .package-card .card-body .mt-auto {
      margin-top: auto !important;
    }
  </style>

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

  <!--====== Start Agency Packages Section ======-->
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
                    <h4>{{ __('Agency Packages') }}</h4>
                    <a href="{{ route('user.packages.subscription_log') }}" class="btn btn-outline-primary btn-sm">
                      <i class="fas fa-history"></i> {{ __('Subscription Log') }}
                    </a>
                  </div>

                  <!-- Current Package Info -->
                  @if($currentPackage)
                    <div class="alert alert-success">
                      <strong>{{ __('Current Package') }}: {{ $currentPackage->title }}</strong><br>
                      {{ __('Max Subusers') }}: <strong>{{ $currentPackage->max_subusers }}</strong><br>
                      {{ __('Expires') }}: <strong>{{ $currentMembership->expire_date }}</strong><br>
                      <a href="{{ route('user.packages.subscription_log') }}" class="btn btn-sm btn-outline-primary mt-2">
                        {{ __('View Subscription History') }}
                      </a>
                    </div>
                  @else
                    <div class="alert alert-info">
                      <strong>{{ __('No Active Package') }}</strong><br>
                      {{ __('Purchase a package to unlock agency privileges and create subusers.') }}
                    </div>
                  @endif

                  <div class="main-info">
                    @if (count($packages) == 0)
                      <div class="row text-center mt-2">
                        <div class="col">
                          <h4>{{ __('No Packages Available') . '!' }}</h4>
                          <p>{{ __('No agency packages are currently available.') }}</p>
                        </div>
                      </div>
                    @else
                      <div class="row">
                        @foreach ($packages as $package)
                          <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 package-card {{ $package->recommended ? 'border-primary' : '' }}">
                              @if($package->recommended)
                                <div class="card-header bg-primary text-white text-center">
                                  <strong>{{ __('RECOMMENDED') }}</strong>
                                </div>
                              @endif
                              
                              <div class="card-body d-flex flex-column">
                                <h5 class="card-title text-center">{{ $package->title }}</h5>
                                
                                <div class="text-center mb-3">
                                  <span class="h3 text-primary">
                                    {{ $bs->base_currency_symbol }}{{ $package->price }}
                                  </span>
                                  <small class="text-muted">/ {{ ucfirst($package->term) }}</small>
                                  @if($package->term === 'lifetime')
                                    <div class="mt-1">
                                      <span class="badge badge-success">{{ __('One-time payment') }}</span>
                                    </div>
                                  @elseif($package->term === 'yearly')
                                    <div class="mt-1">
                                      <span class="badge badge-info">{{ __('Billed annually') }}</span>
                                    </div>
                                  @elseif($package->term === 'monthly')
                                    <div class="mt-1">
                                      <span class="badge badge-warning">{{ __('Billed monthly') }}</span>
                                    </div>
                                  @endif
                                </div>

                                <ul class="list-unstyled mb-4">
                                  <li class="mb-2">
                                    <i class="fas fa-users text-success"></i>
                                    <strong>{{ __('Max Subusers') }}:</strong> {{ $package->max_subusers }}
                                  </li>
                                  @if($package->custom_features)
                                    <li class="mb-2">
                                      <i class="fas fa-star text-warning"></i>
                                      <strong>{{ __('Features') }}:</strong>
                                      <div class="mt-1">
                                        {!! $package->custom_features !!}
                                      </div>
                                    </li>
                                  @endif
                                </ul>

                                <div class="mt-auto">
                                  @if($currentPackage && $currentPackage->id == $package->id)
                                    <button class="btn btn-success btn-block" disabled>
                                      <i class="fas fa-check"></i> {{ __('Current Package') }}
                                    </button>
                                  @else
                                    <a href="{{ route('user.packages.checkout', $package->id) }}" 
                                       class="btn btn-primary btn-block">
                                      <i class="fas fa-shopping-cart"></i> {{ __('Purchase Package') }}
                                    </a>
                                  @endif
                                </div>
                              </div>
                            </div>
                          </div>
                        @endforeach
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