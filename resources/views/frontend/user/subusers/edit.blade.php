@extends('frontend.layout')

@php $title = __('Edit Subuser'); @endphp

@section('pageHeading')
  {{ $title }}
@endsection

@section('content')
  @includeIf('frontend.partials.breadcrumb', ['breadcrumb' => $breadcrumb, 'title' => $title])

  <!--====== Start Edit Subuser Section ======-->
  <section class="user-dashboard pt-100 pb-60">
    <div class="container">
      <div class="row">
        @includeIf('frontend.user.side-navbar')

        <div class="col-lg-9">
          <div class="row">
            <div class="col-lg-12">
              <div class="user-profile-details mb-40">
                <div class="account-info">
                  <div class="title">
                    <h4>{{ __('Edit Subuser') }}: {{ $subuser->username }}</h4>
                  </div>

                  <div class="main-info">
                    <form action="{{ route('user.subusers.update', $subuser->id) }}" method="POST" enctype="multipart/form-data">
                      @csrf
                      
                      <div class="row">
                        <div class="col-md-6">
                          <div class="form-group">
                            <label for="username">{{ __('Username') }} *</label>
                            <input type="text" 
                                   class="form-control @error('username') is-invalid @enderror" 
                                   id="username" 
                                   name="username" 
                                   value="{{ old('username', $subuser->username) }}" 
                                   required>
                            @error('username')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="form-group">
                            <label for="image">{{ __('Profile Image') }}</label>
                            @if($subuser->image)
                              <div class="mb-2">
                                <img src="{{ asset('assets/img/subusers/' . $subuser->image) }}" 
                                     alt="{{ $subuser->username }}" 
                                     class="rounded" 
                                     width="80" height="80">
                                <small class="d-block text-muted">{{ __('Current image') }}</small>
                              </div>
                            @endif
                            <input type="file" 
                                   class="form-control @error('image') is-invalid @enderror" 
                                   id="image" 
                                   name="image" 
                                   accept="image/*">
                            @error('image')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">{{ __('Max size: 2MB. Supported formats: JPEG, PNG, JPG, GIF, SVG') }}</small>
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="form-group">
                            <label for="first_name">{{ __('First Name') }} *</label>
                            <input type="text" 
                                   class="form-control @error('first_name') is-invalid @enderror" 
                                   id="first_name" 
                                   name="first_name" 
                                   value="{{ old('first_name', $subuser->first_name) }}" 
                                   required>
                            @error('first_name')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="form-group">
                            <label for="last_name">{{ __('Last Name') }} *</label>
                            <input type="text" 
                                   class="form-control @error('last_name') is-invalid @enderror" 
                                   id="last_name" 
                                   name="last_name" 
                                   value="{{ old('last_name', $subuser->last_name) }}" 
                                   required>
                            @error('last_name')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="form-group">
                            <label for="phone_number">{{ __('Phone Number') }}</label>
                            <input type="text" 
                                   class="form-control @error('phone_number') is-invalid @enderror" 
                                   id="phone_number" 
                                   name="phone_number" 
                                   value="{{ old('phone_number', $subuser->phone_number) }}">
                            @error('phone_number')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="form-group">
                            <label for="country">{{ __('Country') }}</label>
                            <input type="text" 
                                   class="form-control @error('country') is-invalid @enderror" 
                                   id="country" 
                                   name="country" 
                                   value="{{ old('country', $subuser->country) }}">
                            @error('country')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="form-group">
                            <label for="state">{{ __('State/Province') }}</label>
                            <input type="text" 
                                   class="form-control @error('state') is-invalid @enderror" 
                                   id="state" 
                                   name="state" 
                                   value="{{ old('state', $subuser->state) }}">
                            @error('state')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>

                        <div class="col-md-6">
                          <div class="form-group">
                            <label for="city">{{ __('City') }}</label>
                            <input type="text" 
                                   class="form-control @error('city') is-invalid @enderror" 
                                   id="city" 
                                   name="city" 
                                   value="{{ old('city', $subuser->city) }}">
                            @error('city')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>

                        <div class="col-12">
                          <div class="form-group">
                            <label for="address">{{ __('Address') }}</label>
                            <textarea class="form-control @error('address') is-invalid @enderror" 
                                      id="address" 
                                      name="address" 
                                      rows="3">{{ old('address', $subuser->address) }}</textarea>
                            @error('address')
                              <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                          </div>
                        </div>
                      </div>

                      <div class="form-group mt-4">
                        <button type="submit" class="btn btn-primary">
                          <i class="fas fa-save"></i> {{ __('Update Subuser') }}
                        </button>
                        <a href="{{ route('user.subusers.index') }}" class="btn btn-secondary">
                          <i class="fas fa-arrow-left"></i> {{ __('Back to List') }}
                        </a>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection 