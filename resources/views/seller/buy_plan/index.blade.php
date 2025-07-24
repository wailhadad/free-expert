@extends('seller.layout')
@php
  Config::set('app.timezone', App\Models\BasicSettings\Basic::first()->timezone);
@endphp
@section('styles')
  <link rel="stylesheet" href="{{ asset('assets/css/buy_plan.css') }}">
  <style>
    .card-pricing2 {
      position: relative;
    }
    
    .card-pricing2 .pricing-header {
      position: relative;
    }
    
    /* Badge positioning - top right corner with stacking */
    .card-pricing2 .badge-container {
      position: absolute;
      top: 16px;
      right: 16px;
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 8px;
      z-index: 3;
    }
    
    .card-pricing2 .badge {
      font-size: 0.75rem;
      padding: 4px 10px;
      border-radius: 12px;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 4px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    
    .card-pricing2 .badge.badge-info {
      background: linear-gradient(90deg, #17a2b8 0%, #138496 100%);
      color: white;
    }
    
    .card-pricing2 .badge.badge-danger {
      background: linear-gradient(90deg, #dc3545 0%, #c82333 100%);
      color: white;
    }
    
    .card-pricing2 .badge.badge-warning {
      background: linear-gradient(90deg, #ffc107 0%, #e0a800 100%);
      color: #212529;
    }
    
    /* Ensure title doesn't overlap with badges */
    .card-pricing2 .pricing-header h3.fw-bold {
      padding-right: 120px;
      margin-bottom: 0;
    }
  </style>
@endsection

@php
  $seller = Auth::guard('seller')->user();
  $package = \App\Http\Helpers\SellerPermissionHelper::currentPackagePermission($seller->id);
@endphp
@section('content')
  <!-- Grace Period Countdown Alert -->
  @php
    $gracePeriodData = \App\Http\Helpers\GracePeriodHelper::getSellerGracePeriodCountdown(Auth::guard('seller')->id());
  @endphp
  @if($gracePeriodData)
    <div class="alert alert-warning alert-dismissible fade show" role="alert" id="grace-period-alert">
      <div class="d-flex align-items-center">
        <i class="fas fa-clock me-2"></i>
        <div class="flex-grow-1">
          <strong>Insufficient Balance for Auto-Renewal!</strong>
          <p class="mb-0">Your membership for package "{{ $gracePeriodData['package_title'] }}" is in grace period. 
          Current balance: <span class="fw-bold text-warning">${{ number_format($gracePeriodData['current_balance'], 2) }}</span> | 
          Required: <span class="fw-bold text-danger">${{ number_format($gracePeriodData['package_price'], 2) }}</span> | 
          Shortfall: <span class="fw-bold text-danger">${{ number_format($gracePeriodData['balance_shortfall'], 2) }}</span><br>
          Time remaining: <span id="grace-countdown" class="fw-bold text-danger">{{ $gracePeriodData['formatted_time'] }}</span></p>
        </div>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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

  @if (is_null($package))
    @php
      $pendingMemb = \App\Models\Membership::query()
          ->where([['seller_id', '=', $seller->id], ['status', 0]])
          ->whereYear('start_date', '<>', '9999')
          ->orderBy('id', 'DESC')
          ->first();
      $pendingPackage = isset($pendingMemb) ? \App\Models\Package::query()->findOrFail($pendingMemb->package_id) : null;
    @endphp

    @if ($pendingPackage)
      <div class="alert alert-warning text-dark">
        {{ __('You have requested a package which needs an action (Approval / Rejection) by Admin. You will be notified via mail once an action is taken.') }}
      </div>
      <div class="alert alert-warning text-dark">
        <strong>{{ __('Pending Package') . ':' }} </strong> {{ $pendingPackage->title }}
        <span class="badge badge-secondary">{{ $pendingPackage->term }}</span>
        <span class="badge badge-warning">{{ __('Decision Pending') }}</span>
      </div>
    @elseif (!$gracePeriodData)
      <div class="alert alert-warning text-dark">
        {{ __('Your membership is expired. Please purchase a new package / extend the current package.') }}
      </div>
    @endif
  @else
    <div class="row justify-content-center align-items-center mb-1">
      <div class="col-12">
        <div class="alert border-left border-primary text-dark">
          @if ($package_count >= 2 && $next_membership)
            @if ($next_membership->status == 0)
              <strong
                class="text-danger">{{ __('You have requested a package which needs an action (Approval / Rejection) by Admin. You will be notified via mail once an action is taken.') }}</strong><br>
            @elseif ($next_membership->status == 1)
              <strong
                class="text-danger">{{ __('You have another package to activate after the current package expires. You cannot purchase / extend any package, until the next package is activated') }}</strong><br>
            @endif
          @endif

          <strong>{{ __('Current Package') . ':' }} </strong>
          @if($current_package)
              {{ $current_package->title }}
          @else
              <span class="text-danger">{{ __('No active package') }}</span>
          @endif
          @if($current_package)
              <span class="badge badge-secondary">{{ $current_package->term }}</span>
          @endif
          @if ($current_membership && $current_membership->is_trial == 1)
            ({{ __('Expire Date') . ':' }}
            {{ $current_membership->expire_date }})
            <span class="badge badge-primary">{{ __('Trial') }}</span>
          @else
            ({{ __('Expire Date') . ':' }}
            {{ $current_package ? \Carbon\Carbon::parse($current_membership->expire_date)->format('M-d-Y') : 'N/A' }})
          @endif

          @if ($package_count >= 2 && $next_package)
            <div>
              <strong>{{ __('Next Package To Activate') . ':' }} </strong> {{ $next_package->title }} <span
                class="badge badge-secondary">{{ $next_package->term }}</span>
              @if ($current_package && $current_package->term != 'lifetime' && $current_membership && $current_membership->is_trial != 1)
                (
                {{ __('Activation Date') . ':' }}
                {{ Carbon\Carbon::parse($next_membership->start_date)->format('M-d-Y') }},
                {{ __('Expire Date') . ':' }}
                {{ $next_package->term === 'lifetime' ? 'Lifetime' : Carbon\Carbon::parse($next_membership->expire_date)->format('M-d-Y') }})
              @endif
              @if ($next_membership->status == 0)
                <span class="badge badge-warning">{{ __('Decision Pending') }}</span>
              @endif
            </div>
          @endif
        </div>
      </div>
    </div>
  @endif
  <div class="row mb-5 justify-content-center">
    @foreach ($packages as $key => $package)
      <div class="col-md-3 pr-md-0 mb-5">
        <div class="card-pricing2 @if (isset($current_package->id) && $current_package->id === $package->id) card-success @else card-primary @endif">
          <div class="pricing-header">
            <h3 class="fw-bold d-inline-block">
              {{ $package->title }}
            </h3>
            <div class="badge-container">
              @if ($package->recommended == 1)
                <h3 class="badge badge-info">
                  <i class="fas fa-star"></i> {{ __('Recommended') }}
                </h3>
              @endif
              @if (isset($current_package->id) && $current_package->id === $package->id)
                <h3 class="badge badge-danger">
                  <i class="fas fa-check-circle"></i> {{ __('Current') }}
                </h3>
              @endif
              @if ($package_count >= 2)
                @if ($next_package)
                  @if ($next_package->id == $package->id)
                    <h3 class="badge badge-warning">
                      <i class="fas fa-clock"></i> {{ __('Next') }}
                    </h3>
                  @endif
                @endif
              @endif
            </div>
            <span class="sub-title"></span>
          </div>
          <div class="price-value">
            <div class="value">
              <span class="amount">{{ $package->price == 0 ? 'Free' : format_price($package->price) }}</span>
              <span class="month">/{{ $package->term }}</span>
            </div>
          </div>

          <ul class="pricing-content">
            <li>{{ __('Services') . ' :' }} {{ $package->number_of_service_add }}</li>
            <li>{{ __('Featured Services') . ' : ' }} {{ $package->number_of_service_featured }}</li>
            <li>{{ __('Custom Form') . ' : ' }} {{ $package->number_of_form_add }}</li>
            <li class="{{ $package->live_chat_status == 0 ? 'disable' : '' }}">{{ __('Live Chat') }}</li>
            <li class="{{ $package->qr_builder_status == 0 ? 'disable' : '' }}">{{ __('QR Builder') }}</li>
            @if (!is_null($package->custom_features))
              @php
                $features = explode("\n", $package->custom_features);
              @endphp
              @if (count($features) > 0)
                @foreach ($features as $key => $value)
                  <li>{{ $value }} </li>
                @endforeach
              @endif
            @endif

          </ul>

          @php
            $hasPendingMemb = \App\Http\Helpers\SellerPermissionHelper::hasPendingMembership(Auth::id());
          @endphp
          @if ($package_count < 2 && !$hasPendingMemb)
            <div class="px-4">
              @if (isset($current_package->id) && $current_package->id === $package->id)
                @if ($package->term != 'lifetime' || $current_membership->is_trial == 1)
                  <a href="{{ route('seller.plan.extend.checkout', $package->id) }}"
                    class="btn btn-success btn-lg w-75 fw-bold mb-3">{{ __('Extend') }}</a>
                @endif
              @else
                <a href="{{ route('seller.plan.extend.checkout', $package->id) }}"
                  class="btn btn-primary btn-block btn-lg fw-bold mb-3">{{ __('Buy Now') }}</a>
              @endif
            </div>
          @endif
        </div>
      </div>
    @endforeach
  </div>
@endsection
