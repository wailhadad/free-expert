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
            
            // Handle image removal
            if ($request->has('remove_image') && $request->remove_image == '1') {
                // Remove old image if exists
                if ($subuser->image) {
                    $imagePath = public_path('assets/img/subusers/' . $subuser->image);
                    if (file_exists($imagePath)) {
                        @unlink($imagePath);
                    }
                }
                $data['image'] = null;
            } elseif ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = uniqid() . '.' . $image->getClientOriginalExtension();
                $image->move(public_path('assets/img/subusers'), $imageName);
                $data['image'] = $imageName;
                
                // Remove old image if exists
                if ($subuser->image) {
                    $oldImagePath = public_path('assets/img/subusers/' . $subuser->image);
                    if (file_exists($oldImagePath)) {
                        @unlink($oldImagePath);
                    }
                }
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
        
        return redirect()->back()->with('success', $message);
    }
} 