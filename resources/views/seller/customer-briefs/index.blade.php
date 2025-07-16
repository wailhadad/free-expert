@extends('seller.layout')

@section('content')
<div class="container py-4">
    <h2 class="mb-4 fw-bold">Customer Briefs Matching Your Services</h2>
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
                    <th style="min-width: 120px; border-right: 2px solid #23263a;">Delivery Time</th>
                    <th style="min-width: 220px; border-right: 2px solid #23263a;">Tags</th>
                    <th style="min-width: 170px; border-right: 2px solid #23263a;">Price/Request Quote</th>
                    <th style="min-width: 120px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($briefs as $brief)
                <tr>
                    <td style="border-right: 2px solid #23263a;">{{ $brief->title }}</td>
                    <td style="border-right: 2px solid #23263a;">
                        @if($brief->subuser)
                            <div class="d-flex align-items-center">
                                <img src="{{ $brief->subuser->image ? asset('assets/img/subusers/' . $brief->subuser->image) : asset('assets/img/profile.jpg') }}" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
                                <span>{{ $brief->subuser->username }}</span>
                            </div>
                        @else
                            <div class="d-flex align-items-center">
                                <img src="{{ $brief->user->image ? asset('assets/img/users/' . $brief->user->image) : asset('assets/img/profile.jpg') }}" class="rounded-circle me-2" style="width:32px;height:32px;object-fit:cover;">
                                <span>{{ $brief->user->username }}</span>
                            </div>
                        @endif
                    </td>
                    <td style="border-right: 2px solid #23263a;">{{ $brief->delivery_time }} days</td>
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
                    <td>
                        <div class="d-flex align-items-center justify-content-center gap-2 flex-nowrap">
                            <a href="{{ route('seller.customer-briefs.show', $brief->id) }}" class="btn btn-outline-info btn-sm" title="View Details"><i class="fas fa-eye"></i></a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="d-flex flex-column align-items-center">
                            <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No matching briefs found</h5>
                            <p class="text-muted mb-0">Customer briefs that match your service tags will appear here.</p>
                        </div>
                    </td>
                </tr>
                @endforelse
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
    .btn-outline-info:hover {
        box-shadow: 0 0 8px 0 #23263a;
        opacity: 0.9;
    }
    .badge.bg-primary, .badge.bg-info, .badge.bg-success {
        font-size: 1em;
        border-radius: 1em;
        padding: 0.5em 1em;
    }
</style>
@endpush 