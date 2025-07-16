<?php

namespace App\Http\Controllers\Seller;

use App\Models\CustomerBrief;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class SellerCustomerBriefController extends Controller
{
    // List all customer briefs matching seller's service tags
    public function index(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        
        // Get tags from service contents instead of skills from services
        $serviceTags = $seller->services()->with('content')->get()->flatMap(function($service) {
            return $service->content->pluck('tags')->filter();
        })->toArray();
        
        $allTags = collect($serviceTags)->flatMap(function($tags) {
            return array_map('trim', explode(',', $tags));
        })->unique()->filter()->values()->all();

        $query = CustomerBrief::query()->where('status', 'active');
        if ($request->filled('tags')) {
            $filterTags = array_filter(array_map('trim', explode(',', $request->input('tags'))));
            $query->where(function($q) use ($filterTags) {
                foreach ($filterTags as $tag) {
                    $q->orWhere('tags', 'like', "%$tag%");
                }
            });
        }
        $briefs = $query->get()->filter(function($brief) use ($allTags) {
            $briefTags = array_map('trim', explode(',', $brief->tags));
            return count(array_intersect($briefTags, $allTags)) > 0;
        });
        return view('seller.customer-briefs.index', compact('briefs', 'allTags'));
    }

    // Show details of a customer brief
    public function show(CustomerBrief $customerBrief)
    {
        $seller = Auth::guard('seller')->user();
        
        // Get tags from service contents instead of skills from services
        $serviceTags = $seller->services()->with('content')->get()->flatMap(function($service) {
            return $service->content->pluck('tags')->filter();
        })->toArray();
        
        $allTags = collect($serviceTags)->flatMap(function($tags) {
            return array_map('trim', explode(',', $tags));
        })->unique()->filter()->values()->all();
        
        $briefTags = array_map('trim', explode(',', $customerBrief->tags));
        if (count(array_intersect($briefTags, $allTags)) === 0) {
            abort(403, 'You do not have access to this brief.');
        }
        $brief = $customerBrief->load(['user', 'subuser']);
        return view('seller.customer-briefs.show', compact('brief'));
    }
} 