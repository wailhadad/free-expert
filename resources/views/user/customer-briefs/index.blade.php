@extends('frontend.layout')

@php $title = __('My Customer Briefs'); @endphp

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
            <div class="card-header bg-white d-flex justify-content-between align-items-center rounded-top-4 border-bottom-0" style="padding: 2rem 2rem 1rem 2rem;">
              <h3 class="mb-0 fw-bold" style="letter-spacing: 1px;">My Briefs</h3>
              <a href="{{ route('user.customer-briefs.create') }}" class="btn btn-lg px-4 py-2 shadow-sm create-brief-btn" style="background: var(--color-primary); border-color: var(--color-primary); color: #fff; font-size: 1.1rem; border-radius: 2rem;">
                <i class="fas fa-plus me-2"></i> Create New Brief
              </a>
            </div>
            <div class="card-body" style="padding: 2rem;">
              <table class="table table-hover table-striped align-middle rounded-4 overflow-hidden" style="box-shadow: 0 2px 12px rgba(0,0,0,0.04);">
                <thead class="table-light">
                  <tr>
                    <th class="fw-semibold">Title</th>
                    <th class="fw-semibold">Tags</th>
                    <th class="fw-semibold">Delivery Time</th>
                    <th class="fw-semibold">Price/Request Quote</th>
                    <th class="fw-semibold">Status</th>
                    <th class="fw-semibold text-center">Actions</th>
                  </tr>
                </thead>
                <tbody>
                @forelse($briefs as $brief)
                  <tr>
                    <td>{{ $brief->title }}</td>
                    <td>
                      @foreach(explode(',', $brief->tags) as $tag)
                        <span class="badge bg-primary me-1 mb-1" style="font-size: 0.95em;">{{ trim($tag) }}</span>
                      @endforeach
                    </td>
                    <td>{{ $brief->delivery_time }} days</td>
                    <td>
                      @if($brief->request_quote)
                        <span class="badge bg-info">Request a Quote</span>
                      @else
                        {{ $brief->price ? '$' . $brief->price : '-' }}
                      @endif
                    </td>
                    <td>{{ ucfirst($brief->status) }}</td>
                    <td class="text-center">
                      <div class="d-flex justify-content-center gap-1">
                                            <a href="{{ route('user.customer-briefs.show', $brief) }}" class="btn btn-outline-info btn-sm" title="Details"><i class="fas fa-eye"></i></a>
                    <a href="{{ route('user.customer-briefs.edit', $brief) }}" class="btn btn-outline-warning btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
                        <button type="button" class="btn btn-outline-danger btn-sm delete-brief-btn" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $brief->id }}" data-brief-title="{{ $brief->title }}">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="6" class="text-center text-muted py-4">No briefs found.</td></tr>
                @endforelse
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Delete Confirmation Modals -->
  @foreach($briefs as $brief)
    <div class="modal fade" id="deleteModal{{ $brief->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $brief->id }}" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 1rem;">
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
              <h6 class="fw-bold text-dark mb-2">Are you sure you want to delete this brief?</h6>
              <p class="text-muted mb-0">
                <strong>"{{ $brief->title }}"</strong> will be permanently removed and cannot be recovered.
              </p>
            </div>
          </div>
          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius: 0.5rem;">
              <i class="fas fa-times me-2"></i>Cancel
            </button>
                            <form action="{{ route('user.customer-briefs.destroy', $brief) }}" method="POST" class="d-inline">
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
  @endforeach
@endsection

@push('scripts')
<style>
.create-brief-btn:hover, .create-brief-btn:focus {
  background: #fff !important;
  color: var(--color-primary) !important;
  border: 2px solid var(--color-primary) !important;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
}
.card.shadow.rounded-4 {
  box-shadow: 0 4px 32px rgba(0,0,0,0.08) !important;
  border-radius: 2rem !important;
}
.form-label.fw-semibold {
  font-size: 1.08rem;
  color: #222;
}
.table th, .table td {
  border-right: 1px solid #e9ecef;
  vertical-align: middle;
}
.table th:last-child, .table td:last-child {
  border-right: none;
}
.table thead th {
  background-color: #f8f9fa;
  border-bottom: 2px solid #dee2e6;
  font-weight: 600;
  color: #495057;
}
.table tbody tr:nth-child(even) {
  background-color: #f8f9fa;
}
.table tbody tr:hover {
  background-color: #e9ecef;
}
.delete-brief-btn:hover {
  background-color: #dc3545 !important;
  border-color: #dc3545 !important;
  color: white !important;
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
}
.modal-content {
  box-shadow: 0 10px 40px rgba(0,0,0,0.15) !important;
}
.modal-header {
  background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Add smooth animation to delete buttons
  const deleteButtons = document.querySelectorAll('.delete-brief-btn');
  deleteButtons.forEach(button => {
    button.addEventListener('mouseenter', function() {
      this.style.transition = 'all 0.3s ease';
    });
  });
});
</script>
@endpush 