<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontEnd\MiscellaneousController;
use App\Http\Helpers\UserPermissionHelper;
use App\Http\Helpers\UploadFile;
use App\Models\Subuser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;

class SubuserController extends Controller
{
    public function index()
    {
        $misc = new MiscellaneousController();
        $breadcrumb = $misc->getBreadcrumb();

        $user = Auth::guard('web')->user();
        
        // Update subuser statuses to ensure they match the current membership limits
        \App\Http\Helpers\UserPermissionHelper::updateSubuserStatuses($user->id);
        
        // Get all subusers (statuses are now synchronized)
        $subusers = $user->subusers()->orderBy('created_at', 'DESC')->get();
        
        // Get total count and prioritization status for display purposes
        $totalSubusers = $subusers->count();
        $totalMaxSubusers = \App\Http\Helpers\UserPermissionHelper::totalMaxSubusers($user->id);
        $isPrioritized = $totalMaxSubusers < $totalSubusers;

        return view('frontend.user.subusers.index', compact('breadcrumb', 'subusers', 'user', 'totalSubusers', 'isPrioritized', 'totalMaxSubusers'));
    }

    /**
     * Get prioritized subusers for dropdowns (API endpoint)
     */
    public function getPrioritizedSubusers()
    {
        $user = Auth::guard('web')->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Update subuser statuses to ensure they match the current membership limits
        \App\Http\Helpers\UserPermissionHelper::updateSubuserStatuses($user->id);

        // Get all subusers with their status information (now synchronized)
        $allSubusers = $user->subusers()->orderBy('created_at', 'DESC')->get();
        $totalMaxSubusers = \App\Http\Helpers\UserPermissionHelper::totalMaxSubusers($user->id);
        $actualSubusersCount = $allSubusers->count();
        $isPrioritized = $totalMaxSubusers < $actualSubusersCount;
        
        $subusersData = $allSubusers->map(function($subuser) {
            return [
                'id' => $subuser->id,
                'username' => $subuser->username,
                'full_name' => $subuser->full_name,
                'image' => $subuser->image ? asset('assets/img/subusers/' . $subuser->image) : asset('assets/img/users/profile.jpeg'),
                'status' => $subuser->status,
                'created_at' => $subuser->created_at->format('M d, Y')
            ];
        });

        return response()->json([
            'subusers' => $subusersData,
            'total_subusers' => $actualSubusersCount,
            'total_max_subusers' => $totalMaxSubusers,
            'is_prioritized' => $isPrioritized,
            'showing_count' => $actualSubusersCount,
            'actual_count' => $actualSubusersCount
        ]);
    }

    public function create()
    {
        $misc = new MiscellaneousController();
        $breadcrumb = $misc->getBreadcrumb();

        $user = Auth::guard('web')->user();
        
        if (!$user->canCreateSubuser()) {
            Session::flash('error', 'You do not have permission to create subusers or you have reached your limit.');
            return redirect()->route('user.subusers.index');
        }

        return view('frontend.user.subusers.create', compact('breadcrumb', 'user'));
    }

    public function store(Request $request)
    {
        $user = Auth::guard('web')->user();
        
        if (!$user->canCreateSubuser()) {
            Session::flash('error', 'You do not have permission to create subusers or you have reached your limit.');
            return redirect()->route('user.subusers.index');
        }

        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:subusers,username|max:255',
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'phone_number' => 'nullable|max:255',
            'address' => 'nullable|max:500',
            'city' => 'nullable|max:255',
            'state' => 'nullable|max:255',
            'country' => 'nullable|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $subuser = new Subuser();
        $subuser->user_id = $user->id;
        $subuser->username = $request->username;
        $subuser->first_name = $request->first_name;
        $subuser->last_name = $request->last_name;
        $subuser->phone_number = $request->phone_number;
        $subuser->address = $request->address;
        $subuser->city = $request->city;
        $subuser->state = $request->state;
        $subuser->country = $request->country;

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $imageName = uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('assets/img/subusers/');
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Resize and save using Intervention Image
            $image = Image::make($file)->fit(300, 300);
            $image->save($destinationPath . $imageName);

            $subuser->image = $imageName;
        }

        $subuser->save();

        Session::flash('success', 'Subuser created successfully!');
        return redirect()->route('user.subusers.index');
    }

    public function edit($id)
    {
        $misc = new MiscellaneousController();
        $breadcrumb = $misc->getBreadcrumb();

        $user = Auth::guard('web')->user();
        $subuser = $user->subusers()->findOrFail($id);

        return view('frontend.user.subusers.edit', compact('breadcrumb', 'subuser', 'user'));
    }

    public function update(Request $request, $id)
    {
        $user = Auth::guard('web')->user();
        $subuser = $user->subusers()->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'username' => 'required|unique:subusers,username,' . $id . '|max:255',
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'phone_number' => 'nullable|max:255',
            'address' => 'nullable|max:500',
            'city' => 'nullable|max:255',
            'state' => 'nullable|max:255',
            'country' => 'nullable|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $subuser->username = $request->username;
        $subuser->first_name = $request->first_name;
        $subuser->last_name = $request->last_name;
        $subuser->phone_number = $request->phone_number;
        $subuser->address = $request->address;
        $subuser->city = $request->city;
        $subuser->state = $request->state;
        $subuser->country = $request->country;

        // Handle image removal
        if ($request->has('remove_image') && $request->remove_image == '1') {
            // Remove old image if exists
            if ($subuser->image) {
                $imagePath = public_path('assets/img/subusers/' . $subuser->image);
                if (file_exists($imagePath)) {
                    @unlink($imagePath);
                }
            }
            $subuser->image = null;
        } elseif ($request->hasFile('image')) {
            $file = $request->file('image');
            $imageName = uniqid() . '.' . $file->getClientOriginalExtension();
            $destinationPath = public_path('assets/img/subusers/');
            
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            // Remove old image if exists
            if ($subuser->image && file_exists($destinationPath . $subuser->image)) {
                @unlink($destinationPath . $subuser->image);
            }

            // Resize and save using Intervention Image
            $image = Image::make($file)->fit(300, 300);
            $image->save($destinationPath . $imageName);

            $subuser->image = $imageName;
        }

        $subuser->save();

        Session::flash('success', 'Subuser updated successfully!');
        return redirect()->route('user.subusers.index');
    }

    public function destroy($id)
    {
        $user = Auth::guard('web')->user();
        $subuser = $user->subusers()->findOrFail($id);

        // Delete subuser image if exists
        if ($subuser->image) {
            $imagePath = public_path('assets/img/subusers/' . $subuser->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }

        // Count related records before deletion
        $orderCount = $subuser->serviceOrders()->count();
        $messageCount = $subuser->messages()->count();
        
        // Delete related records (database will handle foreign key constraints)
        $subuser->serviceOrders()->delete();
        $subuser->messages()->delete();
        
        // Delete related customer offers
        \App\Models\CustomerOffer::where('subuser_id', $subuser->id)->delete();
        
        // Delete related customer briefs
        \App\Models\CustomerBrief::where('subuser_id', $subuser->id)->delete();
        
        // Delete related direct chats and messages
        \App\Models\DirectChat::where('subuser_id', $subuser->id)->delete();
        \App\Models\DirectChatMessage::where('subuser_id', $subuser->id)->delete();

        $subuser->delete();

        // Create success message
        $deletedItems = [];
        if ($orderCount > 0) $deletedItems[] = "{$orderCount} orders";
        if ($messageCount > 0) $deletedItems[] = "{$messageCount} messages";
        
        $message = !empty($deletedItems) 
            ? "Subuser and " . implode(', ', $deletedItems) . " deleted successfully!" 
            : 'Subuser deleted successfully!';
        Session::flash('success', $message);
        
        return redirect()->route('user.subusers.index');
    }

    public function toggleStatus($id)
    {
        $user = Auth::guard('web')->user();
        $subuser = $user->subusers()->findOrFail($id);

        // Check if trying to activate a subuser that would exceed the limit
        if (!$subuser->status) {
            // Trying to activate - check if it would exceed the limit
            $totalMaxSubusers = \App\Http\Helpers\UserPermissionHelper::totalMaxSubusers($user->id);
            $activeSubusersCount = $user->subusers()->where('status', 1)->count();
            
            if ($activeSubusersCount >= $totalMaxSubusers) {
                Session::flash('error', 'Cannot activate subuser - you have reached your maximum allowed subusers limit.');
                return redirect()->route('user.subusers.index');
            }
        }

        $subuser->status = !$subuser->status;
        $subuser->save();

        // Update all subuser statuses to ensure they match the current limits
        \App\Http\Helpers\UserPermissionHelper::updateSubuserStatuses($user->id);

        $status = $subuser->status ? 'activated' : 'deactivated';
        Session::flash('success', "Subuser {$status} successfully!");
        
        return redirect()->route('user.subusers.index');
    }

    public function listJson()
    {
        $user = Auth::guard('web')->user();
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);
        $subusers = $user->subusers()->select('id', 'username', 'first_name', 'last_name', 'image')->get();
        return response()->json(['subusers' => $subusers]);
    }
} 