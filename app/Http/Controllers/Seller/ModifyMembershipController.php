<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use App\Models\MembershipModificationRequest;
use App\Models\Package;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ModifyMembershipController extends Controller
{
    public function index()
    {
        $seller = Auth::guard('seller')->user();
        $currentRequest = $seller->membershipModificationRequests()->where('status', 'pending')->latest('requested_at')->first();
        $packages = Package::where('status', 1)->get();
        return view('seller.modify_membership', compact('seller', 'currentRequest', 'packages'));
    }

    public function request(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        $request->validate([
            'package_id' => 'required|exists:packages,id',
        ]);
        // Cancel any previous pending request
        $seller->membershipModificationRequests()->where('status', 'pending')->update(['status' => 'cancelled']);
        // Create new request
        MembershipModificationRequest::create([
            'seller_id' => $seller->id,
            'package_id' => $request->package_id,
            'status' => 'pending',
            'requested_at' => now(),
        ]);
        Session::flash('success', 'Modification request submitted successfully!');
        return redirect()->route('seller.modify_membership');
    }

    public function delete(Request $request)
    {
        $seller = Auth::guard('seller')->user();
        $seller->membershipModificationRequests()->where('status', 'pending')->update(['status' => 'cancelled']);
        Session::flash('success', 'Modification request deleted. The current membership will continue.');
        return redirect()->route('seller.modify_membership');
    }
} 