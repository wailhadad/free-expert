@extends('backend.layout')

@section('content')
<div class="page-header">
  <h4 class="page-title">{{ __('Create User Package') }}</h4>
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
      <a href="#">{{ __('Create User Package') }}</a>
    </li>
  </ul>
</div>
<div class="row">
  <div class="col-md-12">
    <div class="card">
      <div class="card-body">
        <form action="{{ route('admin.user_package.store') }}" method="POST" enctype="multipart/form-data">
          @csrf
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="title">{{ __('Package title') }}*</label>
                <input id="title" type="text" class="form-control" name="title" placeholder="{{ __('Enter Package title') }}" value="" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="price">{{ __('Price') }}*</label>
                <input id="price" type="number" class="form-control" name="price" placeholder="{{ __('Enter Package price') }}" value="" min="0" step="0.01" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="term">{{ __('Package term') }}*</label>
                <select id="term" name="term" class="form-control" required>
                  <option value="monthly">{{ __('Monthly') }}</option>
                  <option value="yearly">{{ __('Yearly') }}</option>
                  <option value="lifetime" selected>{{ __('Lifetime') }}</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="max_subusers">{{ __('Max Subusers') }}*</label>
                <input id="max_subusers" type="number" class="form-control" name="max_subusers" placeholder="{{ __('Enter max subusers') }}" value="0" min="0" required>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="status">{{ __('Status') }}*</label>
                <select id="status" name="status" class="form-control" required>
                  <option value="1">{{ __('Active') }}</option>
                  <option value="0">{{ __('Deactive') }}</option>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label for="recommended">{{ __('Recommended') }}*</label>
                <select id="recommended" name="recommended" class="form-control" required>
                  <option value="0">{{ __('No') }}</option>
                  <option value="1">{{ __('Yes') }}</option>
                </select>
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label for="description">{{ __('Description') }}</label>
                <textarea id="description" name="description" class="form-control" rows="3" placeholder="{{ __('Enter package description') }}"></textarea>
              </div>
            </div>
            <div class="col-12">
              <div class="form-group">
                <label for="custom_features">{{ __('Custom Features') }}</label>
                <textarea id="custom_features" name="custom_features" class="form-control" rows="4" placeholder="{{ __('Enter custom features') }}"></textarea>
              </div>
            </div>
          </div>
          <div class="form-group mt-4">
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> {{ __('Create Package') }}
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