@extends('backend.layout')

@section('content')
<div class="page-header">
  <h4 class="page-title">{{ __('Edit User Package') }}</h4>
  <ul class="breadcrumbs">
    <li class="nav-home">
      <a href="{{ route('admin.dashboard') }}">
        <i class="flaticon-home"></i>
      </a>
    </li>
    <li class="separator">
      <i class="flaticon-right-arrow"></i>
    </li>
    <li class="nav-item">
      <a href="#">{{ __('User Package Management') }}</a>
    </li>
    <li class="separator">
      <i class="flaticon-right-arrow"></i>
    </li>
    <li class="nav-item">
      <a href="#">{{ __('Edit User Package') }}</a>
    </li>
  </ul>
</div>
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-body">
        <form id="editForm" action="{{ route('admin.user_package.update') }}" method="POST">
          @csrf
          <input type="hidden" name="id" value="{{ $package->id }}">
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="title">{{ __('Package title') }}*</label>
                <input id="title" type="text" class="form-control" name="title" value="{{ $package->title }}" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="price">{{ __('Price') }} ({{ $bs->base_currency_text }})*</label>
                <input id="price" type="number" class="form-control" name="price" value="{{ $package->price }}" min="0" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="term">{{ __('Package term') }}*</label>
                <input id="term" type="text" class="form-control" name="term" value="lifetime" readonly required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="max_subusers">{{ __('Max Subusers') }}*</label>
                <input id="max_subusers" type="number" class="form-control" name="max_subusers" value="{{ $package->max_subusers }}" min="0" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="status">{{ __('Status') }}*</label>
                <select id="status" name="status" class="form-control" required>
                  <option value="1" {{ $package->status == 1 ? 'selected' : '' }}>{{ __('Active') }}</option>
                  <option value="0" {{ $package->status == 0 ? 'selected' : '' }}>{{ __('Deactive') }}</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="recommended">{{ __('Recommended') }}*</label>
                <select id="recommended" name="recommended" class="form-control" required>
                  <option value="0" {{ $package->recommended == 0 ? 'selected' : '' }}>{{ __('No') }}</option>
                  <option value="1" {{ $package->recommended == 1 ? 'selected' : '' }}>{{ __('Yes') }}</option>
                </select>
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label for="custom_features">{{ __('Custom Features') }}</label>
                <textarea id="custom_features" name="custom_features" class="form-control" rows="4">{{ $package->custom_features }}</textarea>
                <small class="form-text text-warning">Each new line will be shown as a new feature in the pricing plan</small>
              </div>
            </div>
          </div>
          <div class="form-group mt-4">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> {{ __('Update Package') }}
            </button>
            <a href="{{ route('admin.user_package.index') }}" class="btn btn-secondary">
              <i class="fas fa-arrow-left"></i> {{ __('Back to List') }}
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection 