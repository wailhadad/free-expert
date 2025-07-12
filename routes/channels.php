<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// Admin notification channel
Broadcast::channel('App.Models.Admin.{id}', function ($admin, $id) {
    return (int) $admin->id === (int) $id;
});

// Seller notification channel
Broadcast::channel('App.Models.Seller.{id}', function ($seller, $id) {
    return (int) $seller->id === (int) $id;
});

// User notification channel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Message channel for chat
Broadcast::channel('message-channel', function () {
    return true;
});

// Notification channel for real-time notifications
Broadcast::channel('notification-channel', function () {
    return true;
});

// Direct chat private channel
Broadcast::channel('direct-chat.{chatId}', function ($user, $chatId) {
    $chat = \App\Models\DirectChat::find($chatId);
    if (!$chat) return false;
    // Check if the user is a participant (user, seller, or subuser)
    if ($chat->user_id === $user->id) return true;
    if ($chat->seller_id === $user->id) return true;
    if ($chat->subuser_id && $chat->subuser_id === $user->id) return true;
    return false;
});
