@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    {{ __('Customer Offer Order Details') }}
                </div>
                <div class="card-body">
                    <h5>{{ __('Order Number:') }} <span class="fw-bold">#{{ $order->id }}</span></h5>
                    <p><strong>{{ __('Offer Title:') }}</strong> {{ $order->title ?? '-' }}</p>
                    <p><strong>{{ __('Amount:') }}</strong> {{ $order->amount ?? '-' }}</p>
                    <p><strong>{{ __('Status:') }}</strong> {{ ucfirst($order->status) }}</p>
                    <a href="{{ route('user.dashboard') }}" class="btn btn-outline-primary mt-3">{{ __('Back to Dashboard') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection 