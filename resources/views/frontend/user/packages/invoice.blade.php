<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>{{ __('Invoice') }}</title>
  <link rel="stylesheet" href="{{ public_path('assets/css/pdf.css') }}">
  <style>
    {!! file_get_contents(public_path('assets/css/pdf.css')) !!}
    body {
      font-family: DejaVu Sans, serif;
    }
  </style>
</head>

<body>
  <div class="main">
    <table class="heading">
      <tr>
        <td>
          @if (!empty($bs->logo))
            <img loading="lazy" src="{{ public_path('assets/img/' . $bs->logo) }}" height="40" class="d-inline-block">
          @else
            <img loading="lazy" src="{{ public_path('assets/admin/img/noimage.jpg') }}" height="40" class="d-inline-block">
          @endif
        </td>
        <td class="text-right strong invoice-heading">{{ __('INVOICE') }}</td>
      </tr>
    </table>
    <div class="header">
      <div class="ml-20">
        <table class="text-left">
          <tr>
            <td class="strong small gry-color">{{ __('Bill to') . ':' }}</td>
          </tr>
          <tr>
            <td class="strong">{{ ucfirst($user->first_name) . ' ' . ucfirst($user->last_name) }}</td>
          </tr>
          <tr>
            <td class="gry-color small"><strong>{{ __('Username') . ':' }} </strong>{{ $user->username }}</td>
          </tr>
          <tr>
            <td class="gry-color small"><strong>{{ __('Email') . ':' }} </strong> {{ $user->email }}</td>
          </tr>
        </table>
      </div>
      <div class="order-details">
        <table class="text-right">
          <tr>
            <td class="strong">{{ __('Order Details') . ':' }}</td>
          </tr>
          <tr>
            <td class="gry-color small"><strong>{{ __('Invoice Number') . ':' }}</strong> #{{ $membership->id }}</td>
          </tr>
          <tr>
            <td class="gry-color small"><strong>Total:</strong>
              {{ $membership->currency_symbol }}{{ number_format($membership->price, 2) }}
            </td>
          </tr>
          <tr>
            <td class="gry-color small"><strong>{{ __('Payment Method') . ':' }}</strong>
              {{ $membership->payment_method ?? '-' }}</td>
          </tr>
          <tr>
            <td class="gry-color small"><strong>{{ __('Payment Status') . ':' }}</strong>{{ __('Completed') }}</td>
          </tr>
          <tr>
            <td class="gry-color small"><strong>{{ __('Order Date') . ':' }}</strong>
              {{ $membership->created_at ? $membership->created_at->format('d/m/Y') : '' }}</td>
          </tr>
        </table>
      </div>
    </div>

    <div class="package-info">
      <table class="padding text-left small border-bottom">
        <thead>
          <tr class="gry-color info-titles">
            <th width="20%">{{ __('Package Title') }}</th>
            <th width="20%">{{ __('Term') }}</th>
            <th width="20%">{{ __('Start Date') }}</th>
            <th width="20%">{{ __('Expire Date') }}</th>
            <th width="20%">{{ __('Max Subusers') }}</th>
            <th width="20%">{{ __('Total') }}</th>
          </tr>
        </thead>
        <tbody class="strong">
          <tr class="text-center">
            <td>{{ $package->title }}</td>
            <td>{{ ucfirst($package->term) }}</td>
            <td>{{ $membership->start_date }}</td>
            <td>{{ $membership->expire_date }}</td>
            <td>{{ $package->max_subusers }}</td>
            <td>{{ $membership->currency_symbol }}{{ number_format($membership->price, 2) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <table class="mt-80">
      <tr>
        <td class="text-right regards">{{ __('Thanks & Regards') . ',' }}</td>
      </tr>
      <tr>
        <td class="text-right strong regards">{{ $bs->website_title ?? 'Your Company' }}</td>
      </tr>
    </table>
  </div>
</body>
</html> 