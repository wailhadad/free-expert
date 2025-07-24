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
  
  /* Service row that exceeds package limit */
  .service-exceeds-limit {
    background-color:rgb(195, 192, 192) !important;
    border-left: 4px solid #dc3545 !important;
    opacity: 0.95;
    transition: all 0.3s ease;
  }
  
  .service-exceeds-limit:hover {
    background-color:rgb(153, 152, 152) !important;
    cursor: not-allowed;
    transform: translateX(2px);
  }
  
  .service-exceeds-limit td {
    position: relative;
    color: #333 !important;
  }
  
  .service-exceeds-limit a {
    color: #007bff !important;
  }
  
  .service-exceeds-limit a:hover {
    color: #0056b3 !important;
  }
  
  /* Warning indicator for exceeds limit rows */
  .service-exceeds-limit td:first-child::before {
    content: "⚠️";
    position: absolute;
    left: -30px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 14px;
    z-index: 1;
    animation: pulse 2s infinite;
  }
  
  @keyframes pulse {
    0% { opacity: 1; }
    50% { opacity: 0.5; }
    100% { opacity: 1; }
  }
  
  /* Disabled action buttons */
  .disabled-action {
    opacity: 0.5 !important;
    pointer-events: none !important;
  }
  
  .disabled-action:hover {
    background: none !important;
    color: inherit !important;
    border-color: inherit !important;
  }
  
  /* Make the entire row have not-allowed cursor */
  .service-exceeds-limit,
  .service-exceeds-limit * {
    cursor: not-allowed !important;
  }
  
  /* Exception: allow normal cursor for the status badge and info text */
  .service-exceeds-limit .badge,
  .service-exceeds-limit small {
    cursor: default !important;
  }
  
  /* Service info box styling */
  .service-info-box {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 8px;
    padding: 15px 20px;
    margin-bottom: 20px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  }
  
  .service-info-box .badge {
    font-size: 0.9em;
    padding: 0.4em 0.8em;
  }
  
  .service-info-box .fw-bold {
    font-weight: 600 !important;
  }
</style>
@endsection

