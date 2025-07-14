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

            // Broadcast only if this is a new chat (was just created)
            if ($chat->wasRecentlyCreated) {
                // Prepare discussion data for frontend
                $discussionData = [
                    'id' => $chat->id,
                    'user_id' => $chat->user_id,
                    'seller_id' => $chat->seller_id,
                    'subuser_id' => $chat->subuser_id,
                    'created_at' => $chat->created_at,
                    'updated_at' => $chat->updated_at,
                    'type' => Auth::guard('web')->check() ? 'user' : (Auth::guard('seller')->check() ? 'seller' : (Auth::guard('admin')->check() ? 'admin' : 'unknown')),
                ];
                // Optionally, eager load user/seller/subuser for richer data
                $chat->load(['user', 'seller', 'subuser']);
                $discussionData['user'] = $chat->user ? [
                    'id' => $chat->user->id,
                    'username' => $chat->user->username,
                    'avatar_url' => $chat->user->avatar_url ?? asset('assets/img/default-avatar.png'),
                ] : null;
                $discussionData['seller'] = $chat->seller ? [
                    'id' => $chat->seller->id,
                    'username' => $chat->seller->username,
                    'avatar_url' => $chat->seller->avatar_url ?? asset('assets/img/default-avatar.png'),
                ] : null;
                $discussionData['subuser'] = $chat->subuser ? [
                    'id' => $chat->subuser->id,
                    'username' => $chat->subuser->username,
                    'avatar_url' => $chat->subuser->image ? asset('assets/img/subusers/' . $chat->subuser->image) : asset('assets/img/users/profile.jpeg'),
                ] : null;
                // Broadcast via Pusher
                $pusher = new \Pusher\Pusher(config('broadcasting.connections.pusher.key'), config('broadcasting.connections.pusher.secret'), config('broadcasting.connections.pusher.app_id'), [
                    'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                    'useTLS' => true,
                ]);
                $pusher->trigger('discussion-channel', 'discussion.started', $discussionData);
            }
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
        $chats = DirectChat::with(['seller', 'messages', 'subuser'])->where('user_id', $user->id)->latest('updated_at')->get();
        $chats = $chats->map(function($chat) use ($user) {
            // Group unread counts by subuser_id (null for main user)
            $unreadBySubuser = $chat->messages()
                ->whereNull('read_at')
                ->where('sender_type', '!=', 'user')
                ->get()
                ->groupBy('subuser_id')
                ->map(function($msgs, $subuserId) {
                    return $msgs->count();
                });
            $subusers = [];
            foreach ($unreadBySubuser as $subuserId => $count) {
                $subuser = $subuserId ? \App\Models\Subuser::find($subuserId) : null;
                $subusers[] = [
                    'id' => $subuserId,
                    'username' => $subuser ? $subuser->username : $user->username,
                    'avatar_url' => $subuser ? ($subuser->image ? asset('assets/img/subusers/' . $subuser->image) : asset('assets/img/users/profile.jpeg')) : ($user->image ? asset('assets/img/users/' . $user->image) : asset('assets/img/users/profile.jpeg')),
                    'unread_count' => $count,
                ];
            }
            $globalUnread = $unreadBySubuser->sum();
            $chatArr = $chat->toArray();
            $chatArr['subusers'] = $subusers;
            $chatArr['unread_count'] = $globalUnread;
            // Ensure seller avatar_url uses accessor
            if ($chat->seller) {
                $chatArr['seller']['avatar_url'] = $chat->seller->avatar_url;
            }
            return $chatArr;
        });
        return response()->json(['chats' => $chats]);
    }

    // List all chats for the authenticated seller
    public function listForSeller()
    {
        $seller = Auth::guard('seller')->user();
        if (!$seller) return response()->json(['error' => 'Unauthorized'], 401);
        
        // Get chats that have at least one message
        $chats = DirectChat::with(['user', 'messages', 'subuser'])
            ->where('seller_id', $seller->id)
            ->whereHas('messages') // Only include chats with messages
            ->latest('updated_at')
            ->get();
        
        $chats = $chats->map(function($chat) use ($seller) {
            // Group unread counts by subuser_id (null for main user)
            $unreadBySubuser = $chat->messages()
                ->whereNull('read_at')
                ->where('sender_type', '!=', 'seller')
                ->get()
                ->groupBy('subuser_id')
                ->map(function($msgs, $subuserId) {
                    return $msgs->count();
                });
            
            $subusers = [];
            foreach ($unreadBySubuser as $subuserId => $count) {
                $subuser = $subuserId ? \App\Models\Subuser::find($subuserId) : null;
                $mainUser = $chat->user;
                $subusers[] = [
                    'id' => $subuserId,
                    'username' => $subuser ? $subuser->username : $mainUser->username,
                    'avatar_url' => $subuser ? ($subuser->image ? asset('assets/img/subusers/' . $subuser->image) : asset('assets/img/users/profile.jpeg')) : ($mainUser->image ? asset('assets/img/users/' . $mainUser->image) : asset('assets/img/users/profile.jpeg')),
                    'unread_count' => $count,
                ];
            }
            
            $globalUnread = $unreadBySubuser->sum();
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
                'seller' => $chat->seller ? [
                    'id' => $chat->seller->id,
                    'username' => $chat->seller->username,
                    'avatar_url' => $chat->seller->avatar_url,
                ] : null,
                'subusers' => $subusers,
                'unread_count' => $globalUnread,
                'messages' => $chat->messages,
            ];
        });
        return response()->json(['chats' => $chats]);
    }

    // List all chats for admin
    public function listForAdmin()
    {
        // Get chats that have at least one message
        $chats = DirectChat::with(['user', 'seller', 'subuser', 'messages'])
            ->whereHas('messages') // Only include chats with messages
            ->latest('updated_at')
            ->get();
        
        $chats = $chats->map(function($chat) {
            // Group unread counts by subuser_id (null for main user)
            $unreadBySubuser = $chat->messages()
                ->whereNull('read_at')
                ->get()
                ->groupBy('subuser_id')
                ->map(function($msgs, $subuserId) {
                    return $msgs->count();
                });
            
            $subusers = [];
            foreach ($unreadBySubuser as $subuserId => $count) {
                $subuser = $subuserId ? \App\Models\Subuser::find($subuserId) : null;
                $mainUser = $chat->user;
                $subusers[] = [
                    'id' => $subuserId,
                    'username' => $subuser ? $subuser->username : $mainUser->username,
                    'avatar_url' => $subuser ? ($subuser->image ? asset('assets/img/subusers/' . $subuser->image) : asset('assets/img/users/profile.jpeg')) : ($mainUser->image ? asset('assets/img/users/' . $mainUser->image) : asset('assets/img/users/profile.jpeg')),
                    'unread_count' => $count,
                ];
            }
            
            $globalUnread = $unreadBySubuser->sum();
            
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
                    'avatar_url' => $chat->seller->avatar_url ? $chat->seller->avatar_url : asset('assets/img/users/profile.jpeg'), // <-- Use accessor for correct image path
                ] : null,
                'subusers' => $subusers,
                'unread_count' => $globalUnread,
                'messages' => $chat->messages,
            ];
        });
        return response()->json(['chats' => $chats]);
    }

    // Get unread direct chat messages count for the authenticated user
    public function unreadCount()
    {
        // Determine which guard is being used
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            if (!$user) return response()->json(['count' => 0]);
            $count = \App\Models\DirectChatMessage::whereHas('chat', function($q) use ($user) {
                $q->where('user_id', $user->id);
            })->whereNull('read_at')->where('sender_type', '!=', 'user')->count();
        } elseif (Auth::guard('seller')->check()) {
            $seller = Auth::guard('seller')->user();
            if (!$seller) return response()->json(['count' => 0]);
            $count = \App\Models\DirectChatMessage::whereHas('chat', function($q) use ($seller) {
                $q->where('seller_id', $seller->id);
            })->whereNull('read_at')->where('sender_type', '!=', 'seller')->count();
        } elseif (Auth::guard('admin')->check()) {
            // Admin can see all unread messages
            $count = \App\Models\DirectChatMessage::whereNull('read_at')->count();
        } else {
            return response()->json(['count' => 0]);
        }
        return response()->json(['count' => $count]);
    }

    // Mark all messages as read for a given chat and subuser (for the authenticated user)
    public function markSubuserMessagesRead(Request $request)
    {
        $chatId = $request->input('chat_id');
        $subuserId = $request->input('subuser_id'); // can be null for main user
        if (!$chatId) return response()->json(['error' => 'chat_id required'], 422);
        
        // Determine which guard is being used and get the appropriate user/seller
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            if (!$user) return response()->json(['error' => 'Unauthorized'], 401);
            $chat = \App\Models\DirectChat::where('id', $chatId)->where('user_id', $user->id)->first();
            if (!$chat) return response()->json(['error' => 'Chat not found'], 404);
            $messages = $chat->messages()
                ->whereNull('read_at')
                ->where('sender_type', '!=', 'user')
                ->where('subuser_id', $subuserId)
                ->get();
            // Mark related notifications as read
            $notificationQuery = $user->unreadNotifications()->where('type', 'App\\Notifications\\DirectChatNotification');
            if ($subuserId) {
                $notificationQuery->whereJsonContains('data->chat_id', $chatId)->whereJsonContains('data->subuser_id', $subuserId);
            } else {
                $notificationQuery->whereJsonContains('data->chat_id', $chatId)->whereNull('data->subuser_id');
            }
            $notificationQuery->update(['read_at' => now()]);
        } elseif (Auth::guard('seller')->check()) {
            $seller = Auth::guard('seller')->user();
            if (!$seller) return response()->json(['error' => 'Unauthorized'], 401);
            $chat = \App\Models\DirectChat::where('id', $chatId)->where('seller_id', $seller->id)->first();
            if (!$chat) return response()->json(['error' => 'Chat not found'], 404);
            $messages = $chat->messages()
                ->whereNull('read_at')
                ->where('sender_type', '!=', 'seller')
                ->where('subuser_id', $subuserId)
                ->get();
            // Mark related notifications as read
            $notificationQuery = $seller->unreadNotifications()->where('type', 'App\\Notifications\\DirectChatNotification');
            if ($subuserId) {
                $notificationQuery->whereJsonContains('data->chat_id', $chatId)->whereJsonContains('data->subuser_id', $subuserId);
            } else {
                $notificationQuery->whereJsonContains('data->chat_id', $chatId)->whereNull('data->subuser_id');
            }
            $notificationQuery->update(['read_at' => now()]);
        } elseif (Auth::guard('admin')->check()) {
            // Admin can mark any chat as read
            $chat = \App\Models\DirectChat::where('id', $chatId)->first();
            if (!$chat) return response()->json(['error' => 'Chat not found'], 404);
            $messages = $chat->messages()
                ->whereNull('read_at')
                ->where('subuser_id', $subuserId)
                ->get();
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        foreach ($messages as $msg) {
            $msg->read_at = now();
            $msg->save();
        }
        
        return response()->json(['success' => true]);
    }
}
