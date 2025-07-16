@extends('backend.layout')

@section('content')
<div class="container py-4">
    <h2 class="mb-4">Edit Customer Brief</h2>
    <form action="{{ route('customer-briefs.update', $brief->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="mb-3">
            <label class="form-label">Title</label>
            <input type="text" name="title" class="form-control" value="{{ old('title', $brief->title) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" required>{{ old('description', $brief->description) }}</textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Delivery Time (days)</label>
            <input type="number" name="delivery_time" class="form-control" value="{{ old('delivery_time', $brief->delivery_time) }}" min="1" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Tags (comma separated)</label>
            <input type="text" name="tags" class="form-control" value="{{ old('tags', $brief->tags) }}" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Price</label>
            <input type="number" name="price" class="form-control" value="{{ old('price', $brief->price) }}" step="0.01">
        </div>
        <div class="mb-3">
            <label class="form-label">Request Quote</label>
            <select name="request_quote" class="form-select" required>
                <option value="1" @if(old('request_quote', $brief->request_quote)) selected @endif>Yes</option>
                <option value="0" @if(!old('request_quote', $brief->request_quote)) selected @endif>No</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
                <option value="active" @if(old('status', $brief->status) == 'active') selected @endif>Active</option>
                <option value="closed" @if(old('status', $brief->status) == 'closed') selected @endif>Closed</option>
                <option value="archived" @if(old('status', $brief->status) == 'archived') selected @endif>Archived</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">User</label>
            <input type="text" class="form-control" value="{{ $brief->user ? $brief->user->username : '-' }}" readonly>
        </div>
        <div class="mb-3">
            <label class="form-label">Subuser</label>
            <input type="text" class="form-control" value="{{ $brief->subuser ? $brief->subuser->username : '-' }}" readonly>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="{{ route('customer-briefs.index') }}" class="btn btn-secondary ms-2">Back to List</a>
    </form>
</div>
@endsection 