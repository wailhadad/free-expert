@extends('frontend.layout')

@section('pageHeading')
  {{ __('Payment Success') }}
@endsection

@section('content')
  @includeIf('frontend.partials.breadcrumb', ['breadcrumb' => $breadcrumb, 'title' => __('Payment Success')])
  <section class="user-dashboard pt-100 pb-60">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-body text-center">
              <div class="mb-4">
                <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
              </div>
              <h3 class="text-success mb-3">{{ __('Payment Successful!') }}</h3>
              <p class="lead mb-4">{{ __('Your user package purchase has been completed successfully.') }}</p>
              <p class="mb-4">{{ __('You will receive a confirmation email with your invoice shortly.') }}</p>
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