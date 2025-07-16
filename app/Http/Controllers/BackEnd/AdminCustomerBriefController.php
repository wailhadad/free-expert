<?php

namespace App\Http\Controllers\BackEnd;

use App\Http\Controllers\Controller;
use App\Models\CustomerBrief;
use Illuminate\Http\Request;

class AdminCustomerBriefController extends Controller
{
    // List all customer briefs
    public function index(Request $request)
    {
        $briefsQuery = CustomerBrief::with(['user', 'subuser'])->orderByDesc('id');
        if ($request->filled('tags')) {
            $tags = array_filter(array_map('trim', explode(',', $request->input('tags'))));
            $briefsQuery->where(function($query) use ($tags) {
                foreach ($tags as $tag) {
                    $query->orWhere('tags', 'like', "%$tag%");
                }
            });
        }
        $briefs = $briefsQuery->get();
        return view('backend.admin.customer-briefs.index', compact('briefs'));
    }

    // Show details of a customer brief
    public function show(CustomerBrief $customerBrief)
    {
        $brief = $customerBrief->load(['user', 'subuser']);
        return view('backend.admin.customer-briefs.show', compact('brief'));
    }

    // Edit a customer brief
    public function edit(CustomerBrief $customerBrief)
    {
        $brief = $customerBrief->load(['user', 'subuser']);
        return view('backend.admin.customer-briefs.edit', compact('brief'));
    }

    // Update a customer brief
    public function update(Request $request, CustomerBrief $customerBrief)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'delivery_time' => 'required|integer|min:1',
            'tags' => 'required|string',
            'price' => 'nullable|numeric',
            'request_quote' => 'required|boolean',
            'status' => 'required|string',
        ]);
        $customerBrief->update($data);
        return redirect()->route('customer-briefs.index')->with('success', 'Brief updated successfully.');
    }

    // Delete a customer brief
    public function destroy(CustomerBrief $customerBrief)
    {
        $customerBrief->delete();
        return redirect()->route('customer-briefs.index')->with('success', 'Brief deleted successfully.');
    }
} 