@section('content')
  <div class="page-header">
    <h4 class="page-title">{{ __('Services') }}</h4>
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
        <a href="#">{{ __('Services') }}</a>
      </li>
    </ul>
  </div>

  @if(session()->has('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert" id="errorAlert">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="position: absolute; top: 0; right: 0; padding: 0.75rem 1.25rem;">
        <span aria-hidden="true">&times;</span>
      </button>
      <i class="fas fa-exclamation-triangle mr-2"></i>
      {{ session('error') }}
    </div>
  @endif

  @if(session()->has('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert" id="successAlert">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close" style="position: absolute; top: 0; right: 0; padding: 0.75rem 1.25rem;">
        <span aria-hidden="true">&times;</span>
      </button>
      <i class="fas fa-check-circle mr-2"></i>
      {{ session('success') }}
    </div>
  @endif

  <div class="row">
    <div class="col-md-12">
      <div class="card">
        <div class="card-header">
          <div class="row">
            <div class="col-lg-4">
              <div class="card-title d-inline-block">{{ __('Services') }}</div>
            </div>

            <div class="col-lg-3">
              @includeIf('seller.partials.languages')
            </div>

            <div class="col-lg-4 offset-lg-1 mt-2 mt-lg-0">
              @if($serviceLimit > 0)
                <a href="{{ route('seller.service_management.create_service') }}"
                  class="btn btn-primary btn-sm float-right">
                  <i class="fas fa-plus"></i> {{ __('Add Service') }}
                </a>
              @else
                <span class="text-muted float-right">
                  <i class="fas fa-info-circle"></i> {{ __('Services not allowed in current package') }}
                </span>
              @endif

              <button class="btn btn-danger btn-sm float-right mr-2 d-none bulk-delete"
                data-href="{{ route('seller.service_management.bulk_delete_service') }}">
                <i class="flaticon-interface-5"></i> {{ __('Delete') }}
              </button>
            </div>
          </div>
        </div>

        <div class="card-body">
          <!-- Service Info Box -->
          @if($serviceLimit > 0)
            <div class="service-info-box">
              <i class="fas fa-cogs me-2"></i>
              <strong>{{ __('Service Profiles') }}:</strong> 
              <span class="badge badge-light text-dark">{{ $totalServices }}</span> 
              <span class="mx-1 fw-bold" style="font-size: 1.1em;">/</span> 
              <span class="badge badge-light text-dark">{{ $serviceLimit }}</span><br/>
              @if($isPrioritized)
                <span class="text-warning ms-2">
                  <i class="fas fa-exclamation-triangle"></i>
                  {{ __('Prioritization active') }}
                </span>
                <div class="mt-2 small">
                  <strong>{{ __('Priority Sequence') }}:</strong>
                  <span class="badge badge-light text-dark me-1">1. {{ __('Active orders') }}</span>
                  <span class="badge badge-light text-dark me-1">2. {{ __('Completed orders') }}</span>
                  <span class="badge badge-light text-dark">3. {{ __('Others') }}</span>
                </div>
              @endif
            </div>
          @endif

          <div class="row">
            <div class="col-lg-12">
              @php
                $data = sellerPermission(Auth::guard('seller')->user()->id, 'service');
                $data2 = sellerPermission(Auth::guard('seller')->user()->id, 'service-featured');
              @endphp
              @if ($data['status'] == 'package_false')
                <div class="alert alert-warning text-dark">
                  {{ __('Your membership is expired. Please purchase a new package / extend the current package.') }}
                </div>
              @else
                @if ($data2['status'] == 'false')
                  <div class="alert alert-warning alert-block">
                    <strong
                      class="text-dark">{{ __('Currently, you have featured ' . $data2['total_service_featured'] . ' services. ' . 'Your current package supports ' . $data2['package_support'] . ' services to make featured. Please unfeatured ' . $data2['total_service_featured'] - $data2['package_support'] . ' services to enable service management') }}</strong>
                  </div>
                @endif
              @endif

              @if (count($services) == 0)
                <h3 class="text-center mt-2">{{ __('NO SERVICE FOUND') . '!' }}</h3>
              @else
                <div class="table-responsive">
                  <table class="table table-striped mt-3" id="basic-datatables">
                    <thead>
                      <tr>
                        <th scope="col">
                          <input type="checkbox" class="bulk-check" data-val="all">
                        </th>
                        <th scope="col">{{ __('Title') }}</th>
                        <th scope="col">{{ __('Category') }}</th>
                        <th scope="col">{{ __('Packages') }}</th>
                        <th scope="col">{{ __('Addons') }}</th>
                        <th scope="col">{{ __('Featured') }}</th>
                        <th scope="col">{{ __('Actions') }}</th>
                      </tr>
                    </thead>
                    <tbody>

                      @foreach ($services as $service)
                        @php
                          // Check if this service is within the limit
                          $isWithinLimit = $servicesWithinLimit->contains($service->id);
                          $disabledClass = $isWithinLimit ? '' : 'disabled-action';
                          $disabledAttr = $isWithinLimit ? '' : 'disabled';
                          $cursorStyle = $isWithinLimit ? '' : 'cursor: not-allowed;';
                          $rowClass = $isWithinLimit ? '' : 'service-exceeds-limit';
                        @endphp
                        <tr class="{{ $rowClass }}">
                          <td>
                            <input type="checkbox" class="bulk-check" data-val="{{ $service->id }}" {{ $disabledAttr }}>
                          </td>
                          <td>
                            <a target="_blank"
                              href="{{ route('service_details', ['slug' => $service->slug, 'id' => $service->id]) }}">{{ strlen($service->title) > 75 ? mb_substr($service->title, 0, 75, 'UTF-8') . '...' : $service->title }}</a>
                          </td>
                          <td>{{ $service->categoryName }}</td>
                          <td>
                            @if ($service->quote_btn_status == 1)
                              <span class="ml-4">-</span>
                            @else
                              <a href="{{ $isWithinLimit ? route('seller.service_management.service.packages', ['id' => $service->id, 'language' => request()->input('language')]) : '#' }}"
                                class="btn btn-primary btn-sm {{ $disabledClass }}"
                                style="{{ $cursorStyle }}"
                                {{ $disabledAttr }}
                                onclick="{{ $isWithinLimit ? '' : 'return false;' }}"
                                title="{{ $isWithinLimit ? __('Manage') : __('Cannot manage - exceeds package limit') }}">
                                {{ __('Manage') }}
                              </a>
                            @endif
                          </td>
                          <td>
                            @if ($service->quote_btn_status == 1)
                              <span class="ml-4">-</span>
                            @else
                              <a href="{{ $isWithinLimit ? route('seller.service_management.service.addons', ['id' => $service->id, 'language' => request()->input('language')]) : '#' }}"
                                class="btn btn-primary btn-sm {{ $disabledClass }}"
                                style="{{ $cursorStyle }}"
                                {{ $disabledAttr }}
                                onclick="{{ $isWithinLimit ? '' : 'return false;' }}"
                                title="{{ $isWithinLimit ? __('Manage') : __('Cannot manage - exceeds package limit') }}">
                                {{ __('Manage') }}
                              </a>
                            @endif
                          </td>
                          <td>
                            <form id="featuredForm-{{ $service->id }}" class="d-inline-block"
                              action="{{ route('seller.service_management.service.update_featured_status', ['id' => $service->id]) }}"
                              method="post">
                              @csrf
                              <select
                                class="form-control form-control-sm @if ($service->is_featured == 'yes') bg-success @else bg-danger @endif {{ $disabledClass }}"
                                name="is_featured"
                                style="{{ $cursorStyle }}"
                                {{ $disabledAttr }}
                                onchange="{{ $isWithinLimit ? 'document.getElementById(\'featuredForm-'.$service->id.'\').submit()' : 'return false;' }}">
                                <option value="yes" {{ $service->is_featured == 'yes' ? 'selected' : '' }}>
                                  {{ __('Yes') }}
                                </option>
                                <option value="no" {{ $service->is_featured == 'no' ? 'selected' : '' }}>
                                  {{ __('No') }}
                                </option>
                              </select>
                            </form>
                          </td>
                          <td>
                            <a href="{{ $isWithinLimit ? route('seller.service_management.edit_service', ['id' => $service->id]) : '#' }}"
                              class="btn btn-sm btn-info {{ $disabledClass }}"
                              style="{{ $cursorStyle }}"
                              {{ $disabledAttr }}
                              onclick="{{ $isWithinLimit ? '' : 'return false;' }}"
                              title="{{ $isWithinLimit ? __('Edit') : __('Cannot edit - exceeds package limit') }}">
                              <i class="fas fa-edit"></i>
                            </a>
                            <a href="{{ $isWithinLimit ? route('seller.service_management.service.faqs', ['id' => $service->id, 'language' => request()->input('language')]) : '#' }}"
                              class="btn btn-sm btn-warning {{ $disabledClass }}"
                              style="{{ $cursorStyle }}"
                              {{ $disabledAttr }}
                              onclick="{{ $isWithinLimit ? '' : 'return false;' }}"
                              title="{{ $isWithinLimit ? __('FAQ') : __('Cannot manage FAQ - exceeds package limit') }}">
                              <i class="fas fa-question-circle"></i>
                            </a>
                            <form class="deleteForm d-inline-block" style="display:inline;" action="{{ route('seller.service_management.delete_service', ['id' => $service->id]) }}" method="post">
                              @csrf
                              <button type="submit" 
                                      class="btn btn-sm btn-danger deleteBtn {{ $disabledClass }}" 
                                      style="{{ $cursorStyle }}"
                                      {{ $disabledAttr }}
                                      onclick="{{ $isWithinLimit ? '' : 'return false;' }}"
                                      title="{{ $isWithinLimit ? __('Delete') : __('Cannot delete - exceeds package limit') }}">
                                <i class="fas fa-trash-alt"></i>
                              </button>
                            </form>
                            
                            @if(!$isWithinLimit)
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
@endsection

@section('script')
<script>
document.addEventListener('DOMContentLoaded', function() {
    var dropdownElements = [].slice.call(document.querySelectorAll('.dropdown-toggle[data-bs-toggle="dropdown"]'));
    dropdownElements.forEach(function (dropdownToggleEl) {
        new bootstrap.Dropdown(dropdownToggleEl);
    });
});

  $(document).ready(function() {
    // Ensure alert close functionality works
    $('.alert .close').on('click', function() {
      $(this).closest('.alert').fadeOut();
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
      $('.alert').fadeOut();
    }, 5000);
  });
</script>
@endsection
