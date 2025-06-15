@extends('backend.layout')

{{-- this style will be applied when the direction of language is right-to-left --}}
@includeIf('backend.partials.rtl-style')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Hero Section') }}</h4>
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
        <a href="#">{{ __('Home Page') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Hero Section') }}</a>
      </li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col">
              <div class="card-title">{{ __('Section Image') }}</div>
            </div>
          </div>
        </div>

        <div class="card-body">
          <div class="row">
            <div class="col-lg-8 offset-lg-2">
              <form id="heroImgForm" action="{{ route('admin.home_page.update_hero_img') }}" method="POST"
                enctype="multipart/form-data">
                @csrf
                <div class="form-group">
                  <label for="">{{ __('Background Image') . '*' }}</label>
                  <br>
                  <div class="thumb-preview">
                    @if (empty($heroImgs->hero_bg_img))
                      <img src="{{ asset('assets/img/noimage.jpg') }}" alt="..." class="uploaded-background-img">
                    @else
                      <img src="{{ asset('assets/img/' . $heroImgs->hero_bg_img) }}" alt="image"
                        class="uploaded-background-img">
                    @endif
                  </div>

                  <div class="mt-3">
                    <div role="button" class="btn btn-primary btn-sm upload-btn">
                      {{ __('Choose Image') }}
                      <input type="file" class="background-img-input" name="hero_bg_img">
                    </div>
                  </div>
                  @error('hero_bg_img')
                    <p class="mt-2 mb-0 text-danger">{{ $message }}</p>
                  @enderror
                </div>
                <div class="form-group">
                  <label for="">{{ __('Image') . '*' }}</label>
                  <br>
                  <div class="thumb-preview">
                    @if (empty($heroImgs->hero_static_img))
                      <img src="{{ asset('assets/img/noimage.jpg') }}" alt="..." class="uploaded-img">
                    @else
                      <img src="{{ asset('assets/img/' . $heroImgs->hero_static_img) }}" alt="image"
                        class="uploaded-img">
                    @endif
                  </div>

                  <div class="mt-3">
                    <div role="button" class="btn btn-primary btn-sm upload-btn">
                      {{ __('Choose Image') }}
                      <input type="file" class="img-input" name="image">
                    </div>
                  </div>
                  @error('image')
                    <p class="mt-2 mb-0 text-danger">{{ $message }}</p>
                  @enderror
                </div>
                @if ($settings->theme_version == 2)
                  <div class="form-group">
                    <label for="">{{ __('Youtube Video URL') }}</label>
                    <input type="text" class="form-control" name="hero_video_url"
                      placeholder="Enter youtube video url" value="{{ $heroImgs->hero_video_url }}">
                    @error('hero_video_url')
                      <p class="mt-2 mb-0 text-danger">{{ $message }}</p>
                    @enderror
                  </div>
                @endif
              </form>
            </div>
          </div>
        </div>

        <div class="card-footer">
          <div class="row">
            <div class="col-12 text-center">
              <button type="submit" form="heroImgForm" class="btn btn-success">
                {{ __('Update') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-6">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-lg-9">
              <div class="card-title">{{ __('Hero Section Information') }}</div>
            </div>

            <div class="col-lg-3">
              @includeIf('backend.partials.languages')
            </div>
          </div>
        </div>

        <div class="card-body">
          <div class="row">
            <div class="col-lg-12">
              <form id="staticForm"
                action="{{ route('admin.home_page.update_hero_info', ['language' => request()->input('language')]) }}"
                method="post">
                @csrf
                <div class="form-group">
                  <label for="">{{ __('Title') }}</label>
                  <input type="text" class="form-control" name="title" placeholder="Enter Title"
                    value="@if (!empty($heroInfo)) {{ $heroInfo->title }} @endif">
                </div>

                <div class="form-group">
                  <label for="">{{ __('Text') }}</label>
                  <textarea class="form-control" name="text" rows="5" placeholder="Enter Text">
@if (!empty($heroInfo))
{{ $heroInfo->text }}
@endif
</textarea>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="card-footer">
          <div class="row">
            <div class="col-12 text-center">
              <button type="submit" form="staticForm" class="btn btn-success">
                {{ __('Update') }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
