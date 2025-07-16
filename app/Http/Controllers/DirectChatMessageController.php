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
        // Force English locale for logging
        \App::setLocale('en');
        
        // Test log in English
        \Log::info('=== TEST LOG IN ENGLISH - MESSAGE CONTROLLER ===');
        
        $chatId = $request->input('chat_id');
        $message = $request->input('message');
        
        // Determine sender first
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
        
        // Handle case when no chat_id is provided (new chat)
        if (!$chatId) {
            $userId = $request->input('user_id');
            $sellerId = $request->input('seller_id');
            $subuserId = $request->input('subuser_id');
            
            if (!$userId || !$sellerId) {
                return response()->json(['error' => 'Missing required parameters for new chat'], 422);
            }
            
            // Check if chat already exists
            $chat = DirectChat::where([
                'user_id' => $userId,
                'seller_id' => $sellerId,
                'subuser_id' => $subuserId,
            ])->first();
            
            if (!$chat) {
                // Create new chat
                $chat = DirectChat::create([
                    'user_id' => $userId,
                    'seller_id' => $sellerId,
                    'subuser_id' => $subuserId,
                ]);
                
                \Log::info('Created new chat for first message', [
                    'chat_id' => $chat->id,
                    'user_id' => $userId,
                    'seller_id' => $sellerId,
                    'subuser_id' => $subuserId
                ]);
            }
            
            $chatId = $chat->id;
        } else {
            // Existing chat
            $chat = DirectChat::findOrFail($chatId);
        }



        $fileName = null;
        $fileOriginalName = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileName = \App\Http\Helpers\UploadFile::store('./assets/file/direct-chat/', $file);
            $fileOriginalName = $file->getClientOriginalName();
        }

        $subuserId = $request->input('subuser_id') ?: ($chat->subuser_id ?? null);

        // Check if this is the seller's first message BEFORE creating the message
        $isFirstSellerMessage = false;
        if ($senderType === 'seller') {
            $existingSellerMessageCount = $chat->messages()->where('sender_type', 'seller')->count();
            $isFirstSellerMessage = ($existingSellerMessageCount == 0);
        }

        // Check if this is the seller's first message and there's a pending brief_id
        $pendingBriefId = $chat->brief_id;
        
        // Check for brief context from frontend (new approach)
        $briefContext = $request->input('brief_context');
        if ($briefContext && $senderType === 'seller') {
            try {
                $briefContextData = json_decode($briefContext, true);
                if ($briefContextData && isset($briefContextData['brief_id'])) {
                    $pendingBriefId = $briefContextData['brief_id'];
                    \Log::info('Received brief context from frontend for seller message', [
                        'chat_id' => $chatId,
                        'brief_id' => $pendingBriefId,
                        'is_first_seller_message' => $isFirstSellerMessage,
                        'brief_context' => $briefContextData
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Error parsing brief context JSON', [
                    'error' => $e->getMessage(),
                    'brief_context' => $briefContext
                ]);
            }
        }
        
        // If no brief_id in chat record, check session for new chats (fallback)
        if (!$pendingBriefId && $isFirstSellerMessage && $senderType === 'seller') {
            $sessionKey = 'pending_brief_id_' . $chat->user_id . '_' . $chat->seller_id . '_' . ($chat->subuser_id ?? 'null');
            $pendingBriefId = session($sessionKey);
            
            if ($pendingBriefId) {
                // Store the brief_id in the chat record
                $chat->brief_id = $pendingBriefId;
                $chat->save();
                
                // Clear from session
                session()->forget($sessionKey);
                
                \Log::info('Retrieved brief_id from session and stored in chat', [
                    'chat_id' => $chatId,
                    'brief_id' => $pendingBriefId,
                    'session_key' => $sessionKey
                ]);
            }
        }
        
        \Log::info('Checking for brief details auto-send', [
            'chat_id' => $chatId,
            'chat_brief_id' => $pendingBriefId,
            'is_first_seller_message' => $isFirstSellerMessage,
            'sender_type' => $senderType,
            'sender_id' => $senderId
        ]);
        
        // If there's a pending brief_id, send brief details first
        // For brief context from frontend, always send regardless of message count
        // For session-based brief_id, only send on first seller message
        $shouldSendBriefDetails = false;
        $briefContextData = null;
        
        if ($briefContext) {
            // Frontend brief context - always send
            $shouldSendBriefDetails = true;
            try {
                $briefContextData = json_decode($briefContext, true);
            } catch (\Exception $e) {
                \Log::error('Error parsing brief context for sendBriefDetailsMessage', [
                    'error' => $e->getMessage()
                ]);
            }
        } elseif ($pendingBriefId && $isFirstSellerMessage) {
            // Session-based brief_id - only send on first seller message
            $shouldSendBriefDetails = true;
        }
        
        if ($shouldSendBriefDetails) {
            \Log::info('Sending brief details message before seller message', [
                'chat_id' => $chatId,
                'brief_id' => $pendingBriefId,
                'is_first_seller_message' => $isFirstSellerMessage,
                'has_brief_context' => !empty($briefContext)
            ]);
            
            // Send brief details message BEFORE the seller's message
            $this->sendBriefDetailsMessage($chat, $pendingBriefId, $briefContextData);
        }

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
                $fromUsername = $subuser ? $subuser->username : $user->username;
                $notificationData = [
                    'type' => 'direct_chat',
                    'title' => 'New Message from ' . $fromUsername,
                    'message' => "You have received a new direct message from " . $fromUsername,
                    'url' => route('seller.discussions') . '?chat_id=' . $chat->id . ($subuser ? '&subuser_id=' . $subuser->id : ''),
                    'icon' => 'fas fa-comments',
                    'extra' => [
                        'chat_id' => $chat->id,
                        'user_id' => $subuser ? $subuser->id : $user->id,
                        'user_name' => $fromUsername,
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
                    'title' => 'New Message from ' . $seller->username,
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

    // Send automatic brief details message
    private function sendBriefDetailsMessage($chat, $briefId, $briefContextData = null)
    {
        try {
            $brief = \App\Models\CustomerBrief::find($briefId);
            if (!$brief) {
                \Log::warning('Brief not found for auto-message', ['brief_id' => $briefId]);
                return;
            }

            // Use brief context data from frontend if available, otherwise use database data
            if ($briefContextData) {
                $briefDetails = [
                    'type' => 'brief_details',
                    'brief_id' => $briefContextData['brief_id'],
                    'title' => $briefContextData['brief_title'],
                    'description' => $briefContextData['brief_description'],
                    'delivery_time' => $briefContextData['brief_delivery_time'],
                    'tags' => $briefContextData['brief_tags'],
                    'price' => $briefContextData['brief_price'],
                    'request_quote' => $briefContextData['brief_request_quote'],
                    'created_at' => $briefContextData['brief_created_at'],
                    'user_name' => $briefContextData['user_name'],
                    'user_avatar' => $briefContextData['user_avatar'],
                    'attachments' => $briefContextData['brief_attachments'] ?? [],
                    'attachment_names' => $briefContextData['brief_attachment_names'] ?? [],
                ];
            } else {
                // Fallback to database data
                $briefDetails = [
                    'type' => 'brief_details',
                    'brief_id' => $brief->id,
                    'title' => $brief->title,
                    'description' => $brief->description,
                    'delivery_time' => $brief->delivery_time,
                    'tags' => $brief->tags,
                    'price' => $brief->price,
                    'request_quote' => $brief->request_quote,
                    'created_at' => $brief->created_at->format('M d, Y'),
                    'user_name' => $brief->subuser ? $brief->subuser->username : $brief->user->username,
                    'user_avatar' => $brief->subuser 
                        ? ($brief->subuser->image ? asset('assets/img/subusers/' . $brief->subuser->image) : asset('assets/img/users/profile.jpeg'))
                        : ($brief->user->image ? asset('assets/img/users/' . $brief->user->image) : asset('assets/img/users/profile.jpeg')),
                    'attachments' => $brief->getAttachmentsArray(),
                    'attachment_names' => $brief->getAttachmentNamesArray(),
                ];
            }

            // Create the message
            $message = \App\Models\DirectChatMessage::create([
                'chat_id' => $chat->id,
                'sender_id' => $brief->user_id,
                'sender_type' => 'user',
                'subuser_id' => $brief->subuser_id,
                'message' => json_encode($briefDetails),
                'file_name' => null,
                'file_original_name' => null,
            ]);

            // Update chat's updated_at for sorting
            $chat->touch();

            // Broadcast the message via Pusher
            $messageData = [
                'id' => $message->id,
                'chat_id' => $chat->id,
                'sender_id' => $message->sender_id,
                'sender_type' => $message->sender_type,
                'subuser_id' => $message->subuser_id,
                'message' => $message->message,
                'file_name' => $message->file_name,
                'file_original_name' => $message->file_original_name,
                'created_at' => $message->created_at->toISOString(),
                'updated_at' => $message->updated_at->toISOString(),
                'brief_details' => $briefDetails,
            ];

            $pusher = new \Pusher\Pusher(config('broadcasting.connections.pusher.key'), config('broadcasting.connections.pusher.secret'), config('broadcasting.connections.pusher.app_id'), [
                'cluster' => config('broadcasting.connections.pusher.options.cluster'),
                'useTLS' => true,
            ]);
            $pusher->trigger('chat-' . $chat->id, 'message.sent', $messageData);

            \Log::info('Brief details message sent automatically after seller message', [
                'chat_id' => $chat->id,
                'brief_id' => $briefId,
                'message_id' => $message->id,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error sending brief details message', [
                'chat_id' => $chat->id,
                'brief_id' => $briefId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
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
