@extends('frontend.layout')

@section('pageHeading')
  {{ __('Checkout') }}
@endsection

@section('content')
@includeIf('frontend.partials.breadcrumb', ['breadcrumb' => $breadcrumb, 'title' => __('Checkout')])
<section class="user-dashboard pt-100 pb-60">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card shadow-lg rounded-lg mb-4 border-0 border border-primary-subtle">
          <div class="card-header bg-gradient-primary text-white rounded-top">
            <h3 class="mb-0 font-weight-bold"><i class="fas fa-shopping-cart mr-2"></i> {{ __('Package Checkout') }}</h3>
          </div>
          <div class="card-body p-4">
            <div class="mb-4">
              <h4 class="font-weight-bold text-primary mb-2">{{ $package->title }}</h4>
              <div class="d-flex align-items-center mb-2 flex-wrap">
                <span class="badge badge-pill px-3 py-2 mr-2 mb-2" style="font-size:1.1rem; background: #375ab7; color: #fff; margin-right: 4px;"><i class="fas fa-tag mr-1"></i> {{ $bs->base_currency_symbol }}{{ $package->price }} <span class="text-light">/ {{ ucfirst($package->term) }}</span></span>
                <span class="badge badge-pill px-3 py-2 mb-2" style="font-size:1.1rem; background: #5a8dee; color: #fff;"><i class="fas fa-users mr-1"></i> {{ __('Max Subusers:') }} {{ $package->max_subusers }}</span>
              </div>
              @if($package->custom_features)
                <div class="mt-2">
                  <span class="font-weight-bold text-secondary">{{ __('Features:') }}</span>
                  <ul class="list-unstyled ml-3 mb-0">
                    @foreach(explode("\n", $package->custom_features) as $feature)
                      <li class="mb-1"><i class="fas fa-check-circle text-primary mr-1"></i> {{ $feature }}</li>
                    @endforeach
                  </ul>
                </div>
              @endif
            </div>
            <hr class="my-4">
            <form action="{{ route('user.packages.processPayment', $package->id) }}" method="POST" enctype="multipart/form-data" id="checkout-form">
              @csrf
              <div class="form-group mb-4">
                <label for="payment_method" class="font-weight-bold text-primary">{{ __('Select Payment Method') }}</label>
                <select name="payment_method" id="payment_method" class="form-control custom-select-lg shadow-sm stylish-dropdown" required>
                  <option value="" selected disabled>{{ __('Choose a payment method...') }}</option>
                  @foreach($online_gateways as $gateway)
                    <option value="{{ $gateway->name }}" data-type="online">
                      {{ $gateway->name }}
                    </option>
                  @endforeach
                  @foreach($offline_gateways as $gateway)
                    <option value="{{ $gateway->name }}" data-type="offline">
                      {{ $gateway->name }} ({{ __('Offline') }})
                    </option>
                  @endforeach
                </select>
              </div>
              <div id="offline-instructions" class="alert alert-info shadow-sm" style="display: none;">
                <h6 class="alert-heading font-weight-bold"><i class="fas fa-info-circle mr-1"></i> {{ __('Payment Instructions') }}</h6>
                <div id="gateway-description"></div>
                <div id="gateway-instructions"></div>
                <div id="receipt-upload" style="display: none;">
                  <hr>
                  <div class="form-group mb-0">
                    <label for="receipt" class="font-weight-bold">{{ __('Upload Payment Receipt') }}</label>
                    <input type="file" class="form-control-file" id="receipt" name="receipt" accept="image/*,.pdf">
                    <small class="form-text text-muted">{{ __('Please upload a screenshot or PDF of your payment receipt.') }}</small>
                  </div>
                </div>
              </div>
              <button type="submit" class="btn btn-gradient-primary btn-lg btn-block mt-4 shadow-sm pay-btn-fix" id="pay-button" disabled>
                <i class="fas fa-credit-card mr-2"></i><span class="pay-btn-text">{{ __('Pay Now') }} - {{ $bs->base_currency_symbol }}{{ $package->price }}</span>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<style>
  .bg-gradient-primary {
    background: linear-gradient(90deg, #4e73df 0%,rgb(29, 8, 186) 100%) !important;
  }
  .btn-gradient-primary {
    background:rgb(0, 0, 227) !important;
    color: #fff !important;
    border: none;
    transition: box-shadow 0.2s, background 0.2s, color 0.2s;
    text-shadow: 0 1px 2px rgba(0,0,0,0.18);
    font-weight: 600;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 12px 0 rgba(37,99,235,.10);
  }
  .btn-gradient-primary:hover, .btn-gradient-primary:focus {
    background:rgb(15, 72, 230) !important;
    color: #fff !important;
    box-shadow: 0 0 0 0.2rem rgba(37,99,235,.18);
    text-shadow: 0 2px 6px rgba(0,0,0,0.18);
  }
  .pay-btn-fix {
    font-size: 1.25rem;
    border-radius: 2rem;
    padding: 0.85rem 1.5rem;
    box-shadow: 0 2px 12px 0 rgba(78,115,223,.08);
  }
  .pay-btn-text {
    color: #fff !important;
    text-shadow: 0 1px 2px rgba(0,0,0,0.18);
  }
  .stylish-dropdown {
    border-radius: 1.5rem;
    border: 2px solid #4e73df;
    font-size: 1.05rem;
    padding: 0.75rem 1.25rem;
    background: #fff !important;
    color: #23272b !important;
    box-shadow: 0 1px 4px 0 rgba(78,115,223,.06);
    transition: border-color 0.2s, box-shadow 0.2s, color 0.2s;
    font-weight: 500;
    appearance: auto !important;
    -webkit-appearance: auto !important;
    -moz-appearance: auto !important;
  }
  .stylish-dropdown:focus {
    border-color: #224abe;
    box-shadow: 0 0 0 0.2rem rgba(78,115,223,.13);
    color: #23272b !important;
    background: #fff !important;
  }
  .stylish-dropdown option {
    color: #23272b !important;
    background: #fff !important;
    font-weight: 500;
  }
  .card {
    border-radius: 1.25rem;
    border: 1.5px solid #4e73df22;
  }
  .card-header {
    border-radius: 1.25rem 1.25rem 0 0;
  }
  .badge {
    font-weight: 500;
    letter-spacing: 0.2px;
  }
  @media (max-width: 576px) {
    .card-header h3, .card-body h4 {
      font-size: 1.2rem;
    }
    .btn-lg {
      font-size: 1rem;
      padding: 0.75rem 1rem;
    }
    .pay-btn-fix {
      font-size: 1rem;
      padding: 0.7rem 1rem;
    }
    .stylish-dropdown {
      font-size: 1rem;
      padding: 0.6rem 1rem;
    }
  }
</style>

@section('script')
<script>
  $(document).ready(function() {
    // Handle payment method selection
    $('#payment_method').on('change', function() {
      const selectedMethod = $(this).val();
      const selectedOption = $(this).find('option:selected');
      const isOffline = selectedOption.data('type') === 'offline';
      // Enable the button only if a value is selected
      if (selectedMethod && selectedMethod !== '') {
        $('#pay-button').prop('disabled', false);
      } else {
        $('#pay-button').prop('disabled', true);
      }
      if (isOffline) {
        // Show offline instructions
        $('#offline-instructions').slideDown(200);
        // Get gateway instructions via AJAX
        $.ajax({
          url: '{{ route("user.packages.payment.instruction") }}',
          method: 'POST',
          data: {
            name: selectedMethod,
            _token: '{{ csrf_token() }}'
          },
          success: function(response) {
            $('#gateway-description').html('<p><strong>' + response.description + '</strong></p>');
            $('#gateway-instructions').html('<div class="mt-2">' + response.instructions + '</div>');
            if (response.has_attachment) {
              $('#receipt-upload').slideDown(200);
            } else {
              $('#receipt-upload').slideUp(200);
            }
          },
          error: function() {
            $('#gateway-description').html('<p class="text-danger">Error loading instructions.</p>');
            $('#gateway-instructions').html('');
            $('#receipt-upload').slideUp(200);
          }
        });
      } else {
        // Hide offline instructions for online payments
        $('#offline-instructions').slideUp(200);
      }
    });
    // On page load, check if a value is already selected (for browser autofill)
    if ($('#payment_method').val()) {
      $('#payment_method').trigger('change');
    }
    // Fallback: on button click, if a value is selected, forcibly enable and submit
    $('#pay-button').on('click', function(e) {
      const selectedMethod = $('#payment_method').val();
      if (!selectedMethod || selectedMethod === '') {
        e.preventDefault();
        $(this).prop('disabled', true);
        console.log('Pay button clicked but no payment method selected.');
        return false;
      }
      // If button is disabled but a value is selected, forcibly enable and submit
      if ($(this).prop('disabled') && selectedMethod) {
        $(this).prop('disabled', false);
        $('#checkout-form').submit();
      }
    });
  });
</script>
@endsection
@endsection 