<!DOCTYPE html>
<html>

<head>
  {{-- required meta tags --}}
  <meta http-equiv="Content-Type" content="text/html" charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">

  {{-- csrf-token for ajax request --}}
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="notifiable-id" content="{{ auth('seller')->id() }}">
  <meta name="notifiable-type" content="Seller">
  <meta name="pusher-key" content="{{ config('broadcasting.connections.pusher.key') }}">
  <meta name="pusher-cluster" content="{{ config('broadcasting.connections.pusher.options.cluster') }}">

  {{-- title --}}
  <title>{{ __('Seller') . ' | ' . $websiteInfo->website_title }}</title>

  {{-- fav icon --}}
  <link rel="shortcut icon" type="image/png" href="{{ asset('assets/img/' . $websiteInfo->favicon) }}">

  {{-- include styles --}}
  @includeIf('seller.partials.styles')
  <link rel="stylesheet" href="{{ asset('assets/css/sidebar-fix.css') }}">

  {{-- additional style --}}
  @yield('style')
</head>

<body data-background-color="{{ Session::get('seller_theme_version') == 'light' ? 'white2' : 'dark' }}">
  {{-- loader start --}}
  <div class="request-loader">
    <img src="{{ asset('assets/img/loader.gif') }}" alt="loader">
  </div>
  {{-- loader end --}}

  <div class="wrapper">
    {{-- top navbar area start --}}
    @includeIf('seller.partials.top-navbar')
    {{-- top navbar area end --}}

    {{-- side navbar area start --}}
    @includeIf('seller.partials.side-navbar')
    {{-- side navbar area end --}}

    <div class="main-panel">
      <div class="content">
        <div class="page-inner">
          @yield('content')
        </div>
      </div>

      {{-- footer area start --}}
      @includeIf('seller.partials.footer')
      {{-- footer area end --}}
    </div>
  </div>

  {{-- include scripts --}}
  @includeIf('seller.partials.scripts')

  {{-- additional script --}}
  @yield('script')

  <script src="https://js.pusher.com/7.2/pusher.min.js"></script>
  <script src="{{ asset('assets/js/real-time-notifications.js') }}?v={{ time() }}&cb={{ uniqid() }}&t={{ microtime(true) }}"></script>
  @stack('scripts')

</body>

</html>
