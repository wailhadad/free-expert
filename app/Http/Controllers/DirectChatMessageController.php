<?php

namespace App\Http\Controllers;

use App\Models\DirectChat;
use App\Models\DirectChatMessage;
use App\Events\DirectChatMessageSent;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DirectChatMessageController extends Controller
{
    // Send a message in a direct chat
    public function sendMessage(Request $request)
    {
        $chatId = $request->input('chat_id');
        $message = $request->input('message');
        $chat = DirectChat::findOrFail($chatId);

        // Determine sender
        if (Auth::guard('web')->check()) {
            $senderId = Auth::id();
            $senderType = 'user';
            $sender = Auth::user();
        } elseif (Auth::guard('seller')->check()) {
            $senderId = Auth::guard('seller')->id();
            $senderType = 'seller';
            $sender = Auth::guard('seller')->user();
        } elseif (Auth::guard('admin')->check()) {
            $senderId = Auth::guard('admin')->id();
            $senderType = 'admin';
            $sender = Auth::guard('admin')->user();
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $fileName = null;
        $fileOriginalName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileName = \App\Http\Helpers\UploadFile::store('./assets/file/direct-chat/', $file);
            $fileOriginalName = $file->getClientOriginalName();
        }

        $subuserId = $request->input('subuser_id') ?: ($chat->subuser_id ?? null);

        $msg = DirectChatMessage::create([
            'chat_id' => $chatId,
            'sender_id' => $senderId,
            'sender_type' => $senderType,
            'subuser_id' => $subuserId,
            'message' => $message,
            'file_name' => $fileName,
            'file_original_name' => $fileOriginalName,
        ]);

        // Update chat's updated_at for sorting
        $chat->touch();

        // --- Real-time discussion event for first message ---
        if ($chat->messages()->count() == 1) {
            $lastMsg = $msg;
            $realUser = $chat->user;
            $subuser = null;
            $user = null;
            $isSubuser = false;
            if ($lastMsg && $lastMsg->subuser_id) {
                $subuser = \App\Models\Subuser::find($lastMsg->subuser_id);
                $user = $subuser;
                $isSubuser = true;
            } else if ($chat->subuser) {
                $subuser = $chat->subuser;
                $user = $subuser;
                $isSubuser = true;
            } else {
                $user = $realUser;
            }
            $discussionData = [
                'id' => $chat->id,
                'real_user' => [
                    'id' => $realUser->id,
                    'username' => $realUser->username,
                    'avatar_url' => $realUser->image ? asset('assets/img/users/' . $realUser->image) : asset('assets/img/users/profile.jpeg'),
                ],
                'subuser' => $subuser ? [
                    'id' => $subuser->id,
                    'username' => $subuser->username,
                    'avatar_url' => $subuser->image ? asset('assets/img/subusers/' . $subuser->image) : asset('assets/img/users/profile.jpeg'),
                ] : null,
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
                    'avatar_url' => $chat->seller->image ? asset('assets/img/sellers/' . $chat->seller->image) : asset('assets/img/users/profile.jpeg'),
                ] : null,
                'latest_message' => $lastMsg->message,
            ];
            $pusher = new \Pusher\Pusher(config('broadcasting.connections.pusher.key'), config('broadcasting.connections.pusher.secret'), config('broadcasting.connections.pusher.app_id'), [
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                'useTLS' => true,
            ]);
            $pusher->trigger('discussion-channel', 'discussion.started', $discussionData);
        }

        // Notify seller if sender is user
        if ($senderType === 'user') {
            $seller = $chat->seller;
            $user = $chat->user;
            // Use subuser from message if present
            $subuser = $msg->subuser_id ? \App\Models\Subuser::find($msg->subuser_id) : $chat->subuser;
            if ($seller) {
                $notificationData = [
                    'type' => 'direct_chat',
                    'title' => 'New Direct Message from Customer',
                    'message' => "You have received a new direct message from " . ($subuser ? $subuser->username : $user->username),
                    'url' => route('seller.discussions') . '?chat_id=' . $chat->id . ($subuser ? '&subuser_id=' . $subuser->id : ''),
                    'icon' => 'fas fa-comments',
                    'extra' => [
                        'chat_id' => $chat->id,
                        'user_id' => $subuser ? $subuser->id : $user->id,
                        'user_name' => $subuser ? $subuser->username : $user->username,
                        'user_avatar' => $subuser ? ($subuser->image ? asset('assets/img/subusers/' . $subuser->image) : asset('assets/img/users/profile.jpeg')) : ($user->avatar_url ?? asset('assets/img/users/profile.jpeg')),
                        'message_preview' => mb_substr($message, 0, 100),
                    ],
                ];
                
                \Log::info('Sending notification to seller', [
                    'seller_id' => $seller->id,
                    'notification_data' => $notificationData
                ]);
                
                $notificationService = new NotificationService();
                $notificationService->sendRealTime($seller, $notificationData);
                
                \Log::info('Notification sent to seller via NotificationService');
            }
            
            // Admin notifications removed - admins will not be notified about direct chat messages
        }

        // Notify user if sender is seller
        if ($senderType === 'seller') {
            $seller = $chat->seller;
            $user = $chat->user;
            if ($user) {
                $notificationData = [
                    'type' => 'direct_chat',
                    'title' => 'New Direct Message from Seller',
                    'message' => "You have received a new direct message from {$seller->username}",
                    'url' => route('user.discussions') . '?chat_id=' . $chat->id . ($subuserId ? '&subuser_id=' . $subuserId : ''),
                    'icon' => 'fas fa-comments',
                    'extra' => [
                        'chat_id' => $chat->id,
                        'seller_id' => $seller->id,
                        'seller_name' => $seller->username,
                        'seller_avatar' => $seller->photo ? asset('assets/admin/img/seller-photo/' . $seller->photo) : asset('assets/img/users/profile.jpeg'),
                        'message_preview' => mb_substr($message, 0, 100),
                    ],
                ];
                
                \Log::info('Sending notification to user', [
                    'user_id' => $user->id,
                    'notification_data' => $notificationData
                ]);
                
                $notificationService = new NotificationService();
                $notificationService->sendRealTime($user, $notificationData);
                
                \Log::info('Notification sent to user via NotificationService');
            }
            
            // Admin notifications removed - admins will not be notified about direct chat messages
        }

        // Notify user if sender is admin
        if ($senderType === 'admin') {
            $admin = Auth::guard('admin')->user();
            $user = $chat->user;
            if ($user) {
                $notificationData = [
                    'type' => 'direct_chat',
                    'title' => 'New Direct Message from Admin',
                    'message' => "You have received a new direct message from {$admin->first_name} {$admin->last_name}",
                    'url' => route('user.discussions') . '?chat_id=' . $chat->id . ($subuserId ? '&subuser_id=' . $subuserId : ''),
                    'icon' => 'fas fa-comments',
                    'extra' => [
                        'chat_id' => $chat->id,
                        'admin_id' => $admin->id,
                        'admin_name' => $admin->first_name . ' ' . $admin->last_name,
                        'admin_avatar' => $admin->image ? asset('assets/img/admins/' . $admin->image) : asset('assets/img/users/profile.jpeg'),
                        'message_preview' => mb_substr($message, 0, 100),
                    ],
                ];
                
                \Log::info('Sending notification to user from admin', [
                    'user_id' => $user->id,
                    'admin_id' => $admin->id,
                    'notification_data' => $notificationData
                ]);
                
                $notificationService = new NotificationService();
                $notificationService->sendRealTime($user, $notificationData);
                
                \Log::info('Notification sent to user from admin via NotificationService');
            }
        }

        // Broadcast event
        broadcast(new DirectChatMessageSent($msg->message, $chatId, $senderId, $senderType, $msg->created_at))->toOthers();

        return response()->json(['message' => $msg]);
    }

    // Get all messages for a chat
    public function getMessages($chatId)
    {
        try {
            $chat = DirectChat::with(['subuser'])->findOrFail($chatId);
            $messages = $chat->messages()->orderBy('created_at')->get();
            $messages = $messages->map(function($msg) use ($chat) {
                $sender = null;
                $avatar = null;
                $name = null;
                if ($msg->sender_type === 'user') {
                    // If message has subuser_id, show subuser details
                    if ($msg->subuser_id) {
                        $sender = \App\Models\Subuser::find($msg->subuser_id);
                        $avatar = $sender && $sender->image ? asset('assets/img/subusers/' . $sender->image) : asset('assets/img/users/profile.jpeg');
                        $name = $sender ? $sender->username : 'Subuser';
                    } elseif ($chat->subuser) {
                        $sender = $chat->subuser;
                        $avatar = $sender->image ? asset('assets/img/subusers/' . $sender->image) : asset('assets/img/users/profile.jpeg');
                        $name = $sender->username;
                    } else {
                        $sender = \App\Models\User::find($msg->sender_id);
                        $avatar = $sender && isset($sender->image) && $sender->image ? asset('assets/img/users/' . $sender->image) : asset('assets/img/users/profile.jpeg');
                        $name = $sender ? ($sender->name ?? $sender->username ?? 'User') : 'User';
                    }
                } elseif ($msg->sender_type === 'seller') {
                    $sender = \App\Models\Seller::find($msg->sender_id);
                    $avatar = $sender && isset($sender->photo) && $sender->photo ? asset('assets/admin/img/seller-photo/' . $sender->photo) : asset('assets/img/users/profile.jpeg');
                    $name = $sender ? ($sender->username ?? 'Seller') : 'Seller';
                } elseif ($msg->sender_type === 'admin') {
                    $sender = \App\Models\Admin::find($msg->sender_id);
                    $avatar = $sender && isset($sender->image) && $sender->image ? asset('assets/img/admins/' . $sender->image) : asset('assets/img/users/profile.jpeg');
                    $name = $sender ? ($sender->first_name . ' ' . $sender->last_name) : 'Admin';
                }
                return [
                    'id' => $msg->id,
                    'sender_type' => $msg->sender_type,
                    'sender_id' => $msg->sender_id,
                    'name' => $name,
                    'avatar' => $avatar,
                    'message' => $msg->message,
                    'created_at' => $msg->created_at,
                    'file_name' => $msg->file_name,
                    'file_original_name' => $msg->file_original_name,
                    'subuser_id' => $msg->subuser_id ?? null,
                ];
            });
            return response()->json(['messages' => $messages]);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ], 500);
        }
    }

    // Mark all messages as read for the current user/seller
    public function markAsRead($chatId)
    {
        $chat = DirectChat::findOrFail($chatId);
        if (Auth::guard('web')->check()) {
            $userId = Auth::id();
            $type = 'user';
        } elseif (Auth::guard('seller')->check()) {
            $userId = Auth::guard('seller')->id();
            $type = 'seller';
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $chat->messages()->where('sender_type', '!=', $type)->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    }
}
