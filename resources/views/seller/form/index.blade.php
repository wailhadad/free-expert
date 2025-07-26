@extends('seller.layout')

@section('style')
<style>
  .alert {
    position: relative;
    padding-right: 3rem;
  }
  
  .alert .close {
    position: absolute;
    top: 0;
    right: 0;
    padding: 0.75rem 1.25rem;
    color: inherit;
    background: transparent;
    border: 0;
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
    text-shadow: 0 1px 0 #fff;
    opacity: 0.5;
    cursor: pointer;
  }
  
  .alert .close:hover {
    opacity: 0.75;
  }
  
  .alert-dismissible .close {
    position: absolute;
    top: 0;
    right: 0;
    z-index: 2;
    padding: 0.75rem 1.25rem;
    color: inherit;
  }
  
  /* Form row that exceeds package limit */
  .form-exceeds-limit {
    background-color:rgb(222, 220, 220) !important;
    border-left: 4px solid #dc3545 !important;
    opacity: 0.95;
    transition: all 0.3s ease;
  }
  
  .form-exceeds-limit:hover {
    background-color: #fafafa !important;
    cursor: not-allowed;
    transform: translateX(2px);
  }
  
  .form-exceeds-limit td {
    position: relative;
    color: #333 !important;
  }
  
  .form-exceeds-limit a {
    color: #007bff !important;
  }
  
  .form-exceeds-limit a:hover {
    color: #0056b3 !important;
  }

  /* Disabled action buttons */
  .disabled-action {
    opacity: 0.5 !important;
    pointer-events: none !important;
    cursor: not-allowed !important;
  }
  
  .disabled-action:hover {
    background: none !important;
    color: inherit !important;
    border-color: inherit !important;
  }
  
  .disabled-action.editBtn {
    border-color: #ffc107 !important;
    color: #ffc107 !important;
  }
  
  .disabled-action.deleteBtn {
    border-color: #dc3545 !important;
    color: #dc3545 !important;
  }
  
  .disabled-action.btn-info {
    border-color: #17a2b8 !important;
    color: #17a2b8 !important;
    background-color: transparent !important;
  }
  
  .disabled-action.btn-info:hover {
    background-color: transparent !important;
    color: #17a2b8 !important;
    border-color: #17a2b8 !important;
  }

  /* Info box styling */
  .form-info-box {
    background-color: #2a2f3a;
    color: #fff;
    border-color: #3b4252;
  }
  .form-info-box .badge-light {
    background-color: #4a505d !important;
    color: #fff !important;
  }
  .form-info-box .text-dark {
    color: #fff !important;
  }

  /* Static info box styling */
  .static-info-box {
    background-color: #2a2f3a;
    color: #fff;
    border: 1px solid #3b4252;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
  }

  .static-warning-box {
    background-color: #2a2f3a;
    color: #fff;
    border: 1px solid #dc3545;
    border-radius: 0.375rem;
    padding: 1rem;
    margin-bottom: 1rem;
  }

  /* Service usage badges styling */
  .badge-success {
    background-color: #28a745 !important;
    color: #fff !important;
  }
  
  .badge-warning {
    background-color: #ffc107 !important;
    color: #212529 !important;
  }
  
  .badge-secondary {
    background-color: #6c757d !important;
    color: #fff !important;
  }
</style>
@endsection

