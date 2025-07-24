@extends('frontend.layout')

@php
  $pageTitle = $quoteBtnStatus == 0 ? __('Service Checkout') : __('Request A Quote');
@endphp

@section('pageHeading')
  {{ $pageTitle }}
@endsection
@section('metaKeywords')
  {{ $seoInfo->meta_keyword_service_order ?? '' }}
@endsection

@section('metaDescription')
  {{ $seoInfo->meta_description_service_order ?? '' }}
@endsection
@section('content')
  @includeIf('frontend.partials.breadcrumb', [
      'breadcrumb' => $breadcrumb,
      'serviceTitle' => $serviceTitle,
      'title' => $pageTitle,
  ])

  <!--====== Start Service Checkout Area ======-->
  <section class="service-checkout-area pt-100 pb-60">
    <div class="container">
      {{-- show error message for attachment (Offline) --}}
      @error('attachment')
        <div class="row mb-3">
          <div class="col">
            <div class="alert alert-danger alert-block">
              <strong>{{ $message }}</strong>
              <button type="button" class="close" data-dismiss="alert">×</button>
            </div>
          </div>
        </div>
      @enderror


      <form action="{{ route('service.place_order', ['slug' => request()->route('slug')]) }}" method="POST"
        enctype="multipart/form-data" id="payment-form">
        @csrf
        @if ($errors->any())
          <div class="alert alert-danger">
            <ul class="mb-0">
              @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif
        <div class="row">
          <div class=" @if ($quoteBtnStatus == 0) col-lg-8 @else col-12 @endif">
            <input type="hidden" name="quote_btn_status" value="{{ $quoteBtnStatus }}">
            <div class="row mb-40">
              <!-- Service Information Section -->
              <div class="col-12 mb-4">
                <div class="card">
                  <div class="card-body">
                    <h4 class="card-title">{{ __('Service Information') }}</h4>
                    <p class="card-text">{{ $serviceTitle ?? __('Service Details') }}</p>
                    @if(isset($package))
                      <div class="mt-3">
                        <h6>{{ __('Selected Package') }}: {{ $package->name }}</h6>
                        <p class="text-muted">{{ __('Package details and features are shown in the payment section.') }}</p>
                      </div>
                    @endif
                  </div>
                </div>
              </div>

              <!-- Form Fields Section -->
              @if(count($inputFields) > 0)
                @foreach ($inputFields as $inputField)
                  @if ($inputField->type == 1)
                    <div class="col-md-6">
                      <div class="form-group mb-30">
                        <label>
                          {{ __($inputField->label) }}{{ $inputField->is_required == 1 ? '*' : '' }}
                        </label>
                        <input type="text" class="form-control" name="{{ $inputField->name }}"
                          placeholder="{{ __($inputField->placeholder) }}" value="{{ old($inputField->name) }}">
                        @error($inputField->name)
                          <p class="mt-2 text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                  @elseif ($inputField->type == 2)
                    <div class="col-md-6">
                      <div class="form-group mb-30">
                        <label>
                          {{ __($inputField->label) }}{{ $inputField->is_required == 1 ? '*' : '' }}
                        </label>
                        <input type="number" class="form-control" name="{{ $inputField->name }}"
                          placeholder="{{ __($inputField->placeholder) }}" value="{{ old($inputField->name) }}">
                        @error($inputField->name)
                          <p class="mt-2 text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                  @elseif ($inputField->type == 3)
                    @php $options = json_decode($inputField->options); @endphp

                    <div class="col-md-6">
                      <div class="form-group mb-30">
                        <label>
                          {{ __($inputField->label) }}{{ $inputField->is_required == 1 ? '*' : '' }}
                        </label>
                        <select class="form-control" name="{{ $inputField->name }}">
                          <option selected disabled>{{ __($inputField->placeholder) }}</option>

                          @foreach ($options as $option)
                            <option value="{{ $option }}" {{ old($inputField->name) == $option ? 'selected' : '' }}>
                              {{ __($option) }}
                            </option>
                          @endforeach
                        </select>
                        @error($inputField->name)
                          <p class="mt-2 text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                  @elseif ($inputField->type == 4)
                    @php $options = json_decode($inputField->options); @endphp

                    <div class="col-12">
                      <div class="form-group mb-30">
                        <label class="mb-1">
                          {{ __($inputField->label) }}{{ $inputField->is_required == 1 ? '*' : '' }}
                        </label>
                        <br>
                        @foreach ($options as $option)
                          <div class="custom-control custom-checkbox custom-control-inline">
                            <input type="checkbox" id="{{ 'option-' . $loop->iteration }}"
                              name="{{ $inputField->name . '[]' }}" class="custom-control-input"
                              value="{{ $option }}"
                              {{ is_array(old($inputField->name)) && in_array($option, old($inputField->name)) ? 'checked' : '' }}>
                            <label for="{{ 'option-' . $loop->iteration }}"
                              class="custom-control-label">{{ $option }}</label>
                          </div>
                        @endforeach
                        @error($inputField->name)
                          <p class="mt-2 text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                  @elseif ($inputField->type == 5)
                    <div class="col-12">
                      <div class="form-group mb-30">
                        <label>
                          {{ __($inputField->label) }}{{ $inputField->is_required == 1 ? '*' : '' }}
                        </label>
                        <textarea class="form-control" name="{{ $inputField->name }}" placeholder="{{ __($inputField->placeholder) }}"
                          rows="2">{{ old($inputField->name) }}</textarea>
                        @error($inputField->name)
                          <p class="mt-2 text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                  @elseif ($inputField->type == 6)
                    <div class="col-md-6">
                      <div class="form-group mb-30">
                        <label>
                          {{ __($inputField->label) }}{{ $inputField->is_required == 1 ? '*' : '' }}
                        </label>
                        <input type="text" class="form-control datepicker ltr" name="{{ $inputField->name }}"
                          placeholder="{{ __($inputField->placeholder) }}" readonly autocomplete="off"
                          value="{{ old($inputField->name) }}">
                        @error($inputField->name)
                          <p class="mt-2 text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                  @elseif ($inputField->type == 7)
                    <div class="col-md-6">
                      <div class="form-group mb-30">
                        <label>
                          {{ __($inputField->label) }}{{ $inputField->is_required == 1 ? '*' : '' }}
                        </label>
                        <input type="text" class="form-control timepicker ltr" name="{{ $inputField->name }}"
                          placeholder="{{ __($inputField->placeholder) }}" readonly autocomplete="off"
                          value="{{ old($inputField->name) }}">
                        @error($inputField->name)
                          <p class="mt-2 text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                  @else
                    <div class="col-md-6">
                      <div class="form-group mb-30">
                        <label>
                          {{ __($inputField->label) }}{{ $inputField->is_required == 1 ? '*' : '' }}
                          <span
                            class="text-info {{ $currentLanguageInfo->direction == 0 ? 'ms-2' : 'me-2' }}">({{ __('Only .zip file is allowed') . '.' }})</span>
                        </label>
                        <input type="file" name="{{ 'form_builder_' . $inputField->name }}">
                        @error("form_builder_$inputField->name")
                          <p class="mt-2 text-danger">{{ $message }}</p>
                        @enderror
                      </div>
                    </div>
                  @endif
                @endforeach
              @else
                <!-- No Form Fields Message -->
                <div class="col-12">
                  <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    {{ __('No additional information required for this service. You can proceed with the payment.') }}
                  </div>
                </div>
              @endif
            </div>

            @if ($quoteBtnStatus != 0)
              <div class="row mb-40">
                <div class="col-12 text-center">
                  <button class="btn btn-lg btn-primary radius-sm" id="payment-form-btn">
                    {{ $quoteBtnStatus == 0 ? __('Place Order') : __('Submit') }}
                  </button>
                </div>
              </div>
            @endif
          </div>
          @if ($quoteBtnStatus == 0)
            <div class="col-lg-4">
              @if (isset($package))
                @php
                  $position = $currencyInfo->base_currency_symbol_position;
                  $symbol = $currencyInfo->base_currency_symbol;
                @endphp

                <div class="gigs-sidebar mb-40">
                  <div class="packages-widgets">
                    <div class="packages-content-wrap">
                      <div class="packages-content">
                        <div class="p-2">
                          <h3 class="text-center">{{ __('Selected Package') }}</h3>
                        </div>
                        <hr class="p-0 m-0">
                        <ul class="mt-30 list-unstyled">
                          <li class="d-flex justify-content-between">

                            <h3>{{ $package->name }}</h3>
                            <h3>
                              {{ $position == 'left' ? $symbol : '' }}{{ formatPrice($package->current_price) }}{{ $position == 'right' ? $symbol : '' }}
                              @if ($package->previous_price)
                                <del class="ms-2 mdf_34335">
                                  {{ $position == 'left' ? $symbol : '' }}{{ formatPrice($package->previous_price) }}{{ $position == 'right' ? $symbol : '' }}
                                </del>
                              @endif
                            </h3>

                          </li>
                          <li>

                          </li>
                        </ul>
                        <div class="mt-2 mb-2">
                          @if (!empty($package->delivery_time) || !empty($package->number_of_revision))
                            <span class="additional-info">
                              @if (!empty($package->delivery_time))
                                <span class="delivery">
                                  <i class="far fa-clock "></i>
                                  {{ $package->delivery_time }}
                                  {{ $package->delivery_time > 1 ? __('Days Delivery') : __('Day Delivery') }}</span>
                              @endif

                              @if (!empty($package->number_of_revision))
                                &nbsp;&nbsp;
                                <span class="revisions"><i class="far fa-sync-alt"></i>
                                  {{ $package->number_of_revision }}
                                  {{ $package->number_of_revision > 1 ? __('Revisions') : __('Revision') }}</span>
                              @endif
                            </span>
                          @endif
                        </div>
                        @php $features = explode(PHP_EOL, $package->features); @endphp
                        <ul class="features list-unstyled">
                          @foreach ($features as $feature)
                            <li class="feature check-icon">
                              {{ $feature }}
                            </li>
                          @endforeach
                        </ul>

                        @php
                          $chekedAddons = session()->get('addons');
                          $adonPrice = 0;
                        @endphp

                        @if (count($addons) > 0 && $chekedAddons)
                          <h3><span class="title">{{ __('Addons') }}</span></h3>
                          <ul class="features mt-3 list-unstyled">


                            @foreach ($addons as $addon)
                              @if (in_array($addon->id, $chekedAddons))
                                <li class="feature check-icon">

                                  {{ __($addon->name) }}
                                  <span>(<span
                                      class="text-danger">+</span>{{ $position == 'left' ? $symbol : '' }}{{ formatPrice($addon->price) }}{{ $position == 'right' ? $symbol : '' }})</span>
                                </li>
                                @php
                                  $adonPrice = $adonPrice + $addon->price;
                                @endphp
                              @endif
                            @endforeach
                          </ul>
                        @endif

                        @php
                          $totalPrice = $package->current_price + $adonPrice;
                          $tax = ($basicInfo->tax / 100) * $totalPrice;
                        @endphp

                        <hr class="pb-1 mb-1">
                        <p class="mb-0"><strong>{{ __('Subtotal') . ':' }}</strong>
                          {{ $position == 'left' ? $symbol : '' }}{{ formatPrice($totalPrice) }}{{ $position == 'right' ? $symbol : '' }}
                        </p>
                        <p class="mb-0"><strong>{{ __('Tax') . ':' }}</strong>
                          {{ $position == 'left' ? $symbol : '' }}{{ formatPrice($tax) }}{{ $position == 'right' ? $symbol : '' }}
                        </p>
                        <p><strong>{{ __('Total') . ':' }}</strong>
                          {{ $position == 'left' ? $symbol : '' }}{{ formatPrice($tax + $totalPrice) }}{{ $position == 'right' ? $symbol : '' }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              @endif
              <div class="order-payment mb-40">
                <h4 class="mb-3">{{ __('Payment Method') }}</h4>
                @error('gateway')
                  <p class="mt-2 text-danger">{{ $message }}</p>
                @enderror

                @if ($quoteBtnStatus == 0)
                  <div class="form-group mb-30">
                    <label for="subuser_id">Order as</label>
                    <div class="dropdown" id="profile-dropdown-wrapper">
                      <button class="btn btn-light d-flex align-items-center gap-2" type="button" id="profileDropdownBtn" data-bs-toggle="dropdown" aria-expanded="false">
                        <img id="profileDropdownAvatar" src="{{ Auth::guard('web')->user()->image ? asset('assets/img/users/' . Auth::guard('web')->user()->image) : asset('assets/img/profile.jpg') }}" class="rounded-circle" style="width:36px;height:36px;object-fit:cover;">
                        <span id="profileDropdownName">{{ Auth::guard('web')->user()->username }}</span>
                        <i class="bi bi-caret-down-fill ms-2"></i>
                      </button>
                      <ul class="dropdown-menu" id="profileDropdownMenu" aria-labelledby="profileDropdownBtn" style="max-height:300px;overflow-y:auto;min-width:220px;"></ul>
                      <input type="hidden" name="subuser_id" id="subuser_id" value="{{ old('subuser_id', '') }}">
                    </div>
                    @error('subuser_id')
                      <p class="mt-2 text-danger">{{ $message }}</p>
                    @enderror
                  </div>

                    <select class="niceselect form-control wide" name="gateway">
                      <option selected disabled>{{ __('Select a Payment Gateway') }}</option>

                      @if (count($onlineGateways) > 0)
                        @foreach ($onlineGateways as $onlineGateway)
                          <option value="{{ $onlineGateway->keyword }}"
                            {{ old('gateway') == $onlineGateway->keyword ? 'selected' : '' }}
                            data-gateway_type="online">
                            {{ __($onlineGateway->name) }}
                          </option>
                        @endforeach
                      @endif

                      @if (count($offlineGateways) > 0)
                        @foreach ($offlineGateways as $offlineGateway)
                          <option value="{{ $offlineGateway->id }}"
                            {{ old('gateway') == $offlineGateway->id ? 'selected' : '' }} data-gateway_type="offline"
                            data-has_attachment="{{ $offlineGateway->has_attachment }}">
                            {{ __($offlineGateway->name) }}
                          </option>
                        @endforeach
                      @endif
                    </select>
                @endif

                <div class="iyzico-element {{ old('gateway') == 'iyzico' ? '' : 'd-none' }}">
                  <div class="form-group mb-30">
                    <input type="text" name="phone_number" value="{{ old('phone_number') }}" class="form-control"
                      placeholder="Phone Number">
                    @error('phone_number')
                      <p class="text-danger">{{ $message }}</p>
                    @enderror
                  </div>
                  <div class="form-group mb-30">
                    <input type="text" name="identity_number" value="{{ old('identity_number') }}"
                      class="form-control" placeholder="Identity Number">
                    @error('identity_number')
                      <p class="text-danger">{{ $message }}</p>
                    @enderror
                  </div>
                  <div class="form-group mb-30">
                    <input type="text" name="city" value="{{ old('city') }}" class="form-control"
                      placeholder="City">
                    @error('city')
                      <p class="text-danger">{{ $message }}</p>
                    @enderror
                  </div>
                  <div class="form-group mb-30">
                    <input type="text" name="country" value="{{ old('country') }}" class="form-control"
                      placeholder="Country">
                    @error('country')
                      <p class="text-danger">{{ $message }}</p>
                    @enderror
                  </div>
                  <div class="form-group mb-30">
                    <input type="text" name="address" value="{{ old('address') }}" class="form-control"
                      placeholder="Address">
                    @error('address')
                      <p class="text-danger">{{ $message }}</p>
                    @enderror
                  </div>
                  <div class="form-group mb-30">
                    <input type="text" name="zip_code" value="{{ old('zip_code') }}" class="form-control"
                      placeholder="Zip Code">
                    @error('zip_code')
                      <p class="text-danger">{{ $message }}</p>
                    @enderror
                  </div>
                </div>

                <!-----------stripe------------->
                <div id="stripe-element" class="mb-2 mt-2">
                  <!-- A Stripe Element will be inserted here. -->
                </div>
                <!-- Used to display form errors -->
                <div id="stripe-errors" role="alert" class="mb-2 text-danger"></div>
                <!-----------stripe------------->

                <div class="mt-3 mdf_display_none" id="authorizenet-form">
                  <div class="row">
                    <div class="col-md-12 mb-4">
                      <div class="form-group mb-30">
                        <label>{{ __('Card Number') . '*' }}</label>
                        <input type="text" class="form-control" id="cardNumber" autocomplete="off"
                          placeholder="Enter Card Number">
                      </div>
                    </div>

                    <div class="col-md-12 mb-4">
                      <div class="form-group mb-30">
                        <label>{{ __('Card Code') . '*' }}</label>
                        <input type="text" class="form-control" id="cardCode" autocomplete="off"
                          placeholder="Enter Card Code">
                      </div>
                    </div>

                    <div class="col-md-12 mb-4">
                      <div class="form-group mb-30">
                        <label>{{ __('Expiry Month') . '*' }}</label>
                        <input type="text" class="form-control" id="expMonth" placeholder="Enter Expiry Month">
                      </div>
                    </div>

                    <div class="col-md-12 mb-4">
                      <div class="form-group mb-30">
                        <label>{{ __('Expiry Year') . '*' }}</label>
                        <input type="text" class="form-control" id="expYear" placeholder="Enter Expiry Year">
                      </div>
                    </div>

                    <input type="hidden" name="opaqueDataValue" id="opaqueDataValue">
                    <input type="hidden" name="opaqueDataDescriptor" id="opaqueDataDescriptor">

                    <div id="anetErrors"></div>
                  </div>
                </div>

                @if ($quoteBtnStatus == 0 && count($offlineGateways) > 0)
                  <div class="row ">
                    <div class="col-12 mt-3">
                      @foreach ($offlineGateways as $offlineGateway)
                        @if ($offlineGateway->has_attachment == 1)
                          <div class="form-group mb-30 mb-3 mdf_display_none"
                            id="{{ 'gateway-attachment-' . $offlineGateway->id }}">
                            <label><strong>{{ __('Attachment') . '*' }}</strong></label>
                            <br>
                            <input type="file" name="attachment" class="form-control-file">
                            <span class="text-warning">{{ __('Note: File type only jpg, jpeg, png and svg') }}.</span>
                          </div>
                        @endif

                        @if (!is_null($offlineGateway->short_description))
                          <div class="form-group mb-30 mb-3 mdf_display_none"
                            id="{{ 'gateway-description-' . $offlineGateway->id }}">
                            <strong>{{ __('Description') }}</strong>
                            <br>
                            <p>{{ $offlineGateway->short_description }}</p>
                          </div>
                        @endif

                        @if (!is_null($offlineGateway->instructions))
                          <div class="form-group mb-30 mb-3 mdf_display_none"
                            id="{{ 'gateway-instructions-' . $offlineGateway->id }}">
                            <strong class="">{{ __('Instructions') }}</strong>
                            <br>
                            {!! replaceBaseUrl($offlineGateway->instructions, 'summernote') !!}
                          </div>
                        @endif
                      @endforeach
                    </div>
                  </div>
                @endif

                @if ($quoteBtnStatus == 0)
                  <button class="btn btn-lg btn-primary radius-sm w-100" id="payment-form-btn">
                    {{ $quoteBtnStatus == 0 ? __('Place Order') : __('Submit') }}
                  </button>
                @endif

              </div>
            </div>
          @endif
        </div>
      </form>
    </div>
  </section>
  <!--====== End Service Checkout Area ======-->
@endsection


@section('script')
  <script type="text/javascript">
    const clientKey = '{{ $quoteBtnStatus == 0 ? $anetClientKey : '' }}';
    const loginId = '{{ $quoteBtnStatus == 0 ? $anetLoginId : '' }}';
    let stripe_key = "{{ $stripeKey }}";
  </script>
  <script type="text/javascript" src="https://js.stripe.com/v3/"></script>
  <script type="text/javascript" src="{{ $quoteBtnStatus == 0 ? $anetSource : '' }}" charset="utf-8"></script>

  <script type="text/javascript" src="{{ asset('assets/js/service.js') }}"></script>

  @if (old('gateway') == 'stripe')
    <script>
      $(document).ready(function() {
        $('#stripe-element').removeClass('d-none');
      });
    </script>
  @endif
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
  const mainUser = {
    id: '',
    username: '{{ Auth::guard('web')->user()->username }}',
    first_name: '{{ Auth::guard('web')->user()->first_name }}',
    last_name: '{{ Auth::guard('web')->user()->last_name }}',
    image: '{{ Auth::guard('web')->user()->image ? asset('assets/img/users/' . Auth::guard('web')->user()->image) : asset('assets/img/profile.jpg') }}'
  };
  const dropdownMenu = document.getElementById('profileDropdownMenu');
  const dropdownBtn = document.getElementById('profileDropdownBtn');
  const dropdownAvatar = document.getElementById('profileDropdownAvatar');
  const dropdownName = document.getElementById('profileDropdownName');
  // Add main user (Myself)
  dropdownMenu.innerHTML = '';
  const myselfLi = document.createElement('li');
  myselfLi.innerHTML = `<a class="dropdown-item d-flex align-items-center" href="#" data-id="" data-avatar="${mainUser.image}" data-name="${mainUser.username}">
    <img src="${mainUser.image}" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
    <span class="flex-grow-1">${mainUser.username} <span class="text-muted">(Main Account)</span></span>
    <span class="badge badge-success badge-sm">Active</span>
  </a>`;
  dropdownMenu.appendChild(myselfLi);
  document.getElementById('subuser_id').value = '';
  fetch('/subusers/prioritized')
    .then(res => res.json())
    .then(data => {
      if (data.subusers && data.subusers.length) {
        data.subusers.forEach(subuser => {
          const li = document.createElement('li');
          const statusBadge = subuser.status ? 
            '<span class="badge badge-success badge-sm ms-auto">Active</span>' : 
            '<span class="badge badge-danger badge-sm ms-auto">Inactive</span>';
          
          // Add disabled class and data attribute for inactive subusers
          const isActive = subuser.status;
          const disabledClass = isActive ? '' : 'disabled-subuser';
          const disabledAttr = isActive ? '' : 'data-disabled="true"';
          const lockIcon = isActive ? '' : '<i class="fas fa-lock text-muted me-1" style="font-size: 0.75rem;"></i>';
          
          li.innerHTML = `<a class="dropdown-item d-flex align-items-center ${disabledClass}" href="#" data-id="${subuser.id}" data-avatar="${subuser.image}" data-name="${subuser.username}" ${disabledAttr}>
            <img src="${subuser.image}" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
            <span class="flex-grow-1">${lockIcon}${subuser.username} <span class="text-muted">(${subuser.full_name})</span></span>
            ${statusBadge}
          </a>`;
          dropdownMenu.appendChild(li);
        });
      }
      
      // Show prioritization info if applicable
      if (data.is_prioritized) {
        const infoLi = document.createElement('li');
        infoLi.innerHTML = `
          <div class="dropdown-item-text small text-muted">
            <i class="fas fa-info-circle"></i>
            Showing all ${data.actual_count} subusers (${data.total_max_subusers} can be used for orders)
            <br><small><i class="fas fa-lock"></i> Inactive subusers cannot be selected for orders</small>
          </div>
        `;
        dropdownMenu.appendChild(infoLi);
      }
      
      // Click handler
      dropdownMenu.querySelectorAll('a.dropdown-item').forEach(a => {
        a.addEventListener('click', function(e) {
          e.preventDefault();
          
          // Check if this subuser is disabled/inactive
          if (this.hasAttribute('data-disabled') || this.classList.contains('disabled-subuser')) {
            console.log('Inactive subuser clicked - selection prevented');
            return; // Prevent selection of inactive subusers
          }
          
          dropdownAvatar.src = this.getAttribute('data-avatar');
          dropdownName.textContent = this.getAttribute('data-name');
          const id = this.getAttribute('data-id');
          document.getElementById('subuser_id').value = id ? id : '';
          console.log('Subuser selected - ID:', id, 'Value set to:', document.getElementById('subuser_id').value);
        });
      });
    });
});
</script>
<style>
#profile-picker { gap: 1.5rem; }
.profile-option {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 8px 12px;
  border-radius: 8px;
  border: 2px solid transparent;
  transition: border 0.2s, box-shadow 0.2s;
}

