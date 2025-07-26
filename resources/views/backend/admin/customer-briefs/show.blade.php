@extends('backend.layout')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">Customer Brief Details</h2>
    <div class="row">
        <!-- Left Column - Brief Details -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="mb-3">{{ $brief->title }}</h4>
                    <p><strong>Description:</strong> {{ $brief->description }}</p>
                    <p><strong>Delivery Time:</strong> {{ $brief->delivery_time }} days</p>
                    <p><strong>Tags:</strong>
                        @foreach(explode(',', $brief->tags) as $tag)
                            <span class="badge bg-primary text-white rounded-pill me-2 mb-1" style="font-size: 1em; padding: 0.5em 1em;">{{ trim($tag) }}</span>
                        @endforeach
                    </p>
                    <p><strong>Price/Request Quote:</strong>
                        @if($brief->request_quote)
                            <span class="badge bg-info text-white rounded-pill" style="font-size: 1em; padding: 0.5em 1em;">Request a Quote</span>
                        @else
                            <span class="badge bg-success text-white rounded-pill" style="font-size: 1em; padding: 0.5em 1em;">${{ number_format($brief->price, 2) }}</span>
                        @endif
                    </p>
                    <p><strong>Status:</strong> <span class="badge {{ $brief->status === 'active' ? 'bg-primary' : 'bg-secondary' }} text-white rounded-pill" style="font-size: 1em; padding: 0.5em 1em;">{{ $brief->status === 'active' ? 'Active' : 'Closed' }}</span></p>
                    
                    <!-- Attachments Section -->
                    @if($brief->hasAttachments())
                    <div class="mt-4">
                        <h5><strong>Attachments ({{ $brief->getAttachmentCount() }})</strong></h5>
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
                                    <div class="card border h-100">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-start">
                                                <div class="flex-shrink-0 me-3">
                                                    <i class="{{ $iconClass }}" style="font-size: 2rem;"></i>
                                                </div>
                                                <div class="flex-grow-1 min-w-0" style="overflow: hidden;">
                                                    <h6 class="card-title mb-1 text-truncate" style="font-size: 0.9rem; word-break: break-word; overflow-wrap: break-word;">{{ $attachmentName }}</h6>
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
                </div>
            </div>
        </div>
        
        <!-- Right Column - User Information -->
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-users me-2"></i>User Information</h5>
                </div>
                <div class="card-body">
                    <!-- Main User -->
                    @if($brief->user)
                    <div class="mb-4">
                        <h6 class="text-muted mb-3"><i class="fas fa-user me-1"></i>Main User</h6>
                        <div class="d-flex align-items-center p-3 rounded" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                            <img src="{{ $brief->user->image ? asset('assets/img/users/' . $brief->user->image) : asset('assets/img/users/profile.jpeg') }}" 
                                 class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <a href="{{ route('admin.user_management.user.details', $brief->user->id) }}" 
                                       class="text-decoration-none fw-semibold text-light" title="View User Profile">
                                        {{ $brief->user->username }}
                                        <i class="fas fa-external-link-alt ms-1" style="font-size: 0.8em;"></i>
                                    </a>
                                </h6>
                                <small class="text-muted">{{ $brief->user->email_address ?? 'No email' }}</small>
                            </div>
                        </div>
                    </div>
                    @endif
                    
                    <!-- Subuser -->
                    @if($brief->subuser)
                    <div class="mb-4">
                        <h6 class="text-muted mb-3"><i class="fas fa-user-tag me-1"></i>Subuser</h6>
                        <div class="d-flex align-items-center p-3 rounded" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                            <img src="{{ $brief->subuser->image ? asset('assets/img/subusers/' . $brief->subuser->image) : asset('assets/img/users/profile.jpeg') }}" 
                                 class="rounded-circle me-3" style="width: 50px; height: 50px; object-fit: cover;">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">
                                    <a href="{{ route('admin.user_management.subuser.details', $brief->subuser->id) }}" 
                                       class="text-decoration-none fw-semibold text-light" title="View Subuser Profile">
                                        {{ $brief->subuser->username }}
                                        <i class="fas fa-external-link-alt ms-1" style="font-size: 0.8em;"></i>
                                    </a>
                                </h6>
                                <small class="text-muted">Subuser Profile</small>
                            </div>
                        </div>
                    </div>
                    @else
                    <div class="mb-4">
                        <h6 class="text-muted mb-3"><i class="fas fa-user-tag me-1"></i>Subuser</h6>
                        <div class="p-3 rounded text-center" style="background: rgba(255, 255, 255, 0.05); border: 1px solid rgba(255, 255, 255, 0.1);">
                            <i class="fas fa-user-slash text-muted mb-2" style="font-size: 2rem;"></i>
                            <p class="text-muted mb-0">No subuser assigned</p>
                        </div>
                    </div>
                    @endif
                    
                    <!-- Brief Info -->
                    <div class="mt-4 pt-3 border-top" style="border-color: rgba(255, 255, 255, 0.1) !important;">
                        <h6 class="text-muted mb-3"><i class="fas fa-info-circle me-1"></i>Brief Info</h6>
                        <div class="row g-2">
                            <div class="col-6">
                                <small class="text-muted d-block">Created</small>
                                <span class="fw-semibold text-light">{{ $brief->created_at->format('M d, Y') }}</span>
                            </div>
                            <div class="col-6">
                                <small class="text-muted d-block">Updated</small>
                                <span class="fw-semibold text-light">{{ $brief->updated_at->format('M d, Y') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="mt-3">
                <a href="{{ route('customer-briefs.index') }}" class="btn btn-primary w-100 mb-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to List
                </a>
                <a href="{{ route('customer-briefs.edit', $brief->id) }}" class="btn btn-outline-warning w-100">
                    <i class="fas fa-edit me-2"></i>Edit Brief
                </a>
            </div>
        </div>
    </div>
</div>
@endsection 