{{-- this style will be applied when the direction of language is right-to-left --}}
@includeIf('seller.partials.rtl-style')

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Forms') }}</h4>
    <ul class="breadcrumbs">
      <li class="nav-home">
        <a href="{{ route('seller.dashboard') }}">
          <i class="flaticon-home"></i>
        </a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Service Management') }}</a>
      </li>
      <li class="separator">
        <i class="flaticon-right-arrow"></i>
      </li>
      <li class="nav-item">
        <a href="#">{{ __('Forms') }}</a>
      </li>
    </ul>
  </div>

  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-lg-4">
              <div class="card-title d-inline-block">{{ __('All Forms') }}</div>
            </div>

            <div class="col-lg-3">
              @includeIf('seller.partials.languages')
            </div>

            <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
              @if($formLimit > 0)
                <a href="#" data-bs-toggle="modal" data-bs-target="#createModal"
                  class="btn btn-primary btn-sm float-lg-right float-left">
                  <i class="fas fa-plus"></i> {{ __('Add Form') }}
                </a>
              @else
                <span class="text-muted float-lg-right float-left">
                  <i class="fas fa-info-circle"></i> {{ __('Forms not allowed in current package') }}
                </span>
              @endif
            </div>
          </div>
        </div>

        <div class="card-body">
          <!-- Form Info Box -->
          @if($formLimit > 0)
            <div class="form-info-box static-info-box mb-4">
              <i class="fas fa-file-alt me-2"></i>
              <strong>{{ __('Form Profiles') }}:</strong> 
              <span class="badge badge-light text-dark">{{ $totalForms }}</span> 
              <span class="mx-1 fw-bold" style="font-size: 1.1em;">/</span> 
              <span class="badge badge-light text-dark">{{ $formLimit }}</span>
              @if($isPrioritized)
                <span class="text-warning ms-2">
                  <i class="fas fa-exclamation-triangle"></i>
                  {{ __('Prioritization active') }}
                </span>
                <div class="mt-2 small">
                  <strong>{{ __('Priority Sequence') }}:</strong>
                  <span class="badge badge-light text-dark me-1">1. {{ __('Active Services') }}</span>
                  <span class="badge badge-light text-dark me-1">2. {{ __('Inactive Services') }}</span>
                  <span class="badge badge-light text-dark">3. {{ __('Others') }}</span>
                </div>
                <div class="mt-2 small text-danger">
                  <i class="fas fa-info-circle"></i>
                  {{ __('Forms exceeding your package limit will have disabled actions.') }}
                </div>
              @else
                <span class="text-success ms-2">
                  <i class="fas fa-check-circle"></i>
                  {{ __('All forms are within your package limit.') }}
                </span>
              @endif
            </div>
          @else
            <div class="form-info-box static-warning-box mb-4">
              <i class="fas fa-exclamation-triangle me-2"></i>
              <strong>{{ __('Form Profiles') }}:</strong> 
              <span class="badge badge-light text-dark">{{ $totalForms }}</span> 
              <span class="mx-1 fw-bold" style="font-size: 1.1em;">/</span> 
              <span class="badge badge-light text-dark me-2">{{ $formLimit }}</span>
              <span class="text-danger ms-2" style="margin-left: 15px;">
                <i class="fas fa-times-circle"></i>
                {{ __('No forms allowed in your current package.') }}
              </span>
              <div class="mt-2 small">
                <strong>{{ __('Priority Sequence') }}:</strong>
                <span class="badge badge-light text-dark me-1">1. {{ __('Active Services') }}</span>
                <span class="badge badge-light text-dark me-1">2. {{ __('Inactive Services') }}</span>
                <span class="badge badge-light text-dark">3. {{ __('Others') }}</span>
              </div>
              <div class="mt-2 small text-danger">
                <i class="fas fa-info-circle"></i>
                {{ __('All forms will have disabled actions.') }}
              </div>
            </div>
          @endif

          @if (session()->has('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert">
              <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="position: absolute; top: 0; right: 0; padding: 0.75rem 1.25rem;">
                <span aria-hidden="true">&times;</span>
              </button>
              <i class="fas fa-exclamation-triangle mr-2"></i>
              {{ session()->get('error') }}
            </div>
          @endif

          <div class="row">
            <div class="col-lg-12">
              @if (count($forms) == 0)
                <h3 class="text-center mt-2">{{ __('NO FORM FOUND') . '!' }}</h3>
              @else
                <div class="table-responsive">
                  <table class="table table-striped mt-3" id="basic-datatables">
                    <thead>
                      <tr>
                        <th scope="col">#</th>
                        <th scope="col">{{ __('Name') }}</th>
                        <th scope="col">{{ __('Service Usage') }}</th>
                        <th scope="col">{{ __('Form Inputs') }}</th>
                        <th scope="col">{{ __('Actions') }}</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($forms as $form)
                        @php
                          // Check if this form is within the limit
                          $isWithinLimit = $formsWithinLimit->contains('id', $form->id);
                          $disabledClass = $isWithinLimit ? '' : 'disabled-action';
                          $disabledAttr = $isWithinLimit ? '' : 'disabled';
                          $cursorStyle = $isWithinLimit ? '' : 'cursor: not-allowed;';
                          $rowClass = $isWithinLimit ? '' : 'form-exceeds-limit';
                        @endphp
                        <tr class="{{ $rowClass }}">
                          <td>{{ $loop->iteration }}</td>
                          <td>{{ $form->name }}</td>
                          <td>
                            @php
                              $activeServices = $form->serviceContents->where('service.service_status', 1)->count();
                              $inactiveServices = $form->serviceContents->where('service.service_status', 0)->count();
                            @endphp
                            @if($activeServices > 0)
                              <span class="badge badge-success me-1">
                                <i class="fas fa-check-circle"></i>
                                {{ $activeServices }} {{ __('Active') }}
                              </span>
                            @endif
                            @if($inactiveServices > 0)
                              <span class="badge badge-warning me-1">
                                <i class="fas fa-pause-circle"></i>
                                {{ $inactiveServices }} {{ __('Inactive') }}
                              </span>
                            @endif
                            @if($activeServices == 0 && $inactiveServices == 0)
                              <span class="badge badge-secondary">
                                <i class="fas fa-times-circle"></i>
                                {{ __('Not Used') }}
                              </span>
                            @endif
                          </td>
                          <td>
                            <a href="{{ route('seller.service_management.form.input', ['id' => $form->id, 'language' => request()->input('language')]) }}"
                              class="btn btn-sm btn-info {{ $disabledClass }}"
                              data-bs-toggle="tooltip" data-bs-placement="top"
                              title="{{ $isWithinLimit ? __('Manage Form Inputs') : __('Cannot manage - exceeds package limit') }}"
                              {{ $disabledAttr }}>
                              {{ __('Manage') }}
                            </a>
                            @if (!$isWithinLimit)
                              <div class="mt-1">
                                <small class="text-danger">
                                  <i class="fas fa-exclamation-triangle"></i>
                                  {{ __('Disabled') }}
                                </small>
                              </div>
                            @endif
                          </td>
                          <td>
                            <a class="btn btn-secondary btn-sm editBtn mb-1 {{ $disabledClass }}"
                              href="#" data-bs-toggle="modal" data-bs-target="#editModal" data-id="{{ $form->id }}"
                              data-name="{{ $form->name }}"
                              data-bs-placement="top"
                              title="{{ $isWithinLimit ? __('Edit') : __('Cannot edit - exceeds package limit') }}"
                              {{ $disabledAttr }}>
                              <span class="btn-label">
                                <i class="fas fa-edit"></i>
                              </span>
                            </a>

                            <form class="deleteForm d-inline-block"
                              action="{{ route('seller.service_management.delete_form', ['id' => $form->id]) }}"
                              method="post">
                              @csrf
                              <button type="submit" class="btn btn-danger btn-sm deleteBtn mb-1 {{ $disabledClass }}"
                                data-bs-toggle="tooltip" data-bs-placement="top"
                                title="{{ $isWithinLimit ? __('Delete') : __('Cannot delete - exceeds package limit') }}"
                                {{ $disabledAttr }}>
                                <span class="btn-label">
                                  <i class="fas fa-trash"></i>
                                </span>
                              </button>
                            </form>
                            @if (!$isWithinLimit)
                              <div class="mt-1">
                                <small class="text-danger">
                                  <i class="fas fa-exclamation-triangle"></i>
                                  {{ __('Exceeds The Current Membership Limit') }}
                                </small>
                              </div>
                            @endif
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              @endif
            </div>
          </div>
        </div>

        <div class="card-footer"></div>
      </div>
    </div>
  </div>

  {{-- create modal --}}
  @includeIf('seller.form.create')

  {{-- edit modal --}}
  @includeIf('seller.form.edit')
@endsection

@section('script')
<script>
  $(document).ready(function() {
    // No auto-dismiss for static info boxes
    // They will remain visible permanently
  });
</script>
@endsection
