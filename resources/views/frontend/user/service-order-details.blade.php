@extends('frontend.layout')

@php $title = __('Service Order Details'); @endphp

@section('pageHeading')
  {{ $title }}
@endsection

@section('content')
  @includeIf('frontend.partials.breadcrumb', ['breadcrumb' => $breadcrumb, 'title' => $title])

  <!--====== Start Service Order Details ======-->
  <section class="user-dashboard pt-100 pb-60">
    <div class="container">
      <div class="row">
        @includeIf('frontend.user.side-navbar')

        <div class="col-lg-9">
          <div class="row">
            <div class="col-lg-12">
              <div class="user-profile-details mb-40">
                <div class="order-details">
                  <div class="title">
                    <h4>{{ __('Details') }}</h4>
                  </div>

                  <div class="view-order-page">
                    <div class="order-info-area">
                      <div class="row align-items-center mb-3">
                        <div class="d-flex align-items-center mb-4" style="gap: 1rem;">
                          @php
                            $avatar = null;
                            if (!empty($customerAvatar)) {
                              // If subuser is set, use subuser path, else user path
                              if ($orderInfo->subuser_id && $customerAvatar) {
                                $avatar = asset('assets/img/subusers/' . $customerAvatar);
                              } else {
                                $avatar = asset('assets/img/users/' . $customerAvatar);
                              }
                            } else {
                              $avatar = asset('assets/img/users/profile.jpeg');
                            }
                          @endphp
                          <img src="{{ $avatar }}" alt="avatar" class="rounded-circle border" width="56" height="56" style="object-fit:cover;">
                          <span class="fw-bold" style="font-size:1.15rem; letter-spacing:0.5px;">{{ $customerUsername }}</span>
                        </div>
                      </div>
                      <div class="row align-items-center">
                        <div class="col-lg-8">
                          <div class="order-info">
                            <h3>{{ __('Order') . ': #' . $orderInfo->order_number }}</h3>
                            <p>{{ __('Order Date') . ': ' . date_format($orderInfo->created_at, 'M d, Y') }}</p>
                          </div>
                        </div>

                        @if (!is_null($orderInfo->invoice))
                          @php
                            $slug = @$serviceInfo->slug;
                            $date = $orderInfo->created_at->toDateString();
                          @endphp

                          <div class="col-lg-4">
                            <div class="download">
                              <a href="{{ asset('assets/file/invoices/order-invoices/' . $orderInfo->invoice) }}"
                                download="{{ $slug . '-' . $date . '.pdf' }}" class="btn btn-lg btn-primary radius-sm">
                                <i class="fas fa-download"></i> {{ __('Invoice') }}
                              </a>
                            </div>
                          </div>
                        @endif
                      </div>
                    </div>
                  </div>

                  <div class="billing-add-area mb-0">
                    @php
                      $position = $orderInfo->currency_symbol_position;
                      $symbol = $orderInfo->currency_symbol;
                    @endphp

                    <div class="row">
                      <div class="col-md-6">
                        <div class="main-info">
                          <h5>{{ __('Information') }}</h5>
                          <ul class="list list-unstyled">
                            <li>
                              <p><span>{{ __('Name') . ':' }}</span>{{ $displayName ?: 'N/A' }}</p>
                            </li>

                            <li>
                              <p><span>{{ __('Email') . ':' }}</span>{{ $displayEmail ?: 'N/A' }}</p>
                            </li>
                            @php $informations = json_decode($orderInfo->informations); @endphp

                            @if (!is_null($informations))
                              @foreach ($informations as $key => $information)
                                @php
                                  $str = preg_replace('/_/', ' ', $key);
                                  $label = mb_convert_case($str, MB_CASE_TITLE);
                                @endphp
                                @if (is_object($information) && isset($information->type) && $information->type == 8)
                                  <li>
                                    <p>
                                      <span>{{ __($label) . ':' }}</span>
                                      <a href="{{ asset('assets/file/zip-files/' . $information->value) }}" target="_blank">{{ $information->value }}</a>
                                    </p>
                                  </li>
                                @elseif (is_object($information) && isset($information->type) && $information->type == 4)
                                  <li>
                                    <p>
                                      <span>{{ __($label) . ':' }}</span>
                                      {{-- handle type 4 as needed, e.g., checkbox or other --}}
                                      {{ $information->value ?? '' }}
                                    </p>
                                  </li>
                                @else
                                  <li>
                                    <p>
                                      <span>{{ __($label) . ':' }}</span>
                                      {{ is_object($information) && isset($information->value) ? $information->value : $information }}
                                    </p>
                                  </li>
                                @endif
                              @endforeach
                            @endif
                          </ul>
                        </div>
                      </div>

                      <div class="col-md-6">
                        <div class="main-info">
                          <h5>{{ __('Order Information') }}</h5>
                          <ul class="list list-unstyled">
                            <li>
                              <p><span>{{ __('Service') . ':' }}</span>{{ @$serviceInfo->title }}</p>
                            </li>

                            @if (!is_null($packageTitle))
                              <li>
                                <p><span>{{ __('Package') . ':' }}</span>{{ $packageTitle }}
                                  ({{ $position == 'left' ? $symbol : '' }}{{ formatPrice(number_format($orderInfo->package_price, 2)) }}{{ $position == 'right' ? $symbol : '' }})
                                </p>
                              </li>
                            @endif

                            @if (!is_null($orderInfo->addons))
                              @php $addons = json_decode($orderInfo->addons); @endphp

                              <li>
                                <span class="d-block">{{ __('Addons') . ':' }}</span>
                                <div class="ps-3">
                                  @php
                                    $addonTotal = 0;
                                  @endphp
                                  @foreach ($addons as $addon)
                                    @php
                                      $addonId = $addon->id;

                                      $serviceAddon = \App\Models\ClientService\ServiceAddon::query()->find($addonId);
                                    @endphp

                                    <span>
                                      {{ $loop->iteration . '.' }} {{ $serviceAddon->name }}
                                      ({{ $position == 'left' ? $symbol : '' }}{{ formatPrice($addon->price) }}{{ $position == 'right' ? $symbol : '' }})
                                    </span>
                                    <br>
                                    @php
                                      $addonTotal = $addonTotal + $addon->price;
                                    @endphp
                                  @endforeach
                                </div>
                                <hr class="mt-1 mb-1">
                                <p>
                                  <span>{{ __('Total' . ':') }}</span>
                                  {{ $position == 'left' ? $symbol : '' }}{{ formatPrice($addonTotal) }}{{ $position == 'right' ? $symbol : '' }}
                                </p>
                              </li>
                            @endif
                            <li>
                              <p>
                                <span>{{ __('Tax') }} ({{ $orderInfo->tax_percentage . '%' }}) :
                                </span>{{ $position == 'left' ? $symbol : '' }}{{ formatPrice(number_format($orderInfo->tax, 2)) }}{{ $position == 'right' ? $symbol : '' }}
                              </p>
                            </li>

                            @if (is_null($orderInfo->grand_total))
                              <li>
                                <p><span>{{ __('Total') . ':' }}</span>{{ __('Price Requested') }}</p>
                              </li>
                            @else
                              <li>
                                <p>
                                  <span>{{ __('Total') . ':' }}</span>{{ $position == 'left' ? $symbol : '' }}{{ formatPrice(number_format($orderInfo->grand_total, 2)) }}{{ $position == 'right' ? $symbol : '' }}
                                </p>
                              </li>
                            @endif

                            @if (!is_null($orderInfo->payment_method))
                              <li>
                                <p><span>{{ __('Paid via') . ':' }}</span>{{ $orderInfo->payment_method }}</p>
                              </li>
                            @endif

                            <li>
                              <p><span>{{ __('Payment Status') . ':' }}</span>
                                @if ($orderInfo->payment_status == 'completed')
                                  <span class="badge bg-success px-2 py-1">{{ __('Completed') }}</span>
                                @elseif ($orderInfo->payment_status == 'pending')
                                  <span class="badge bg-warning px-2 py-1">{{ __('Pending') }}</span>
                                @else
                                  <span class="badge bg-danger px-2 py-1">{{ __('Rejected') }}</span>
                                @endif
                              </p>
                            </li>

                            <li>
                              <p><span>{{ __('Order Status') . ':' }}</span>
                                @if ($orderInfo->order_status == 'pending')
                                  <span class="badge bg-warning px-2 py-1">{{ __('Pending') }}</span>
                                @elseif ($orderInfo->order_status == 'completed')
                                  <span class="badge bg-success px-2 py-1">{{ __('Completed') }}</span>
                                @else
                                  <span class="badge bg-danger px-2 py-1">{{ __('Rejected') }}</span>
                                @endif
                              </p>
                            </li>
                          </ul>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
  <!--====== End Service Order Details ======-->
@endsection
