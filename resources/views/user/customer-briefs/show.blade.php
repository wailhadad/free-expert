@extends('frontend.layout')

@php $title = __('Customer Brief Details'); @endphp

@section('pageHeading')
  {{ $title }}
@endsection

@section('content')
  @includeIf('frontend.partials.breadcrumb', ['breadcrumb' => $breadcrumb ?? null, 'title' => $title])
  <section class="user-dashboard pt-100 pb-60">
    <div class="container">
      <div class="row">
        @includeIf('frontend.user.side-navbar')
        <div class="col-lg-9">
          <div class="card shadow rounded-4 border-0">
            <div class="card-header bg-white rounded-top-4 border-bottom-0" style="padding: 2rem 2rem 1rem 2rem;">
              <h3 class="mb-0 fw-bold" style="letter-spacing: 1px;">Customer Brief Details</h3>
            </div>
            <div class="card-body" style="padding: 2rem;">
              <h4 class="mb-4">{{ $brief->title }}</h4>
              <div class="row g-4">
                <div class="col-md-6">
                  <p class="mb-3"><strong class="text-muted">Description:</strong><br>{{ $brief->description }}</p>
                </div>
                <div class="col-md-6">
                  <p class="mb-3"><strong class="text-muted">Delivery Time:</strong> {{ $brief->delivery_time }} days</p>
                  <p class="mb-3"><strong class="text-muted">Status:</strong> 
                    <span class="badge bg-{{ $brief->status == 'active' ? 'success' : 'secondary' }}">{{ ucfirst($brief->status) }}</span>
                  </p>
                </div>
                <div class="col-md-12">
                  <p class="mb-3"><strong class="text-muted">Tags:</strong><br>
                    @foreach(explode(',', $brief->tags) as $tag)
                      <span class="badge bg-primary me-2 mb-2">{{ trim($tag) }}</span>
                    @endforeach
                  </p>
                </div>
                <div class="col-md-6">
                  <p class="mb-3"><strong class="text-muted">Price/Request Quote:</strong><br>
                    @if($brief->request_quote)
                      <span class="badge bg-info">Request a Quote</span>
                    @else
                      {{ $brief->price ? '$' . $brief->price : 'Not specified' }}
                    @endif
                  </p>
                </div>
                <div class="col-md-6">
                  <p class="mb-3"><strong class="text-muted">Profile:</strong><br>
                    @if($brief->subuser)
                      <div class="d-flex align-items-center">
                        <img src="{{ $brief->subuser->image ? asset('assets/img/subusers/' . $brief->subuser->image) : asset('assets/img/profile.jpg') }}" class="rounded-circle me-2" style="width:40px;height:40px;object-fit:cover;">
                        <span>{{ $brief->subuser->username }}</span>
                      </div>
                    @else
                      <div class="d-flex align-items-center">
                        <img src="{{ auth()->user()->image ? asset('assets/img/users/' . auth()->user()->image) : asset('assets/img/profile.jpg') }}" class="rounded-circle me-2" style="width:40px;height:40px;object-fit:cover;">
                        <span>{{ auth()->user()->username }} <span class="text-muted">(Myself)</span></span>
                      </div>
                    @endif
                  </p>
                </div>
                
                <!-- Attachments Section -->
                @if($brief->hasAttachments())
                <div class="col-md-12">
                  <p class="mb-3"><strong class="text-muted">Attachments:</strong></p>
                  <div class="row g-3">
                    @foreach($brief->getAttachmentsArray() as $index => $attachment)
                      @php
                        $attachmentName = $brief->getAttachmentNamesArray()[$index] ?? 'Attachment ' . ($index + 1);
                        $fileExt = pathinfo($attachmentName, PATHINFO_EXTENSION);
                        $iconClass = match($fileExt) {
                          'pdf' => 'fas fa-file-pdf text-danger',
                          'doc', 'docx' => 'fas fa-file-word text-primary',
                          'txt' => 'fas fa-file-alt text-secondary',
                          'jpg', 'jpeg', 'png', 'gif' => 'fas fa-file-image text-success',
                          'zip', 'rar' => 'fas fa-file-archive text-warning',
                          default => 'fas fa-file text-muted'
                        };
                      @endphp
                      <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm h-100">
                          <div class="card-body p-3">
                            <div class="d-flex align-items-center">
                              <div class="flex-shrink-0 me-3">
                                <i class="{{ $iconClass }}" style="font-size: 2rem;"></i>
                              </div>
                              <div class="flex-grow-1 min-w-0">
                                <h6 class="card-title mb-1 text-truncate" style="font-size: 0.9rem;">{{ $attachmentName }}</h6>
                                <p class="card-text text-muted mb-2" style="font-size: 0.8rem;">{{ strtoupper($fileExt) }} File</p>
                                <a href="{{ asset('assets/file/customer-briefs/' . $attachment) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                  <i class="fas fa-download me-1"></i> Download
                                </a>
                              </div>
                            </div>
                          </div>
                        </div>
                      </div>
                    @endforeach
                  </div>
                </div>
                @endif
                <div class="col-12 d-flex gap-3 mt-4">
                                  <a href="{{ route('user.customer-briefs.edit', $brief) }}" class="btn btn-outline-primary px-4 py-2" style="border-radius: 2rem;">
                  <i class="fas fa-edit me-2"></i> Edit Brief
                </a>
                <a href="{{ route('user.customer-briefs.index') }}" class="btn btn-outline-secondary px-4 py-2" style="border-radius: 2rem;">
                    <i class="fas fa-arrow-left me-2"></i> Back to List
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
@endsection 