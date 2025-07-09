@extends('backend.layout')
@section('content')
<div class="container mt-4">
  <div class="card">
    <div class="card-header"><h4>{{ __('Subuser Details') }}</h4></div>
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
      <dl class="row">
        <dt class="col-sm-3">{{ __('Username') }}</dt>
        <dd class="col-sm-9">{{ $subuser->username }}</dd>
        <dt class="col-sm-3">{{ __('Full Name') }}</dt>
        <dd class="col-sm-9">{{ $subuser->full_name }}</dd>
        <dt class="col-sm-3">{{ __('Status') }}</dt>
        <dd class="col-sm-9">{{ $subuser->status ? __('Active') : __('Inactive') }}</dd>
        <dt class="col-sm-3">{{ __('Phone') }}</dt>
        <dd class="col-sm-9">{{ $subuser->phone_number }}</dd>
        <dt class="col-sm-3">{{ __('Address') }}</dt>
        <dd class="col-sm-9">{{ $subuser->address }}</dd>
        <dt class="col-sm-3">{{ __('City') }}</dt>
        <dd class="col-sm-9">{{ $subuser->city }}</dd>
        <dt class="col-sm-3">{{ __('State') }}</dt>
        <dd class="col-sm-9">{{ $subuser->state }}</dd>
        <dt class="col-sm-3">{{ __('Country') }}</dt>
        <dd class="col-sm-9">{{ $subuser->country }}</dd>
        <dt class="col-sm-3">{{ __('Created At') }}</dt>
        <dd class="col-sm-9">{{ $subuser->created_at }}</dd>
      </dl>
      <a href="javascript:history.back()" class="btn btn-secondary">{{ __('Back') }}</a>
    </div>
  </div>
</div>
@endsection 