@extends('frontend.layout')

@section('pageHeading')
  {{ __('Success') }}
@endsection

<style>
    .customer-offer-success-bg {
      background: linear-gradient(135deg, #f8fafc 0%, #e3fcec 100%);
      min-height: 80vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .customer-offer-success-card {
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 6px 32px rgba(40,167,69,0.10), 0 1.5px 6px rgba(0,0,0,0.04);
      padding: 2.5rem 2rem 2rem 2rem;
      max-width: 540px;
      margin: 0 auto;
      text-align: center;
    }
    .customer-offer-success-icon {
      font-size: 4.5rem;
      color: #22c55e;
      margin-bottom: 1.2rem;
      text-shadow: 0 2px 12px rgba(34,197,94,0.12);
    }
    .customer-offer-success-title {
      font-size: 2.3rem;
      font-weight: 800;
      letter-spacing: 1px;
      color: #222;
      margin-bottom: 0.5rem;
    }
    .customer-offer-success-desc {
      font-size: 1.15rem;
      color: #444;
      margin-bottom: 1.2rem;
    }
    .customer-offer-success-details {
      display: flex;
      justify-content: space-between;
      gap: 1.5rem;
      margin: 2rem 0 1.5rem 0;
      flex-wrap: wrap;
    }
    .customer-offer-success-details .card {
      background: #f6fdf9;
      border-radius: 12px;
      box-shadow: 0 1.5px 8px rgba(34,197,94,0.06);
      border: none;
      flex: 1 1 220px;
      min-width: 200px;
    }
    .customer-offer-success-details .card-body {
      padding: 1.1rem 1.2rem;
    }
    .customer-offer-success-next {
      background: #e0f2fe;
      border-radius: 10px;
      padding: 1.2rem 1.5rem;
      margin-bottom: 1.5rem;
      color: #0369a1;
      font-size: 1.05rem;
      box-shadow: 0 1.5px 8px rgba(2,132,199,0.06);
      text-align: left;
    }
    .customer-offer-success-next h6 {
      color: #0369a1;
      font-weight: 700;
      margin-bottom: 0.7rem;
      font-size: 1.1rem;
    }
    .customer-offer-success-btns {
      display: flex;
      justify-content: center;
      gap: 1.2rem;
      margin-top: 1.2rem;
    }
    .customer-offer-success-btns .btn {
      font-size: 1.08rem;
      padding: 0.7rem 1.7rem;
      border-radius: 24px;
      font-weight: 600;
      box-shadow: 0 2px 8px rgba(40,167,69,0.08);
      transition: background 0.18s, box-shadow 0.18s;
    }
    .customer-offer-success-btns .btn-primary {
      background: linear-gradient(90deg, #22c55e 80%, #4ade80 100%);
      border: none;
      color: #fff;
    }
    .customer-offer-success-btns .btn-primary:hover {
      background: linear-gradient(90deg, #16a34a 80%, #22d3ee 100%);
      color: #fff;
    }
    .customer-offer-success-btns .btn-outline-secondary {
      border: 2px solid #a3a3a3;
      color: #222;
      background: #f8fafc;
    }
    .customer-offer-success-btns .btn-outline-secondary:hover {
      background: #e5e7eb;
      color: #111;
    }
  </style>

@section('content')

  @includeIf('frontend.partials.breadcrumb', ['breadcrumb' => $breadcrumb ?? '', 'title' => __('Success')])


  <div class="customer-offer-success-bg mt-2">
    <div class="customer-offer-success-card">
      <div class="customer-offer-success-icon">
        <i class="far fa-check-circle"></i>
      </div>
      <div class="customer-offer-success-title">{{ __('SUCCESS!') }}</div>
      <div class="customer-offer-success-desc">
        {{ __('Your customer offer order has been placed successfully.') }}<br>
        {{ __('Once the admin approves your order, you will receive an email with an invoice.') }}
      </div>
      <div class="customer-offer-success-details">
        <div class="card">
          <div class="card-body">
            <h6 class="fw-bold mb-2">{{ __('Offer Details') }}</h6>
            <p class="mb-1"><strong>{{ __('Title:') }}</strong> {{ $offer->title }}</p>
            <p class="mb-1"><strong>{{ __('Price:') }}</strong> {{ $offer->formatted_price }}</p>
            <p class="mb-0"><strong>{{ __('Seller:') }}</strong> {{ $offer->seller->username }}</p>
          </div>
        </div>
        <div class="card">
          <div class="card-body">
            <h6 class="fw-bold mb-2">{{ __('Order Information') }}</h6>
            @if($offer->acceptedOrder)
              <p class="mb-1"><strong>{{ __('Order #:') }}</strong> {{ $offer->acceptedOrder->order_number }}</p>
              <p class="mb-1"><strong>{{ __('Status:') }}</strong>
                <span class="badge bg-{{ $offer->acceptedOrder->payment_status === 'completed' ? 'success' : 'warning' }}">
                  {{ ucfirst($offer->acceptedOrder->payment_status) }}
                </span>
              </p>
              <p class="mb-0"><strong>{{ __('Date:') }}</strong> {{ $offer->acceptedOrder->created_at->format('M d, Y H:i') }}</p>
            @else
              <p class="mb-0 text-muted">{{ __('Order details will be available soon') }}</p>
            @endif
          </div>
        </div>
      </div>
      <div class="customer-offer-success-next">
        <h6><i class="fas fa-info-circle me-2"></i>{{ __('What\'s Next?') }}</h6>
        <ul class="mb-0">
          <li>{{ __('You will receive a confirmation email shortly') }}</li>
          <li>{{ __('The seller will review your order and requirements') }}</li>
          <li>{{ __('You can track your order progress in your dashboard') }}</li>
          <li>{{ __('Feel free to contact the seller if you have any questions') }}</li>
        </ul>
      </div>
      <div class="customer-offer-success-btns">
        <a href="{{ route('user.discussions') }}" class="btn btn-primary">
          <i class="fas fa-comments me-2"></i>{{ __('Back to Discussions') }}
        </a>
        <a href="{{ route('user.dashboard') }}" class="btn btn-outline-secondary">
          <i class="fas fa-tachometer-alt me-2"></i>{{ __('Go to Dashboard') }}
        </a>
      </div>
    </div>
  </div>
@endsection 