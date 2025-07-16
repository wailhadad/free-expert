<div class="modal fade" id="deleteModal{{ $brief->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $brief->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem; background: #23263a; color: #fff;">
            <div class="modal-header border-0 bg-danger text-white" style="border-radius: 1rem 1rem 0 0;">
                <h5 class="modal-title fw-bold" id="deleteModalLabel{{ $brief->id }}">
                    <i class="fas fa-exclamation-triangle me-2"></i>Delete Brief
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                        <i class="fas fa-trash-alt text-danger" style="font-size: 2rem;"></i>
                    </div>
                    <h6 class="fw-bold text-light mb-2">Are you sure you want to delete this brief?</h6>
                    <p class="text-muted mb-0">
                        <strong>"{{ $brief->title }}"</strong> will be permanently removed and cannot be recovered.
                    </p>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0" style="background: #23263a;">
                <button type="button" class="btn btn-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius: 0.5rem;">
                    <i class="fas fa-times me-2"></i>Cancel
                </button>
                <form action="{{ route('customer-briefs.destroy', $brief->id) }}" method="POST" class="d-inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger px-4 py-2" style="border-radius: 0.5rem;">
                        <i class="fas fa-trash-alt me-2"></i>Delete Brief
                    </button>
                </form>
            </div>
        </div>
    </div>
</div> 