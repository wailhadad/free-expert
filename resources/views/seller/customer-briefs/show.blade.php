@extends('seller.layout')

@section('content')
<div class="container py-4">
    <h2 class="mb-4 fw-bold">Customer Brief Details</h2>
    <div class="card shadow-lg" style="border-radius: 1rem; border: 1px solid #23263a;">
        <div class="card-body p-4">
            <div class="row">
                <div class="col-lg-8">
                    <h4 class="mb-4 fw-bold text-primary">{{ $brief->title }}</h4>
                    
                    <div class="mb-4">
                        <h6 class="fw-semibold text-light mb-2">Description</h6>
                        <p class="text-muted mb-0">{{ $brief->description }}</p>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-light mb-2">Delivery Time</h6>
                            <span class="badge bg-warning text-dark" style="font-size: 1em; padding: 0.5em 1em;">{{ $brief->delivery_time }} days</span>
                        </div>
                        <div class="col-md-6">
                            <h6 class="fw-semibold text-light mb-2">Status</h6>
                            <span class="badge bg-success text-white" style="font-size: 1em; padding: 0.5em 1em;">{{ $brief->status === 'active' ? 'Active' : 'Closed' }}</span>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-semibold text-light mb-2">Tags</h6>
                        @foreach(explode(',', $brief->tags) as $tag)
                            <span class="badge bg-primary text-white me-2 mb-2" style="font-size: 1em; padding: 0.5em 1em; border-radius: 1em;">{{ trim($tag) }}</span>
                        @endforeach
                    </div>
                    
                    <div class="mb-4">
                        <h6 class="fw-semibold text-light mb-2">Price/Request Quote</h6>
                        @if($brief->request_quote)
                            <span class="badge bg-info text-white" style="font-size: 1em; padding: 0.5em 1em;">Request a Quote</span>
                        @else
                            <span class="badge bg-success text-white" style="font-size: 1em; padding: 0.5em 1em;">${{ number_format($brief->price, 2) }}</span>
                        @endif
                    </div>
                    
                    <!-- Attachments Section -->
                    @if($brief->hasAttachments())
                    <div class="mb-4">
                        <h6 class="fw-semibold text-light mb-3">Attachments ({{ $brief->getAttachmentCount() }})</h6>
                        <div class="row g-2">
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
                                <div class="col-12">
                                    <div class="d-flex align-items-center p-2 rounded" style="background: rgba(255, 255, 255, 0.1); border: 1px solid rgba(255, 255, 255, 0.2);">
                                        <div class="flex-shrink-0 me-3">
                                            <i class="{{ $iconClass }}" style="font-size: 1.5rem;"></i>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="text-light fw-semibold text-truncate" style="font-size: 0.9rem;">{{ $attachmentName }}</div>
                                            <div class="text-muted" style="font-size: 0.8rem;">{{ strtoupper($fileExt) }} File</div>
                                        </div>
                                        <div class="flex-shrink-0">
                                            <a href="{{ asset('assets/file/customer-briefs/' . $attachment) }}" target="_blank" class="btn btn-sm btn-outline-light">
                                                <i class="fas fa-download me-1"></i> Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif
                </div>
                
                <div class="col-lg-4">
                    <div class="card bg-dark" style="border: 1px solid #23263a;">
                        <div class="card-body">
                            <h6 class="fw-semibold text-light mb-3">Customer Information</h6>
                            @if($brief->subuser)
                                <div class="d-flex align-items-center mb-3">
                                    <img src="{{ $brief->subuser->image ? asset('assets/img/subusers/' . $brief->subuser->image) : asset('assets/img/profile.jpg') }}" class="rounded-circle me-3" style="width:48px;height:48px;object-fit:cover;">
                                    <div>
                                        <h6 class="mb-0 text-light">{{ $brief->subuser->username }}</h6>
                                    </div>
                                </div>
                            @else
                                <div class="d-flex align-items-center mb-3">
                                    <img src="{{ $brief->user && $brief->user->image ? asset('assets/img/users/' . $brief->user->image) : asset('assets/img/profile.jpg') }}" class="rounded-circle me-3" style="width:48px;height:48px;object-fit:cover;">
                                    <div>
                                        <h6 class="mb-0 text-light">{{ $brief->user ? $brief->user->username : 'Unknown' }}</h6>
                                        <small class="text-muted">User</small>
                                    </div>
                                </div>
                            @endif
                            
                            <div class="mt-4">
                                <button id="contactNowBtn" class="btn btn-success w-100 mb-2">
                                    <i class="fas fa-comments me-2"></i>
                                    @if($brief->request_quote)
                                        Request a Quote
                                    @else
                                        Contact Now
                                    @endif
                                </button>

                                <a href="{{ route('seller.customer-briefs.index') }}" class="btn btn-outline-secondary w-100">
                                    <i class="fas fa-arrow-left me-2"></i>Back to List
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('components.direct-chat-modal')
@include('components.customer-offer-modal')

@push('scripts')
<script src="{{ asset('assets/js/direct-chat.js') }}"></script>
<script>
// Wait for both DOM and script to be ready
function waitForDirectChatScript() {
    // Check if modal element exists
    const modalElem = document.getElementById('directChatModal');
    if (!modalElem) {
        console.error('Direct chat modal not found in DOM');
        alert('Chat modal not found. Please refresh the page and try again.');
        return;
    }
    
    if (typeof window.openDirectChatModal === 'function') {
        console.log('Direct chat script loaded successfully');
        initializeContactButton();
    } else {
        console.log('Waiting for direct chat script to load...');
        setTimeout(waitForDirectChatScript, 100);
    }
}

function initializeContactButton() {
    const btn = document.getElementById('contactNowBtn');
    if (!btn) {
        console.error('Contact button not found');
        return;
    }
    
    btn.addEventListener('click', function() {
        console.log('Contact Now button clicked');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Opening chat...';
        
        const requestData = {
            user_id: {{ $brief->user_id }},
            seller_id: {{ Auth::guard('seller')->id() }},
            @if($brief->subuser_id)
            subuser_id: {{ $brief->subuser_id }},
            @endif
            brief_id: {{ $brief->id }}
        };
        
        console.log('Request data:', requestData);
        
        // Start or get chat with brief_id for auto-message
        fetch('/seller/direct-chat/start', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify(requestData)
        })
        .then(res => {
            console.log('Response status:', res.status);
            return res.json();
        })
        .then(data => {
            console.log('Response data:', data);
            // Check if we have a valid response (either success flag or chat data)
            if (data.success || data.chat || (data.status && data.status === 'success')) {
                // Open the direct chat modal
                const partnerName = '@if($brief->subuser){{ $brief->subuser->username }}@else{{ $brief->user->username }}@endif';
                const partnerAvatar = '@if($brief->subuser){{ $brief->subuser->image ? asset("assets/img/subusers/" . $brief->subuser->image) : asset("assets/img/profile.jpg") }}@else{{ $brief->user->image ? asset("assets/img/users/" . $brief->user->image) : asset("assets/img/profile.jpg") }}@endif';
                
                const chatId = data.chat ? data.chat.id : null;
                console.log('Opening chat modal with:', {
                    chatId: chatId,
                    partnerName: partnerName,
                    partnerAvatar: partnerAvatar,
                    sellerId: {{ Auth::guard('seller')->id() }},
                    pendingBriefId: data.pending_brief_id
                });
                
                // Store the chat parameters for when first message is sent
                window.pendingChatParams = {
                    user_id: {{ $brief->user_id }},
                    seller_id: {{ Auth::guard('seller')->id() }},
                    @if($brief->subuser_id)
                    subuser_id: {{ $brief->subuser_id }},
                    @else
                    subuser_id: null,
                    @endif
                    brief_id: {{ $brief->id }}
                };
                
                // Store brief context for the next seller message
                window.pendingBriefContext = {
                    brief_id: {{ $brief->id }},
                    brief_title: '{{ addslashes($brief->title) }}',
                    brief_description: '{{ addslashes($brief->description) }}',
                    brief_delivery_time: {{ $brief->delivery_time }},
                    brief_tags: '{{ addslashes($brief->tags) }}',
                    brief_price: {{ $brief->price ?? 'null' }},
                    brief_request_quote: {{ $brief->request_quote ? 'true' : 'false' }},
                    brief_created_at: '{{ $brief->created_at->format('M d, Y') }}',
                    user_name: '@if($brief->subuser){{ addslashes($brief->subuser->username) }}@else{{ addslashes($brief->user->username) }}@endif',
                    user_avatar: '@if($brief->subuser){{ $brief->subuser->image ? asset("assets/img/subusers/" . $brief->subuser->image) : asset("assets/img/users/profile.jpeg") }}@else{{ $brief->user->image ? asset("assets/img/users/" . $brief->user->image) : asset("assets/img/users/profile.jpeg") }}@endif',
                    brief_attachments: @json($brief->getAttachmentsArray()),
                    brief_attachment_names: @json($brief->getAttachmentNamesArray())
                };
                
                console.log('Brief context stored for next seller message:', window.pendingBriefContext);
                console.log('This brief context will be added before the seller\'s next message, even in existing chats');
                
                // Open modal without chat ID (will be created on first message)
                try {
                    // Force complete modal reset before opening
                    const modalElem = document.getElementById('directChatModal');
                    console.log('Modal element found:', modalElem);
                    if (modalElem) {
                        console.log('Resetting modal state...');
                        // Ensure modal is completely reset
                        modalElem.style.display = 'none';
                        modalElem.classList.remove('show');
                        modalElem.removeAttribute('aria-hidden');
                        modalElem.setAttribute('aria-hidden', 'true');
                        
                        // Clear any existing backdrop
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) backdrop.remove();
                        
                        // Remove modal-open class
                        document.body.classList.remove('modal-open');
                        
                        // Force dispose of any existing Bootstrap modal instance
                        if (window.bootstrap && bootstrap.Modal) {
                            const instance = bootstrap.Modal.getInstance(modalElem);
                            if (instance) {
                                try {
                                    instance.dispose();
                                } catch (e) {
                                    console.log('Disposed existing modal instance');
                                }
                            }
                        }
                    } else {
                        console.error('Modal element not found!');
                        alert('Chat modal not found. Please refresh the page and try again.');
                        return;
                    }
                    
                    // Small delay to ensure state is reset, then open modal
                    setTimeout(() => {
                        try {
                            console.log('Calling openDirectChatModal with:', {
                                chatId: chatId,
                                partnerName: partnerName,
                                partnerAvatar: partnerAvatar,
                                sellerId: {{ Auth::guard('seller')->id() }},
                                subuserId: partnerName
                            });
                            
                            if (typeof window.openDirectChatModal === 'function') {
                                window.openDirectChatModal(chatId, partnerName, partnerAvatar, {{ Auth::guard('seller')->id() }}, partnerName);
                            } else {
                                console.error('openDirectChatModal function not found');
                                // Fallback: try to open modal directly
                                try {
                                    const modalElem = document.getElementById('directChatModal');
                                    if (modalElem && window.bootstrap && bootstrap.Modal) {
                                        const modal = new bootstrap.Modal(modalElem);
                                        modal.show();
                                        console.log('Modal opened using fallback method');
                                    } else {
                                        alert('Chat functionality not available. Please refresh the page and try again.');
                                    }
                                } catch (fallbackError) {
                                    console.error('Fallback modal opening failed:', fallbackError);
                                    alert('Failed to open chat. Please refresh the page and try again.');
                                }
                            }
                        } catch (modalError) {
                            console.error('Error calling openDirectChatModal:', modalError);
                            console.error('Modal error details:', {
                                message: modalError.message,
                                stack: modalError.stack,
                                name: modalError.name,
                                type: typeof modalError
                            });
                            alert('Failed to open chat modal: ' + modalError.message);
                        }
                    }, 100);
                    
                } catch (error) {
                    console.error('Error opening modal:', error);
                    console.error('Main error details:', {
                        message: error.message,
                        stack: error.stack,
                        name: error.name,
                        type: typeof error
                    });
                    // Force modal reset and retry
                    const modalElem = document.getElementById('directChatModal');
                    if (modalElem) {
                        // Reset modal state completely
                        modalElem.style.display = 'none';
                        modalElem.classList.remove('show');
                        modalElem.removeAttribute('aria-hidden');
                        modalElem.setAttribute('aria-hidden', 'true');
                        
                        // Clear backdrop
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) backdrop.remove();
                        
                        // Remove modal-open class
                        document.body.classList.remove('modal-open');
                        
                        // Retry opening
                        setTimeout(() => {
                            try {
                                if (typeof window.openDirectChatModal === 'function') {
                                    window.openDirectChatModal(chatId, partnerName, partnerAvatar, {{ Auth::guard('seller')->id() }}, partnerName);
                                } else {
                                    console.error('openDirectChatModal function not found on retry');
                                    // Fallback: try to open modal directly
                                    try {
                                        const modalElem = document.getElementById('directChatModal');
                                        if (modalElem && window.bootstrap && bootstrap.Modal) {
                                            const modal = new bootstrap.Modal(modalElem);
                                            modal.show();
                                            console.log('Modal opened using fallback method on retry');
                                        } else {
                                            alert('Chat functionality not available. Please refresh the page and try again.');
                                        }
                                    } catch (fallbackError) {
                                        console.error('Fallback modal opening failed on retry:', fallbackError);
                                        alert('Failed to open chat. Please refresh the page and try again.');
                                    }
                                }
                            } catch (retryError) {
                                console.error('Error on retry:', retryError);
                                alert('Failed to open chat modal on retry: ' + retryError.message);
                            }
                        }, 200);
                    }
                }
                
                btn.disabled = false;
                @if($brief->request_quote)
                btn.innerHTML = '<i class="fas fa-comments me-2"></i>Request a Quote';
                @else
                btn.innerHTML = '<i class="fas fa-comments me-2"></i>Contact Now';
                @endif
            } else {
                console.error('API returned error:', data);
                console.error('Response structure analysis:', {
                    hasSuccess: !!data.success,
                    hasChat: !!data.chat,
                    hasStatus: !!data.status,
                    statusValue: data.status,
                    keys: Object.keys(data)
                });
                alert('Failed to start chat: ' + (data.error || data.message || 'Invalid response format'));
                btn.disabled = false;
                @if($brief->request_quote)
                btn.innerHTML = '<i class="fas fa-comments me-2"></i>Request a Quote';
                @else
                btn.innerHTML = '<i class="fas fa-comments me-2"></i>Contact Now';
                @endif
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            console.error('Error details:', {
                message: error.message,
                stack: error.stack,
                name: error.name,
                type: typeof error
            });
            
            // Don't show alert for DataTable or RealTimeNotifications-related errors
            if (error.message && (
                error.message.includes('DataTable') ||
                error.message.includes('Cannot read properties of undefined') ||
                error.message.includes('defaults') ||
                error.message.includes('RealTimeNotifications') ||
                error.message.includes('RealtimeNotifications')
            )) {
                console.warn('Library error ignored, continuing with modal...');
                // Try to open modal anyway
                try {
                    const partnerName = '@if($brief->subuser){{ $brief->subuser->username }}@else{{ $brief->user->username }}@endif';
                    const partnerAvatar = '@if($brief->subuser){{ $brief->subuser->image ? asset("assets/img/subusers/" . $brief->subuser->image) : asset("assets/img/profile.jpg") }}@else{{ $brief->user->image ? asset("assets/img/users/" . $brief->user->image) : asset("assets/img/profile.jpg") }}@endif';
                    
                    window.pendingChatParams = {
                        user_id: {{ $brief->user_id }},
                        seller_id: {{ Auth::guard('seller')->id() }},
                        @if($brief->subuser_id)
                        subuser_id: {{ $brief->subuser_id }},
                        @else
                        subuser_id: null,
                        @endif
                        brief_id: {{ $brief->id }}
                    };
                    
                    if (typeof window.openDirectChatModal === 'function') {
                    window.openDirectChatModal(null, partnerName, partnerAvatar, {{ Auth::guard('seller')->id() }}, partnerName);
                    } else {
                        // Fallback: try to open modal directly
                        const modalElem = document.getElementById('directChatModal');
                        if (modalElem && window.bootstrap && bootstrap.Modal) {
                            const modal = new bootstrap.Modal(modalElem);
                            modal.show();
                            console.log('Modal opened using fallback method in error handler');
                        } else {
                            alert('Chat functionality not available. Please refresh the page and try again.');
                        }
                    }
                } catch (modalError) {
                    console.error('Modal error:', modalError);
                    alert('Failed to open chat. Please try again.');
                }
            } else {
                alert('Failed to start chat. Please try again. Check console for details.');
            }
            
            btn.disabled = false;
            @if($brief->request_quote)
            btn.innerHTML = '<i class="fas fa-comments me-2"></i>Request a Quote';
            @else
            btn.innerHTML = '<i class="fas fa-comments me-2"></i>Contact Now';
            @endif
        });
    });
}



// Start the script loading check when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, starting script check...');
    
    // Test if modal element exists
    const modalElem = document.getElementById('directChatModal');
    console.log('Modal element found on DOM load:', modalElem);
    
    // Test if Bootstrap is available
    console.log('Bootstrap available:', typeof window.bootstrap !== 'undefined');
    if (window.bootstrap) {
        console.log('Bootstrap Modal available:', typeof window.bootstrap.Modal !== 'undefined');
    }
    
    waitForDirectChatScript();
});
</script>
@endpush
@endsection

@push('styles')
<style>
    .card {
        background: rgba(30, 34, 54, 0.95);
        border: 1px solid #23263a;
    }
    .badge {
        font-size: 1em;
        border-radius: 1em;
        padding: 0.5em 1em;
    }
    .btn:hover {
        box-shadow: 0 0 8px 0 #23263a;
        opacity: 0.9;
    }
</style>
@endpush 