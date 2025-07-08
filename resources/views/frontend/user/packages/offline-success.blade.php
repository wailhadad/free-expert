@extends('frontend.layout')

@section('pageHeading')
  {{ __('Payment Submitted') }}
@endsection

@section('content')
  @includeIf('frontend.partials.breadcrumb', ['breadcrumb' => $breadcrumb, 'title' => __('Payment Submitted')])
  <section class="user-dashboard pt-100 pb-60">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-body text-center">
              <div class="mb-4">
                <i class="fas fa-clock text-warning" style="font-size: 4rem;"></i>
              </div>
              <h3 class="text-warning mb-3">{{ __('Payment Submitted Successfully!') }}</h3>
              <p class="lead mb-4">{{ __('Your offline payment has been submitted and is pending admin approval.') }}</p>
              <p class="mb-4">{{ __('You will receive an email notification once your payment is approved.') }}</p>
              <div class="alert alert-info">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>{{ __('Note:') }}</strong> {{ __('Your package features will be activated once the admin approves your payment.') }}
              </div>
              <div class="mt-4">
                <a href="{{ route('user.dashboard') }}" class="btn btn-primary mr-3">
                  <i class="fas fa-tachometer-alt mr-2"></i>{{ __('Go to Dashboard') }}
                </a>
                <a href="{{ route('user.packages.subscription_log') }}" class="btn btn-outline-primary">
                  <i class="fas fa-list mr-2"></i>{{ __('View Subscription Log') }}
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection 