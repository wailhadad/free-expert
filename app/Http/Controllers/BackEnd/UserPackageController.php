<?php

namespace App\Http\Controllers\BackEnd;

use App\Http\Controllers\Controller;
use App\Models\BasicSettings\Basic;
use App\Models\Language;
use App\Models\UserMembership;
use App\Models\UserPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Mews\Purifier\Facades\Purifier;

class UserPackageController extends Controller
{
    public function index(Request $request)
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $search = $request->search;
        $data['bex'] = $currentLang->basic_extended;
        $data['packages'] = UserPackage::query()->when($search, function ($query, $search) {
            return $query->where('title', 'like', '%' . $search . '%');
        })->orderBy('created_at', 'DESC')->get();
        $data['bs'] = \App\Models\BasicSettings\Basic::first();
        return view('backend.user-packages.index', $data);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|max:255',
                'term' => 'required|in:monthly,yearly,lifetime',
                'price' => 'required|numeric|min:0',
                'max_subusers' => 'required|integer|min:0',
                'status' => 'required|boolean',
                'recommended' => 'required|boolean',
                'trial_days' => 'required_if:is_trial,1|nullable|integer|min:0',
            ]);

            $in = $request->all();
            $in["custom_features"] = \Mews\Purifier\Facades\Purifier::clean($request["custom_features"] ?? '');
            \App\Models\UserPackage::create($in);
            \Illuminate\Support\Facades\Session::flash('success', "User Package Created Successfully");
            if ($request->ajax()) {
                return \Illuminate\Support\Facades\Response::json(['status' => 'success'], 200);
            } else {
                return redirect()->route('admin.user_package.index');
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Session::flash('error', $e->getMessage());
            if ($request->ajax()) {
                return \Illuminate\Support\Facades\Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
            } else {
                return redirect()->back();
            }
        }
    }

    public function edit($id)
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['bex'] = $currentLang->basic_extended;
        $data['package'] = UserPackage::query()->findOrFail($id);
        $data['bs'] = \App\Models\BasicSettings\Basic::first();
        return view("backend.user-packages.edit", $data);
    }

    public function update(Request $request)
    {
        try {
            $request->validate([
                'title' => 'required|max:255',
                'term' => 'required|in:monthly,yearly,lifetime',
                'price' => 'required|numeric|min:0',
                'max_subusers' => 'required|integer|min:0',
                'status' => 'required|boolean',
                'recommended' => 'required|boolean',
                'trial_days' => 'required_if:is_trial,1|nullable|integer|min:0',
            ]);

            $package = UserPackage::findOrFail($request->id);
            $oldTerm = $package->term;
            $newTerm = $request->term;
            
            $in = $request->all();
            $in["custom_features"] = Purifier::clean($request["custom_features"] ?? '');
            $package->update($in);

            // Update membership expiry dates if term changed
            if ($oldTerm !== $newTerm) {
                $updatedCount = $this->updateMembershipExpiryDates($package->id, $newTerm);
                if ($updatedCount > 0) {
                    Session::flash('info', "Package term changed from {$oldTerm} to {$newTerm}. {$updatedCount} active membership(s) have been updated with new expiry dates.");
                }
            }

            Session::flash('success', "User Package Updated Successfully");
            if ($request->ajax()) {
                return Response::json(['status' => 'success'], 200);
            } else {
                return redirect()->route('admin.user_package.index');
            }
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            if ($request->ajax()) {
                return Response::json(['status' => 'error', 'message' => $e->getMessage()], 500);
            } else {
                return redirect()->back();
            }
        }
    }

    /**
     * Update membership expiry dates when package term changes
     */
    private function updateMembershipExpiryDates($packageId, $newTerm)
    {
        $memberships = UserMembership::where('package_id', $packageId)
            ->where('status', '1') // Only active memberships
            ->get();

        $updatedCount = 0;
        foreach ($memberships as $membership) {
            $newExpireDate = $this->calculateExpiryDate($membership->start_date, $newTerm);
            $membership->update(['expire_date' => $newExpireDate]);
            $updatedCount++;
        }
        return $updatedCount;
    }

    /**
     * Calculate expiry date based on package term
     */
    private function calculateExpiryDate($startDate, $term)
    {
        $start = \Carbon\Carbon::parse($startDate);
        
        switch ($term) {
            case 'monthly':
                return $start->addMonth()->format('Y-m-d');
            case 'yearly':
                return $start->addYear()->format('Y-m-d');
            case 'lifetime':
                return '9999-12-31'; // Lifetime expiry
            default:
                return $start->addMonth()->format('Y-m-d'); // Default to monthly
        }
    }

    public function delete(Request $request)
    {
        $package = UserPackage::findOrFail($request->id);
        
        // Check if package has active memberships
        $activeMemberships = UserMembership::where('package_id', $package->id)
            ->where('status', '1')
            ->count();
            
        if ($activeMemberships > 0) {
            Session::flash('error', "Cannot delete package with active memberships");
            if ($request->ajax()) {
                return Response::json(['status' => 'error'], 400);
            } else {
                return redirect()->back();
            }
        }

        $package->delete();
        Session::flash('success', "User Package Deleted Successfully");
        if ($request->ajax()) {
            return Response::json(['status' => 'success'], 200);
        } else {
            return redirect()->route('admin.user_package.index');
        }
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->ids;
        $error = false;

        foreach ($ids as $id) {
            $package = UserPackage::find($id);
            if ($package) {
                // Check if package has active memberships
                $activeMemberships = UserMembership::where('package_id', $package->id)
                    ->where('status', '1')
                    ->count();
                    
                if ($activeMemberships > 0) {
                    $error = true;
                    continue;
                }
                $package->delete();
            }
        }

        if ($error) {
            Session::flash('warning', "Some packages could not be deleted due to active memberships");
        } else {
            Session::flash('success', "User Packages Deleted Successfully");
        }
        
        return Response::json(['status' => 'success'], 200);
    }

    public function create()
    {
        if (session()->has('lang')) {
            $currentLang = Language::where('code', session()->get('lang'))->first();
        } else {
            $currentLang = Language::where('is_default', 1)->first();
        }
        $data['bex'] = $currentLang->basic_extended;
        return view('backend.user-packages.create', $data);
    }
} 