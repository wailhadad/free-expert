<?php

namespace App\Http\Controllers\BackEnd\User;

use App\Http\Controllers\Controller;
use App\Models\Subuser;
use Illuminate\Http\Request;

class SubuserController extends Controller
{
    public function show($id)
    {
        $subuser = Subuser::findOrFail($id);
        // For now, just return a simple view with subuser info
        return view('backend.end-user.user.subuser-details', compact('subuser'));
    }

    public function edit(Request $request, $id)
    {
        $subuser = Subuser::findOrFail($id);
        if ($request->isMethod('post')) {
            $data = $request->all();
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('assets/img/subusers'), $imageName);
                $data['image'] = $imageName;
            } else {
                unset($data['image']);
            }
            $subuser->update($data);
            return redirect()->back()->with('success', 'Subuser updated successfully!');
        }
        return view('backend.end-user.user.subuser-edit', compact('subuser'));
    }

    public function destroy(Request $request, $id)
    {
        $subuser = Subuser::findOrFail($id);
        $subuser->delete();
        return redirect()->back()->with('success', 'Subuser deleted successfully!');
    }
} 