/* Status badge styles for dropdowns */
.badge-sm {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}

.dropdown-item .badge {
    font-weight: 500;
}

.dropdown-item:hover .badge {
    opacity: 0.8;
}

/* Ensure proper spacing in dropdown items */
.dropdown-item {
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    padding: 0.5rem 1rem !important;
}

.dropdown-item .flex-grow-1 {
    flex: 1;
    min-width: 0;
}

.dropdown-item img {
    flex-shrink: 0;
}

.dropdown-item .badge {
    flex-shrink: 0;
}

/* Disabled subuser styles for service checkout */
.dropdown-item.disabled-subuser {
    cursor: not-allowed !important;
    opacity: 0.6;
    pointer-events: auto; /* Keep pointer events for hover effects */
}

.dropdown-item.disabled-subuser:hover {
    background-color: #f8f9fa !important;
    cursor: not-allowed !important;
}

.dropdown-item.disabled-subuser img {
    opacity: 0.6;
}

.dropdown-item.disabled-subuser span {
    color: #6c757d !important;
}

.dropdown-item.disabled-subuser .text-muted {
    color: #adb5bd !important;
}

/* Ensure inactive badge text remains white */
.dropdown-item.disabled-subuser .badge-danger {
    color: white !important;
}
</style>
@endpush
