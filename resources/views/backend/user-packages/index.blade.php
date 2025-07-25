@extends('backend.layout')

@php
  use App\Models\Language;
  $selLang = Language::where('code', request()->input('language'))->first();
@endphp
@if (!empty($selLang) && $selLang->rtl == 1)
  @section('styles')
    <style>
      form:not(.modal-form) input,
      form:not(.modal-form) textarea,
      form:not(.modal-form) select,
      select[name='language'] {
        direction: rtl;
      }

      form:not(.modal-form) .note-editor.note-frame .note-editing-area .note-editable {
        direction: rtl;
      }
    </style>
  @endsection
@endif

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('User Packages') }}</h4>
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
        <a href="#">{{ __('User Packages') }}</a>
      </li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-lg-8">
              <form action="{{ route('admin.user_package.index') }}" method="GET">
                <div class="row">
                  <div class="col-lg-6">
                    <input name="search" type="text" class="form-control" placeholder="{{ __('Search by title') }}" value="{{ request()->input('search') }}">
                  </div>
                  <div class="col-lg-3">
                    <button type="submit" class="btn btn-primary">{{ __('Search') }}</button>
                  </div>
                </div>
              </form>
            </div>
            <div class="col-lg-4 text-right">
              <a href="#" class="btn btn-primary" data-toggle="modal" data-target="#createModal">
                <i class="fas fa-plus"></i> {{ __('Add User Package') }}
              </a>
            </div>
          </div>
        </div>

        <div class="card-body">
          <div class="row">
            <div class="col-lg-12">
              @if (count($packages) == 0)
                <h3 class="text-center">{{ __('NO PACKAGE FOUND') }}</h3>
              @else
                <div class="table-responsive">
                  <table class="table table-striped mt-3">
                    <thead>
                      <tr>
                        <th scope="col">{{ __('Title') }}</th>
                        <th scope="col">{{ __('Price') }}</th>
                        <th scope="col">{{ __('Term') }}</th>
                        <th scope="col">{{ __('Max Subusers') }}</th>
                        <th scope="col">{{ __('Status') }}</th>
                        <th scope="col">{{ __('Actions') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($packages as $package)
                        <tr>
                          <td>{{ $package->title }}</td>
                          <td>{{ $bs->base_currency_symbol }}{{ $package->price }}</td>
                          <td>{{ ucfirst($package->term) }}</td>
                          <td>{{ $package->max_subusers }}</td>
                          <td>
                            @if ($package->status == 1)
                              <span class="badge badge-success">{{ __('Active') }}</span>
                            @else
                              <span class="badge badge-danger">{{ __('Deactive') }}</span>
                            @endif
                          </td>
                          <td>
                            <a class="btn btn-sm btn-info" href="{{ route('admin.user_package.edit', $package->id) }}">
                              <i class="fas fa-edit"></i>
                            </a>
                            <button class="btn btn-sm btn-danger" data-toggle="modal" data-target="#deleteModal{{ $package->id }}">
                              <i class="fas fa-trash"></i>
                            </button>
                          </td>
                        </tr>

                        <!-- Delete Modal -->
                        <div class="modal fade" id="deleteModal{{ $package->id }}" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
                          <div class="modal-dialog" role="document">
                            <div class="modal-content">
                              <div class="modal-header">
                                <h5 class="modal-title" id="deleteModalLabel">{{ __('Delete Package') }}</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                  <span aria-hidden="true">&times;</span>
                                </button>
                              </div>
                              <div class="modal-body">
                                {{ __('Are you sure you want to delete this package?') }}
                              </div>
                              <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
                                <form action="{{ route('admin.user_package.delete') }}" method="POST" style="display: inline;">
                                  @csrf
                                  <input type="hidden" name="id" value="{{ $package->id }}">
                                  <button type="submit" class="btn btn-danger">{{ __('Delete') }}</button>
                                </form>
                              </div>
                            </div>
                          </div>
                        </div>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Create Modal -->
  <div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="createModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="createModalLabel">{{ __('Add User Package') }}</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <form id="ajaxForm" enctype="multipart/form-data" class="modal-form" action="{{ route('admin.user_package.store') }}" method="POST">
          @csrf
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label for="title">{{ __('Package title') }}*</label>
                  <input id="title" type="text" class="form-control" name="title" placeholder="{{ __('Enter Package title') }}" value="">
                  <p id="err_title" class="mb-0 text-danger em"></p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="price">{{ __('Price') }} ({{ $bs->base_currency_text }})*</label>
                  <input id="price" type="number" class="form-control" name="price" placeholder="{{ __('Enter Package price') }}" value="" step="0.01">
                  <p id="err_price" class="mb-0 text-danger em"></p>
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
                  <p id="err_term" class="mb-0 text-danger em"></p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="max_subusers">{{ __('Max Subusers') }}*</label>
                  <input id="max_subusers" type="number" class="form-control" name="max_subusers" placeholder="{{ __('Enter max subusers') }}" value="0" min="0">
                  <p id="err_max_subusers" class="mb-0 text-danger em"></p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="status">{{ __('Status') }}*</label>
                  <select id="status" name="status" class="form-control" required>
                    <option value="1">{{ __('Active') }}</option>
                    <option value="0">{{ __('Deactive') }}</option>
                  </select>
                  <p id="err_status" class="mb-0 text-danger em"></p>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label for="recommended">{{ __('Recommended') }}*</label>
                  <select id="recommended" name="recommended" class="form-control" required>
                    <option value="0">{{ __('No') }}</option>
                    <option value="1">{{ __('Yes') }}</option>
                  </select>
                  <p id="err_recommended" class="mb-0 text-danger em"></p>
                </div>
              </div>
              <div class="col-12">
                <div class="form-group">
                  <label for="custom_features">{{ __('Custom Features') }}</label>
                  <textarea id="custom_features" name="custom_features" class="form-control" rows="4" placeholder="{{ __('Enter custom features') }}"></textarea>
                  <p id="err_custom_features" class="mb-0 text-danger em"></p>
                  <small class="form-text text-warning">Each new line will be shown as a new feature in the pricing plan</small>
                </div>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">{{ __('Close') }}</button>
            <button type="submit" class="btn btn-primary">{{ __('Submit') }}</button>
          </div>
        </form>
      </div>
    </div>
  </div>
@endsection

@section('script')
<script>
$(document).ready(function() {
    $('#ajaxForm').on('submit', function(e) {
        e.preventDefault();
        var $form = $(this);
        var $btn = $form.find('button[type=submit]');
        $btn.prop('disabled', true);
        $form.find('.em').text('');
        $.ajax({
            url: $form.attr('action'),
            method: $form.attr('method'),
            data: $form.serialize(),
            success: function(response) {
                if (response.status === 'success') {
                    $('#createModal').modal('hide');
                    location.reload(); // Immediately reload to show session flash
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    $.each(errors, function(key, value) {
                        $('#err_' + key).text(value[0]);
                    });
                } else {
                    alert('An error occurred. Please try again.');
                }
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
@endsection 