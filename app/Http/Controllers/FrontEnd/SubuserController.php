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
        $subusers = $user->subusers()->orderBy('created_at', 'DESC')->get();

        return view('frontend.user.subusers.index', compact('breadcrumb', 'subusers', 'user'));
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

        if ($request->hasFile('image')) {
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

        // Check if subuser has any orders
        if ($subuser->serviceOrders()->count() > 0) {
            Session::flash('error', 'Cannot delete subuser with existing orders');
            return redirect()->route('user.subusers.index');
        }

        // Delete subuser image if exists
        if ($subuser->image) {
            $imagePath = public_path('assets/img/subusers/' . $subuser->image);
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }
        }

        $subuser->delete();

        Session::flash('success', 'Subuser deleted successfully!');
        return redirect()->route('user.subusers.index');
    }

    public function toggleStatus($id)
    {
        $user = Auth::guard('web')->user();
        $subuser = $user->subusers()->findOrFail($id);

        $subuser->status = !$subuser->status;
        $subuser->save();

        $status = $subuser->status ? 'activated' : 'deactivated';
        Session::flash('success', "Subuser {$status} successfully!");
        
        return redirect()->route('user.subusers.index');
    }
} 