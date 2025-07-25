@extends('frontend.layout')

@section('pageHeading')
  {{ __('Customer Offer Checkout') }}
@endsection
@section('metaKeywords')
  {{ $seoInfo->meta_keyword_service_order ?? '' }}
@endsection
@section('metaDescription')
  {{ $seoInfo->meta_description_service_order ?? '' }}
@endsection

<style>
    /* Make only the offer form fields have a more visible border */
    #customer-offer-payment-form .form-group.mb-30 input[type="text"],
    #customer-offer-payment-form .form-group.mb-30 input[type="email"],
    #customer-offer-payment-form .form-group.mb-30 input[type="number"],
    #customer-offer-payment-form .form-group.mb-30 input[type="file"],
    #customer-offer-payment-form .form-group.mb-30 textarea,
    #customer-offer-payment-form .form-group.mb-30 select:not(#payment_method) {
      border: 2px solid #007bff !important;
      box-shadow: 0 0 0 2px rgba(0,123,255,0.08);
      border-radius: 6px;
    }
  </style>
  
@section('content')

  @includeIf('frontend.partials.breadcrumb', [
      'breadcrumb' => $breadcrumb,
      'serviceTitle' => $offer->title,
      'title' => __('Customer Offer Checkout'),
  ])

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var form = document.getElementById('customer-offer-payment-form');
      if (form) {
        form.addEventListener('submit', function(e) {
          var gateway = document.getElementById('payment_method');
          var errorMsg = document.getElementById('gateway-required-error');
          if (gateway && !gateway.value) {
            e.preventDefault();
            if (!errorMsg) {
              errorMsg = document.createElement('div');
              errorMsg.id = 'gateway-required-error';
              errorMsg.className = 'text-danger mt-1';
              errorMsg.textContent = 'Please select a payment gateway.';
              gateway.parentNode.appendChild(errorMsg);
            } else {
              errorMsg.style.display = 'block';
            }
            gateway.classList.add('is-invalid');
            gateway.focus();
            return false;
          } else if (errorMsg) {
            errorMsg.style.display = 'none';
            gateway.classList.remove('is-invalid');
          }
        });
      }
    });
  </script>


  <section class="service-checkout-area pt-100 pb-60">
    <div class="container">
      @if ($errors->any())
        <div class="alert alert-danger">
          <ul class="mb-0">
            @foreach ($errors->all() as $error)
              <li>{{ $error }}</li>
            @endforeach
          </ul>
        </div>
      @endif
      <form action="{{ route('customer.offer.process_checkout', $offer->id) }}" method="POST" enctype="multipart/form-data" id="customer-offer-payment-form">
        @csrf
        <div class="row">
          <div class="col-lg-8">
            <div class="row mb-40">
              <div class="col-12 mb-3">
                <div class="card shadow-sm">
                  <div class="card-body">
                    <h4 class="mb-2 text-success"><i class="fas fa-gift me-2"></i>{{ $offer->title }}</h4>
                    <p class="text-muted mb-2">{{ $offer->description }}</p>
                    <div class="d-flex align-items-center mb-2">
                      <span class="badge bg-primary me-2">{{ __('Freelancer') }}: {{ $offer->seller->username }}</span>
                      @if($offer->form)
                        <span class="badge bg-info me-2">{{ __('Form Required') }}</span>
                      @endif
                    </div>
                    <h5 class="text-success mb-0">{{ $offer->formatted_price }}</h5>
                  </div>
                </div>
              </div>
              <div class="col-12 mb-3">
                <div class="form-group mb-30">
                  <label class="fw-bold">{{ __('Order as') }}</label>
                  <div class="d-flex align-items-center gap-2 p-2 bg-light rounded">
                    @if($offer->subuser)
                      <img src="{{ $offer->subuser->image ? asset('assets/img/subusers/' . $offer->subuser->image) : asset('assets/img/profile.jpg') }}" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">
                      <span class="fw-semibold">{{ $offer->subuser->username }}</span>
                    @else
                      <img src="{{ Auth::guard('web')->user()->image ? asset('assets/img/users/' . Auth::guard('web')->user()->image) : asset('assets/img/profile.jpg') }}" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">
                      <span class="fw-semibold">{{ Auth::guard('web')->user()->username }} <span class="text-muted">({{ __('Myself') }})</span></span>
                    @endif
                  </div>
                  <input type="hidden" name="subuser_id" value="{{ $offer->subuser ? $offer->subuser->id : '' }}">
                </div>
              </div>
              @if($offer->form && $formFields->count() > 0)
                <div class="col-12 mb-3">
                  <div class="form-group mb-30">
                    <h6 class="fw-bold mb-3">
                      <i class="fas fa-file-alt text-info me-2"></i>{{ __('Additional Information Required') }}
                    </h6>
                    <div class="border rounded p-3 bg-light">
                      @foreach($formFields as $field)
                        <div class="mb-3">
                          <label for="{{ $field->name }}" class="form-label fw-bold">
                            {{ $field->label }}
                            @if($field->is_required)
                              <span class="text-danger">*</span>
                            @endif
                          </label>
                          @switch($field->type)
                            @case(1)
                              <input type="text" class="form-control @error($field->name) is-invalid @enderror" name="{{ $field->name }}" id="{{ $field->name }}" value="{{ old($field->name) }}" placeholder="{{ $field->placeholder }}" {{ $field->is_required ? 'required' : '' }}>
                              @break
                            @case(2)
                              <input type="email" class="form-control @error($field->name) is-invalid @enderror" name="{{ $field->name }}" id="{{ $field->name }}" value="{{ old($field->name) }}" placeholder="{{ $field->placeholder }}" {{ $field->is_required ? 'required' : '' }}>
                              @break
                            @case(3)
                              <select class="form-control @error($field->name) is-invalid @enderror" name="{{ $field->name }}" id="{{ $field->name }}" {{ $field->is_required ? 'required' : '' }}>
                                <option value="">{{ __('Select...') }}</option>
                                @foreach(json_decode($field->options) as $option)
                                  <option value="{{ $option }}" {{ old($field->name) == $option ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                              </select>
                              @break
                            @case(4)
                              @foreach(json_decode($field->options) as $option)
                                <div class="form-check">
                                  <input class="form-check-input" type="checkbox" name="{{ $field->name }}[]" value="{{ $option }}" id="{{ $field->name }}_{{ $loop->index }}" {{ in_array($option, old($field->name, [])) ? 'checked' : '' }}>
                                  <label class="form-check-label" for="{{ $field->name }}_{{ $loop->index }}">{{ $option }}</label>
                                </div>
                              @endforeach
                              @break
                            @case(5)
                              @foreach(json_decode($field->options) as $option)
                                <div class="form-check">
                                  <input class="form-check-input" type="radio" name="{{ $field->name }}" value="{{ $option }}" id="{{ $field->name }}_{{ $loop->index }}" {{ old($field->name) == $option ? 'checked' : '' }} {{ $field->is_required ? 'required' : '' }}>
                                  <label class="form-check-label" for="{{ $field->name }}_{{ $loop->index }}">{{ $option }}</label>
                                </div>
                              @endforeach
                              @break
                            @case(6)
                              <textarea class="form-control @error($field->name) is-invalid @enderror" name="{{ $field->name }}" id="{{ $field->name }}" rows="3" placeholder="{{ $field->placeholder }}" {{ $field->is_required ? 'required' : '' }}>{{ old($field->name) }}</textarea>
                              @break
                            @case(7)
                              <input type="number" class="form-control @error($field->name) is-invalid @enderror" name="{{ $field->name }}" id="{{ $field->name }}" value="{{ old($field->name) }}" placeholder="{{ $field->placeholder }}" {{ $field->is_required ? 'required' : '' }}>
                              @break
                            @case(8)
                              <input type="file" class="form-control @error('form_builder_' . $field->name) is-invalid @enderror" name="form_builder_{{ $field->name }}" id="form_builder_{{ $field->name }}" {{ $field->is_required ? 'required' : '' }}>
                              <small class="form-text text-muted">{{ __('Max file size:') }} {{ $field->file_size }}MB</small>
                              @break
                            @default
                              <input type="text" class="form-control @error($field->name) is-invalid @enderror" name="{{ $field->name }}" id="{{ $field->name }}" value="{{ old($field->name) }}" placeholder="{{ $field->placeholder }}" {{ $field->is_required ? 'required' : '' }}>
                          @endswitch
                          @error($field->name)
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                          @error('form_builder_' . $field->name)
                            <div class="invalid-feedback">{{ $message }}</div>
                          @enderror
                        </div>
                      @endforeach
                    </div>
                  </div>
                </div>
              @endif
              <div class="col-12 mb-3">
                <div class="form-group mb-30">
                  <label for="payment_method" class="fw-bold">{{ __('Select Payment Method') }}</label>
                  <select name="payment_method" id="payment_method" class="form-control wide niceselect @error('payment_method') is-invalid @enderror">
                    <option value="">{{ __('Choose a payment method...') }}</option>
                    @if($onlineGateways->count() > 0)
                      <optgroup label="{{ __('Online Payment Methods') }}">
                        @foreach($onlineGateways as $gateway)
                          <option value="{{ $gateway->name }}" {{ old('payment_method') == $gateway->name ? 'selected' : '' }}>{{ $gateway->name }}</option>
                        @endforeach
                      </optgroup>
                    @endif
                    @if($offlineGateways->count() > 0)
                      <optgroup label="{{ __('Offline Payment Methods') }}">
                        @foreach($offlineGateways as $gateway)
                          <option value="{{ $gateway->name }}" {{ old('payment_method') == $gateway->name ? 'selected' : '' }}>{{ $gateway->name }} ({{ __('Offline') }})</option>
                        @endforeach
                      </optgroup>
                    @endif
                  </select>
                  @error('payment_method')
                    <div class="invalid-feedback">{{ $message }}</div>
                  @enderror
                </div>
              </div>
              <div class="col-12 text-center">
                <button type="submit" class="btn btn-lg btn-primary radius-sm" id="payment-form-btn">
                  <i class="fas fa-credit-card me-2"></i>{{ __('Place Order') }}
                </button>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="gigs-sidebar mb-40">
              <div class="packages-widgets">
                <div class="packages-content-wrap">
                  <div class="packages-content">
                    <div class="p-2">
                      <h3 class="text-center">{{ __('Order Summary') }}</h3>
                    </div>
                    <hr class="p-0 m-0">
                    <ul class="mt-30 list-unstyled">
                      <li class="d-flex justify-content-between">
                        <h5>{{ __('Offer') }}</h5>
                        <h5>{{ $offer->title }}</h5>
                      </li>
                      <li class="d-flex justify-content-between">
                        <span>{{ __('Price') }}</span>
                        <span>{{ $offer->formatted_price }}</span>
                      </li>
                      <li class="d-flex justify-content-between">
                        <span>{{ __('Delivery Time') }}</span>
                        <span>{{ $offer->delivery_time }} {{ __('days') }}</span>
                      </li>
                      @if($offer->dead_line)
                      <li class="d-flex justify-content-between">
                        <span>{{ __('Deadline') }}</span>
                        <span>{{ $offer->dead_line->format('Y-m-d H:i') }}</span>
                      </li>
                      @endif
                    </ul>
                    <div class="mt-2 mb-2">
                      <span class="additional-info">
                        <span class="delivery">
                          <i class="far fa-clock"></i>
                          {{ __('Instant Delivery') }}
                        </span>
                      </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                      <span>{{ __('Tax') }}:</span>
                      <span>$0.00</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                      <span>{{ __('Total') }}:</span>
                      <span class="text-success">{{ $offer->formatted_price }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    </div>
  </section>
@endsection 