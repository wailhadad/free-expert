@extends('frontend.layout')

@section('pageHeading')
  @if (!empty($pageHeading))
    {{ $pageHeading->pricing_page_title ?? __('Pricing') }}
  @else
    {{ __('Pricing') }}
  @endif
@endsection

@section('metaKeywords')
  @if (!empty($seoInfo))
    {{ $seoInfo->pricing_page_meta_keywords ?? '' }}
  @endif
@endsection

@section('metaDescription')
  @if (!empty($seoInfo))
    {{ $seoInfo->pricing_page_meta_description ?? '' }}
  @endif
@endsection

@section('content')
  @includeIf('frontend.partials.breadcrumb', ['breadcrumb' => $breadcrumb ?? '', 'title' => 'Agency'])

  <!-- Pricing Start -->
  <div class="pricing-area pt-100 pb-70">
    <div class="container">
      <div class="row justify-content-center">
        @foreach ($user_packages as $package)
          <div class="col-md-6 col-lg-4">
            @php
              $isCurrent = auth('web')->check() && isset($currentPackage) && $currentPackage && $currentPackage->id == $package->id;
              $isRecommended = $package->recommended == 1;
              $flagCount = ($isCurrent ? 1 : 0) + ($isRecommended ? 1 : 0);
              $cardPaddingTop = $flagCount === 2 ? '80px' : ($flagCount === 1 ? '56px' : '32px');
              $cardMinHeight = $flagCount === 2 ? '540px' : '500px';
            @endphp
            <div class="card mb-30 position-relative {{ $isRecommended ? 'active' : '' }}"
                 data-aos-delay="100"
                 style="background: {{ $isRecommended ? '#fffbe6' : '#f7f7fa' }}; box-shadow: {{ $isRecommended ? '0 4px 24px rgba(255,193,7,0.18)' : '0 2px 8px rgba(0,0,0,0.04)' }}; border: {{ $isRecommended ? '2px solid #ffc107' : '1px solid #e0e0e0' }}; padding-top: {{ $cardPaddingTop }}; min-height: {{ $cardMinHeight }};">
              <!-- Flags: Top-right, stacked (Recommended always above Current) -->
              <div style="position: absolute; top: 16px; right: 16px; display: flex; flex-direction: column; align-items: flex-end; gap: 12px; z-index: 3;">
                @if ($isRecommended)
                  <span style="background: linear-gradient(90deg, #ffd700 0%, #ffb300 100%); color: #222; padding: 4px 14px 4px 10px; border-radius: 12px; box-shadow: 0 2px 8px rgba(255,193,7,0.18); font-weight: 600; display: flex; align-items: center; gap: 6px; font-size: 0.95rem;">
                    <i class="fas fa-star" style="color: #ff9800; font-size: 1.1em;"></i> {{ __('Recommended') }}
                  </span>
                @endif
                @if ($isCurrent)
                  <span style="background: linear-gradient(90deg, #4caf50 0%, #43a047 100%); color: #fff; padding: 4px 14px 4px 10px; border-radius: 12px; box-shadow: 0 2px 8px rgba(76,175,80,0.18); font-weight: 600; display: flex; align-items: center; gap: 6px; font-size: 0.95rem;">
                    <i class="fas fa-check-circle" style="color: #fff; font-size: 1.1em;"></i> {{ __('Current') }}
                  </span>
                @endif
              </div>
              <div class="d-flex align-items-center">
                <div class="icon"><i class="far fa-layer-group"></i></div>
                <div class="label">
                  <h4>{{ __($package->title) }}</h4>
                </div>
              </div>
              <div class="d-flex align-items-center">
                <span class="price mt-3">{{ $package->price == 0 ? __('Free') : format_price($package->price) }}</span>
                <span class="period">/ {{ __('Lifetime') }}</span>
              </div>
              @if (!empty($package->description))
                <div class="package-description mb-2">{{ $package->description }}</div>
              @endif
              <h5>{{ __("What's Included") }}</h5>
              <ul class="pricing-list list-unstyled p-0">
                <li><i class="fal fa-check"></i>{{ __('Max Subusers') }}: {{ $package->max_subusers }}</li>
                @if (!is_null($package->custom_features))
                  @php
                    $features = explode("\n", $package->custom_features);
                  @endphp
                  @if (count($features) > 0)
                    @foreach ($features as $key => $value)
                      <li><i class="fal fa-check"></i> {{ __($value) }} </li>
                    @endforeach
                  @endif
                @endif
              </ul>
              <div class="btn-groups mt-3">
                @auth('web')
                  <a href="{{ route('user.packages.checkout', $package->id) }}" class="btn btn-lg btn-outline radius-sm" title="{{ __('Purchase') }}" target="_self">{{ __('Purchase') }}</a>
                @else
                  <a href="{{ route('user.login') }}" class="btn btn-lg btn-outline radius-sm" title="{{ __('Login to Purchase') }}" target="_self">{{ __('Login to Purchase') }}</a>
                @endauth
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
  <!-- Pricing End -->

  <div class="mt-50 text-center advertise">
    {!! showAd(3) !!}
  </div>
@endsection
