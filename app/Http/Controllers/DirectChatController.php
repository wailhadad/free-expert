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
        // Add debug logging to see what's happening
        \Log::info('DirectChat startOrGetChat called', [
            'request_data' => $request->all(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
        ]);
        
        $userId = $request->input('user_id') ?? (Auth::guard('web')->check() ? Auth::id() : null);
        $sellerId = $request->input('seller_id');
        $subuserId = $request->input('subuser_id');
        
        \Log::info('DirectChat parameters extracted', [
            'user_id' => $userId,
            'seller_id' => $sellerId,
            'subuser_id' => $subuserId,
        ]);
        
        if (!$userId || !$sellerId) {
            \Log::warning('DirectChat missing required parameters', [
                'user_id' => $userId,
                'seller_id' => $sellerId,
            ]);
            return response()->json(['error' => 'user_id and seller_id required'], 422);
        }
        
        // Validate seller exists
        $seller = \App\Models\Seller::find($sellerId);
        if (!$seller) {
            \Log::warning('DirectChat seller not found', ['seller_id' => $sellerId]);
            return response()->json(['error' => 'Seller not found'], 404);
        }
        
        try {
            \Log::info('DirectChat attempting to create or get chat');
            $chat = DirectChat::firstOrCreate([
                'user_id' => $userId,
                'seller_id' => $sellerId,
                'subuser_id' => $subuserId,
            ]);
            \Log::info('DirectChat chat created/found successfully', ['chat_id' => $chat->id]);
            return response()->json([
                'chat' => [
                    'id' => $chat->id,
                    'user_id' => $chat->user_id,
                    'seller_id' => $chat->seller_id,
                    'subuser_id' => $chat->subuser_id,
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('DirectChat startOrGetChat error', [
                'user_id' => $userId,
                'seller_id' => $sellerId,
                'subuser_id' => $subuserId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
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
        $chats = DirectChat::with(['user', 'subuser'])->where('seller_id', $seller->id)->latest('updated_at')->get();
        // For each chat, return subuser details if present, else user details
        $chats = $chats->map(function($chat) {
            $lastMsg = $chat->messages->last();
            $user = null;
            $isSubuser = false;
            if ($lastMsg && $lastMsg->subuser_id) {
                $user = \App\Models\Subuser::find($lastMsg->subuser_id);
                $isSubuser = true;
            } else if ($chat->subuser) {
                $user = $chat->subuser;
                $isSubuser = true;
            } else {
                $user = $chat->user;
            }
            return [
                'id' => $chat->id,
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'avatar_url' => $isSubuser
                        ? ($user->image ? asset('assets/img/subusers/' . $user->image) : asset('assets/img/users/profile.jpeg'))
                        : ($user->image ? asset('assets/img/users/' . $user->image) : asset('assets/img/users/profile.jpeg')),
                ],
                'messages' => $chat->messages,
            ];
        });
        return response()->json(['chats' => $chats]);
    }

    // List all chats for admin
    public function listForAdmin()
    {
        $chats = DirectChat::with(['user', 'seller', 'subuser'])->latest('updated_at')->get();
        // For each chat, return user, subuser (if any), and seller details
        $chats = $chats->map(function($chat) {
            return [
                'id' => $chat->id,
                'user' => $chat->user ? [
                    'id' => $chat->user->id,
                    'username' => $chat->user->username,
                    'avatar_url' => $chat->user->image ? asset('assets/img/users/' . $chat->user->image) : asset('assets/img/users/profile.jpeg'),
                ] : null,
                'subuser' => $chat->subuser ? [
                    'id' => $chat->subuser->id,
                    'username' => $chat->subuser->username,
                    'avatar_url' => $chat->subuser->image ? asset('assets/img/subusers/' . $chat->subuser->image) : asset('assets/img/users/profile.jpeg'),
                ] : null,
                'seller' => $chat->seller ? [
                    'id' => $chat->seller->id,
                    'username' => $chat->seller->username,
                    'avatar_url' => $chat->seller->image ? asset('assets/img/sellers/' . $chat->seller->image) : asset('assets/img/users/profile.jpeg'),
                ] : null,
                'messages' => $chat->messages,
            ];
        });
        return response()->json(['chats' => $chats]);
    }
}
