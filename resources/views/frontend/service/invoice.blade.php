<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>{{ __('Service Order Invoice') }}</title>
  <link rel="stylesheet" href="{{ public_path('assets/css/pdf.css') }}">
  <style>
    {!! file_get_contents(public_path('assets/css/pdf.css')) !!}
    body { font-family: DejaVu Sans, serif; }
  </style>
</head>
<body>
  <div class="main">
    <table class="heading">
      <tr>
        <td>
          @if (!empty($orderInfo->logo))
            @php
              $logoPath = base_path('public/assets/img/' . $orderInfo->logo);
              if (file_exists($logoPath)) {
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoType = pathinfo($orderInfo->logo, PATHINFO_EXTENSION);
                $logoSrc = 'data:image/' . $logoType . ';base64,' . $logoData;
              } else {
                $logoPath = base_path('public/assets/img/logo-noir.png');
                $logoData = base64_encode(file_get_contents($logoPath));
                $logoSrc = 'data:image/png;base64,' . $logoData;
              }
            @endphp
            <img loading="lazy" src="{{ $logoSrc }}" height="40" class="d-inline-block">
          @else
            @php
              $logoPath = base_path('public/assets/img/logo-noir.png');
              $logoData = base64_encode(file_get_contents($logoPath));
              $logoSrc = 'data:image/png;base64,' . $logoData;
            @endphp
            <img loading="lazy" src="{{ $logoSrc }}" height="40" class="d-inline-block">
          @endif
          <!-- Debug: Logo value is {{ $orderInfo->logo ?? 'NULL' }} -->
        </td>
        <td class="text-right strong invoice-heading">{{ __('SERVICE ORDER INVOICE') }}</td>
      </tr>
    </table>
    <div class="header">
      <div class="ml-20">
        <table class="text-left">
          <tr>
            <td class="strong small gry-color">{{ __('Bill to') . ':' }}</td>
          </tr>
          <tr>
            <td class="strong">{{ $orderInfo->name }}</td>
          </tr>
          <tr>
            <td class="gry-color small"><strong>{{ __('Email') . ':' }} </strong>{{ $orderInfo->email_address }}</td>
          </tr>
        </table>
      </div>
      <div class="order-details">
        <table class="text-right">
          <tr>
            <td class="strong">{{ __('Order Details') . ':' }}</td>
          </tr>
          <tr>
            <td class="gry-color small"><strong>{{ __('Order No') . ':' }}</strong> #{{ $orderInfo->order_number }}</td>
          </tr>
          <tr>
            <td class="gry-color small"><strong>{{ __('Order Date') . ':' }}</strong> {{ date_format($orderInfo->created_at, 'M d, Y') }}</td>
          </tr>
          <tr>
            <td class="gry-color small"><strong>{{ __('Seller') . ':' }}</strong>
              @if ($orderInfo->seller_id != 0 && $orderInfo->seller)
                {{ $orderInfo->seller->username }}
              @else
                {{ __('Admin') }}
              @endif
            </td>
          </tr>
        </table>
      </div>
    </div>
    <div class="package-info">
      <table class="padding text-left small border-bottom">
        <thead>
          <tr class="gry-color info-titles">
            <th width="25%">{{ __('Service') }}</th>
            <th width="25%">{{ __('Total') }}</th>
            <th width="25%">{{ __('Payment Method') }}</th>
            <th width="25%">{{ __('Payment Status') }}</th>
          </tr>
        </thead>
        <tbody class="strong">
          <tr class="text-center">
            <td>{{ $serviceTitle }}</td>
            <td>
              @if (is_null($orderInfo->grand_total))
                {{ __('Price Requested') }}
              @else
                {{ $orderInfo->currency_text_position == 'left' ? $orderInfo->currency_text . ' ' : '' }}{{ formatPrice(number_format($orderInfo->grand_total, 2)) }}{{ $orderInfo->currency_text_position == 'right' ? ' ' . $orderInfo->currency_text : '' }}
              @endif
            </td>
            <td>
              @if (is_null($orderInfo->payment_method))
                {{ __('None') }}
              @else
                {{ $orderInfo->payment_method }}
              @endif
            </td>
            <td>{{ ucfirst($orderInfo->payment_status) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    @if (!is_null($orderInfo->addons))
      <div class="package-info">
        <table class="padding text-left small border-bottom">
          <thead>
            <tr class="gry-color info-titles">
              <th>{{ __('Addons') }}</th>
              <th>{{ __('Price') }}</th>
            </tr>
          </thead>
          <tbody>
            @php $addons = json_decode($orderInfo->addons); @endphp
            @foreach ($addons as $addon)
              @php $serviceAddon = \App\Models\ClientService\ServiceAddon::query()->find($addon->id); @endphp
              <tr>
                <td>{{ $serviceAddon->name }}</td>
                <td>
                  {{ $orderInfo->currency_text_position == 'left' ? $orderInfo->currency_text . ' ' : '' }}{{ formatPrice($addon->price) }}{{ $orderInfo->currency_text_position == 'right' ? ' ' . $orderInfo->currency_text : '' }}
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
    @if (!is_null($orderInfo->tax))
      <div class="package-info">
        <table class="padding text-left small border-bottom">
          <thead>
            <tr class="gry-color info-titles">
              <th>{{ __('Tax') }}</th>
              <th>{{ __('Tax Percentage') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>
                {{ $orderInfo->currency_text_position == 'left' ? $orderInfo->currency_text . ' ' : '' }}{{ formatPrice(number_format($orderInfo->tax, 2)) }}{{ $orderInfo->currency_text_position == 'right' ? ' ' . $orderInfo->currency_text : '' }}
              </td>
              <td>{{ $orderInfo->tax_percentage }}%</td>
            </tr>
          </tbody>
        </table>
      </div>
    @endif
    <table class="mt-80">
      <tr>
        <td class="text-right regards">{{ __('Thanks & Regards') . ',' }}</td>
      </tr>
      <tr>
        <td class="text-right strong regards">{{ $orderInfo->website_title ?? 'Your Company' }}</td>
      </tr>
    </table>
  </div>
</body>
</html>
