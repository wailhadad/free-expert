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
            @endphp
            <div class="card mb-30 position-relative {{ $isRecommended ? 'active' : '' }}"
                 data-aos-delay="100"
                 style="background: {{ $isRecommended ? '#fffbe6' : '#f7f7fa' }}; box-shadow: {{ $isRecommended ? '0 4px 24px rgba(255,193,7,0.18)' : '0 2px 8px rgba(0,0,0,0.04)' }}; border: {{ $isRecommended ? '2px solid #ffc107' : '1px solid #e0e0e0' }}; height: 600px; display: flex; flex-direction: column;">
              <div style="display: flex; flex-direction: column; height: 100%; padding: 20px;">
                <div class="d-flex align-items-center" style="margin-top: 20px;">
                  <div class="icon"><i class="far fa-layer-group"></i></div>
                  <div class="label">
                    <h4>{{ __($package->title) }}</h4>
                  </div>
                </div>
                
                <!-- Badges moved below title for better alignment -->
                <div style="display: flex; gap: 8px; margin-top: 15px; margin-bottom: 20px; flex-wrap: wrap;">
                  @if ($isRecommended)
                    <span style="background: linear-gradient(90deg, #ffd700 0%, #ffb300 100%); color: #222; padding: 4px 10px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                      <i class="fas fa-star" style="color: #ff9800; font-size: 0.9em;"></i> {{ __('Recommended') }}
                    </span>
                  @endif
                  @if ($isCurrent)
                    <span style="background: linear-gradient(90deg, #4caf50 0%, #43a047 100%); color: #fff; padding: 4px 10px; border-radius: 8px; font-size: 0.8rem; font-weight: 600; display: flex; align-items: center; gap: 4px;">
                      <i class="fas fa-check-circle" style="color: #fff; font-size: 0.9em;"></i> {{ __('Current') }}
                    </span>
                  @endif
                </div>
                
                <div class="d-flex align-items-center" style="margin-top: {{ (!$isRecommended && !$isCurrent) ? '35px' : '10px' }};">
                  <span class="price mt-3">{{ $package->price == 0 ? __('Free') : format_price($package->price) }}</span>
                  <span class="period">/ {{ ucfirst($package->term) }}</span>
                </div>
                @if($package->term === 'lifetime')
                  <div class="text-center mb-2">
                    <span style="background: linear-gradient(90deg, #28a745 0%, #20c997 100%); color: white; padding: 2px 8px; border-radius: 8px; font-size: 0.8rem; font-weight: 600;">{{ __('One-time payment') }}</span>
                  </div>
                @elseif($package->term === 'yearly')
                  <div class="text-center mb-2">
                    <span style="background: linear-gradient(90deg, #17a2b8 0%, #138496 100%); color: white; padding: 2px 8px; border-radius: 8px; font-size: 0.8rem; font-weight: 600;">{{ __('Billed annually') }}</span>
                  </div>
                @elseif($package->term === 'monthly')
                  <div class="text-center mb-2">
                    <span style="background: linear-gradient(90deg, #ffc107 0%, #e0a800 100%); color: #212529; padding: 2px 8px; border-radius: 8px; font-size: 0.8rem; font-weight: 600;">{{ __('Billed monthly') }}</span>
                  </div>
                @endif
                @if (!empty($package->description))
                  <div class="package-description mb-2">{{ $package->description }}</div>
                @endif
                <h5 style="margin-top: 20px;">{{ __("What's Included") }}</h5>
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
                <div class="btn-groups mt-auto" style="margin-top: auto;">
                  @auth('web')
                    <a href="{{ route('user.packages.checkout', $package->id) }}" class="btn btn-lg btn-outline radius-sm" title="{{ __('Purchase') }}" target="_self">{{ __('Purchase') }}</a>
                  @else
                    <a href="{{ route('user.login') }}" class="btn btn-lg btn-outline radius-sm" title="{{ __('Login to Purchase') }}" target="_self">{{ __('Login to Purchase') }}</a>
                  @endauth
                </div>
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
