@extends('backend.layout')
@section('content')
<div class="container mt-4">
  <div class="card">
    <div class="card-header"><h4>{{ __('Edit Subuser') }}</h4></div>
    <div class="card-body">
      <div class="text-center mb-4">
        @if($subuser->image)
          <img src="{{ asset('assets/img/subusers/' . $subuser->image) }}" alt="{{ $subuser->username }}" class="rounded-circle" width="100" height="100">
        @else
          <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
            <i class="fas fa-user text-black" style="font-size: 48px;"></i>
          </div>
        @endif
      </div>
      <form action="{{ route('admin.user_management.subuser.edit', $subuser->id) }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group mb-2">
          <label>{{ __('Profile Image') }}</label>
          <input type="file" class="form-control" name="image">
        </div>
        <div class="form-group mb-2">
          <label>{{ __('Username') }}</label>
          <input type="text" class="form-control" value="{{ $subuser->username }}" readonly>
        </div>
        <div class="form-group mb-2">
          <label>{{ __('First Name') }}</label>
          <input type="text" class="form-control" name="first_name" value="{{ $subuser->first_name }}">
        </div>
        <div class="form-group mb-2">
          <label>{{ __('Last Name') }}</label>
          <input type="text" class="form-control" name="last_name" value="{{ $subuser->last_name }}">
        </div>
        <div class="form-group mb-2">
          <label>{{ __('Phone Number') }}</label>
          <input type="text" class="form-control" name="phone_number" value="{{ $subuser->phone_number }}">
        </div>
        <div class="form-group mb-2">
          <label>{{ __('Address') }}</label>
          <input type="text" class="form-control" name="address" value="{{ $subuser->address }}">
        </div>
        <div class="form-group mb-2">
          <label>{{ __('City') }}</label>
          <input type="text" class="form-control" name="city" value="{{ $subuser->city }}">
        </div>
        <div class="form-group mb-2">
          <label>{{ __('State') }}</label>
          <input type="text" class="form-control" name="state" value="{{ $subuser->state }}">
        </div>
        <div class="form-group mb-2">
          <label>{{ __('Country') }}</label>
          <input type="text" class="form-control" name="country" value="{{ $subuser->country }}">
        </div>
        <div class="form-group mb-2">
          <label>{{ __('Status') }}</label>
          <select class="form-control" name="status">
            <option value="1" @if($subuser->status) selected @endif>{{ __('Active') }}</option>
            <option value="0" @if(!$subuser->status) selected @endif>{{ __('Inactive') }}</option>
          </select>
        </div>
        <a href="javascript:history.back()" class="btn btn-secondary">{{ __('Back') }}</a>
        <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
      </form>
    </div>
  </div>
</div>
@endsection 