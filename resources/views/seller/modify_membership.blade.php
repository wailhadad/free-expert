@extends('seller.layout')

@section('content')
<div class="container mt-4">
    <h3>{{ __('Modify Membership') }}</h3>
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <div class="card mb-4">
        <div class="card-body">
            @if($currentRequest)
                <div class="mb-3">
                    <strong>{{ __('Current Modification Request:') }}</strong><br>
                    {{ $currentRequest->package->title }}
                    <span class="badge badge-info ml-2">{{ ucfirst($currentRequest->status) }}</span>
                    <form action="{{ route('seller.modify_membership.delete') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm ml-2">{{ __('Delete Request') }}</button>
                    </form>
                </div>
            @else
                <form action="{{ route('seller.modify_membership.request') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="package_id">{{ __('Select New Package') }}</label>
                        <select name="package_id" id="package_id" class="form-control" required>
                            <option value="">-- {{ __('Choose Package') }} --</option>
                            @foreach($packages as $package)
                                <option value="{{ $package->id }}">{{ $package->title }} ({{ $package->term }}) - {{ $package->price }} {{ $seller->currency_symbol ?? '' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">{{ __('Submit Modification Request') }}</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection 