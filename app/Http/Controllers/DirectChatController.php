<?php

namespace App\Http\Controllers;

use App\Models\DirectChat;
use App\Models\User;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DirectChatController extends Controller
{
    // Start or get a chat between user and seller
    public function startOrGetChat(Request $request)
    {
        $userId = $request->input('user_id') ?? (Auth::guard('web')->check() ? Auth::id() : null);
        $sellerId = $request->input('seller_id');
        if (!$userId || !$sellerId) {
            return response()->json(['error' => 'user_id and seller_id required'], 422);
        }
        // Validate seller exists
        $seller = \App\Models\Seller::find($sellerId);
        if (!$seller) {
            return response()->json(['error' => 'Seller not found'], 404);
        }
        try {
            $chat = DirectChat::firstOrCreate([
                'user_id' => $userId,
                'seller_id' => $sellerId,
            ]);
            return response()->json(['chat' => $chat]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Could not create or get chat: ' . $e->getMessage()], 500);
        }
    }

    // List all chats for the authenticated user
    public function listForUser()
    {
        $user = Auth::guard('web')->user();
        if (!$user) return response()->json(['error' => 'Unauthorized'], 401);
        $chats = DirectChat::with('seller')->where('user_id', $user->id)->latest('updated_at')->get();
        return response()->json(['chats' => $chats]);
    }

    // List all chats for the authenticated seller
    public function listForSeller()
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) return response()->json(['error' => 'Unauthorized'], 401);
        $chats = DirectChat::with('user')->where('seller_id', $seller->id)->latest('updated_at')->get();
        return response()->json(['chats' => $chats]);
    }

    // List all chats for admin
    public function listForAdmin()
    {
        $chats = DirectChat::with(['user', 'seller'])->latest('updated_at')->get();
        return response()->json(['chats' => $chats]);
    }
}
