@extends('backend.layout')

@section('content')
<div class="container py-4">
    <h2 class="mb-4 fw-bold">All Customer Briefs</h2>
    <form method="GET" class="mb-4">
        <div class="row g-2 align-items-end filter-bar-custom rounded-3 px-3 py-3 mb-4" style="max-width: 700px; margin: 0 auto 2rem auto;">
            <div class="col-md-9 d-flex align-items-center">
                <label for="tags" class="form-label fw-semibold me-2 mb-0">Filter by Tags</label>
                <input type="text" name="tags" id="tags" class="form-control rounded-pill px-4 py-2 border border-primary bg-dark text-light me-2" value="{{ request('tags') }}" placeholder="Type tags, separated by commas" style="max-width: 350px;">
                <button type="submit" class="btn btn-primary d-flex align-items-center justify-content-center rounded-circle p-0" style="width: 40px; height: 40px;">
                    <i class="fas fa-filter"></i>
                </button>
            </div>
            <div class="col-md-3">
                <small class="text-muted ms-2">Enter one or more tags, separated by commas.</small>
            </div>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-dark table-hover align-middle shadow-lg" style="border-radius: 1rem; overflow: hidden;">
            <thead>
                <tr style="background: rgba(30, 34, 54, 0.95);">
                    <th style="min-width: 250px; border-right: 2px solid #23263a;">Title</th>
                    <th style="min-width: 180px; border-right: 2px solid #23263a;">Customer</th>
                    <th style="min-width: 120px; border-right: 2px solid #23263a;">Subuser</th>
                    <th style="min-width: 120px; border-right: 2px solid #23263a;">Delivery Time</th>
                    <th style="min-width: 220px; border-right: 2px solid #23263a;">Tags</th>
                    <th style="min-width: 170px; border-right: 2px solid #23263a;">Price/Request Quote</th>
                    <th style="min-width: 110px; border-right: 2px solid #23263a;">Status</th>
                    <th style="min-width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($briefs as $brief)
                <tr>
                    <td style="border-right: 2px solid #23263a;">{{ $brief->title }}</td>
                    <td style="border-right: 2px solid #23263a;">
                        @if($brief->user)
                            <a href="{{ route('admin.user_management.user.details', $brief->user->id) }}" class="text-info text-decoration-none fw-semibold" title="View Customer Profile">
                                {{ $brief->user->username }}
                                <i class="fas fa-external-link-alt ms-1" style="font-size: 0.8em;"></i>
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td style="border-right: 2px solid #23263a;">
                        @if($brief->subuser)
                            <a href="{{ route('admin.user_management.subuser.details', $brief->subuser->id) }}" class="text-info text-decoration-none fw-semibold" title="View Subuser Profile">
                                {{ $brief->subuser->username }}
                                <i class="fas fa-external-link-alt ms-1" style="font-size: 0.8em;"></i>
                            </a>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </td>
                    <td style="border-right: 2px solid #23263a;">{{ $brief->delivery_time }}</td>
                    <td style="border-right: 2px solid #23263a;">
                        @foreach(explode(',', $brief->tags) as $tag)
                            <span class="badge bg-primary text-white me-1 mb-1" style="font-size: 1em; padding: 0.5em 1em; border-radius: 1em;">{{ trim($tag) }}</span>
                        @endforeach
                    </td>
                    <td style="border-right: 2px solid #23263a;">
                        @if($brief->request_quote)
                            <span class="badge bg-info text-white" style="font-size: 1em;">Request a Quote</span>
                        @else
                            <span class="badge bg-success text-white" style="font-size: 1em;">${{ number_format($brief->price, 2) }}</span>
                        @endif
                    </td>
                    <td style="border-right: 2px solid #23263a;">
                        <span class="badge bg-success text-white" style="font-size: 1em;">Active</span>
                    </td>
                    <td>
                        <div class="d-flex align-items-center justify-content-center gap-2 flex-nowrap">
                            <a href="{{ route('customer-briefs.show', $brief->id) }}" class="btn btn-outline-info btn-sm" title="View"><i class="fas fa-eye"></i></a>
                            <a href="{{ route('customer-briefs.edit', $brief->id) }}" class="btn btn-outline-warning btn-sm" title="Edit"><i class="fas fa-pen"></i></a>
                            <button type="button" class="btn btn-outline-danger btn-sm" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteModal{{ $brief->id }}"><i class="fas fa-trash"></i></button>
                        </div>
                        @include('backend.admin.customer-briefs.partials.delete-modal', ['brief' => $brief])
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('styles')
<style>
    .filter-bar-custom {
        background: rgba(30, 34, 54, 0.95);
        border: 1px solid #23263a;
        box-shadow: 0 2px 12px 0 rgba(0,0,0,0.12);
    }
    .table-dark th, .table-dark td {
        vertical-align: middle;
        border-color: #23263a !important;
    }
    .table-dark th {
        background: rgba(30, 34, 54, 0.95) !important;
        color: #fff !important;
        font-weight: 600;
    }
    .table-dark td {
        background: rgba(30, 34, 54, 0.85) !important;
        color: #e0e6ed;
    }
    .btn-outline-info:hover, .btn-outline-warning:hover, .btn-outline-danger:hover {
        box-shadow: 0 0 8px 0 #23263a;
        opacity: 0.9;
    }
    .badge.bg-primary, .badge.bg-info, .badge.bg-success {
        font-size: 1em;
        border-radius: 1em;
        padding: 0.5em 1em;
    }
    .text-info:hover {
        color: #17a2b8 !important;
        text-decoration: underline !important;
    }
</style>
@endpush 