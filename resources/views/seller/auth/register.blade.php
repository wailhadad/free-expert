@extends('frontend.layout')

@section('pageHeading')
  @if (!empty($pageHeading))
    {{ $pageHeading->seller_signup_page_title }}
  @endif
@endsection

@section('metaKeywords')
  @if (!empty($seoInfo))
    {{ $seoInfo->meta_keyword_seller_signup }}
  @endif
@endsection

@section('metaDescription')
  @if (!empty($seoInfo))
    {{ $seoInfo->meta_description_seller_signup }}
  @endif
@endsection
@php
  $title = $pageHeading->seller_signup_page_title ?? __('No Page Title Found');
@endphp
@section('content')
  @includeIf('frontend.partials.breadcrumb', ['breadcrumb' => $breadcrumb, 'title' => $title])

  <!--====== Start Signup Area Section ======-->
  <div class="user-area-section ptb-100">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="user-form">
            <form action="{{ route('seller.signup_submit') }}" method="POST">
              @csrf

              @if($bs->google_login_status == 1)
              <div class="form-group mb-4" style="max-width: 300px;">
                <a href="{{ route('seller.auth.google') }}"
                   class="btn btn-danger d-inline-flex align-items-center gap-2 fw-bold px-4 py-2"
                   style="background-color: #269c35; border-color: #37db79;"
                   onmouseover="this.style.color='white';
                                this.style.backgroundColor='#32853e';
                                this.style.borderColor='#105a1f';"
                   onmouseout="this.style.color='white';
                               this.style.backgroundColor='#269c35';
                               this.style.borderColor='#37db79';">
                  <i class="fab fa-google fa-lg"></i>
                  Sign up with Google
                </a>
              </div>
              @endif

              <div class="form-group mb-4">
                <label>{{ __('Name') . '*' }}</label>
                <input type="text" class="form-control" name="name" value="{{ old('name') }}">
                @error('name')
                  <p class="text-danger mt-1">{{ $message }}</p>
                @enderror
              </div>
              <div class="form-group mb-4">
                <label>{{ __('Username') . '*' }}</label>
                <input type="text" class="form-control" name="username" value="{{ old('username') }}">
                @error('username')
                  <p class="text-danger mt-1">{{ $message }}</p>
                @enderror
              </div>

              <div class="form-group mb-4">
                <label>{{ __('Email Address') . '*' }}</label>
                <input type="email" class="form-control" name="email" value="{{ old('email') }}">
                @error('email')
                  <p class="text-danger mt-1">{{ $message }}</p>
                @enderror
              </div>
              <div class="form-group mb-4">
                <label>{{ __('Phone') . '*' }}</label>
                <input type="text" class="form-control" name="phone" value="{{ old('phone') }}">
                @error('phone')
                  <p class="text-danger mt-1">{{ $message }}</p>
                @enderror
              </div>

              <div class="form-group mb-4">
                <label>{{ __('Password') . '*' }}</label>
                <input type="password" class="form-control" name="password" value="{{ old('password') }}">
                @error('password')
                  <p class="text-danger mt-1">{{ $message }}</p>
                @enderror
              </div>

              <div class="form-group mb-4">
                <label>{{ __('Confirm Password') . '*' }}</label>
                <input type="password" class="form-control" name="password_confirmation"
                  value="{{ old('password_confirmation') }}">
                @error('password_confirmation')
                  <p class="text-danger mt-1">{{ $message }}</p>
                @enderror
              </div>


              @if ($bs -> google_recaptcha_status == 1)
                <div class="form-group my-4">
                  {!! NoCaptcha::renderJs() !!}
                  {!! NoCaptcha::display() !!}

                  @error('g-recaptcha-response')
                  <p class="text-danger mt-1">{{ $message }}</p>
                  @enderror
                </div>
              @endif

{{--
              @if ($bs -> google_recaptcha_status == 1)
                <div class="form-group my-4">
                  <script src="https://www.google.com/recaptcha/api.js" async defer></script>
                  <div class="g-recaptcha" data-sitekey="{{ env('GOOGLE_RECAPTCHA_SITE_KEY') }}"></div>

                  @error('g-recaptcha-response')
                  <p class="text-danger mt-1">{{ $message }}</p>
                  @enderror
                </div>
              @endif--}}


              <div class="form-group">
                <button type="submit" class="btn btn-lg btn-primary radius-sm">{{ __('Signup') }}</button>
              </div>
              <p class="mt-3">{{ __('Already have an account') . '?' }}
                <a href="{{ route('seller.login') }}">{{ __('Login Now') }}</a>
              </p>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!--====== End Signup Area Section ======-->
@endsection
