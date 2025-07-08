<?php

namespace App\Traits;

use App\Services\NotificationService;

/**
 * Trait NotifiesUsers
 *
 * Usage: Use this trait in any controller or service that needs to send notifications.
 * Example:
 *   use NotifiesUsers;
 *   $this->notifyUser($user, new OrderNotification([...data...]));
 *   $this->notifyUsers($users, new ChatNotification([...data...]));
 */

trait NotifiesUsers
{
    protected function notifyUser($notifiable, $notification)
    {
        app(NotificationService::class)->send($notifiable, $notification);
    }

    protected function notifyUsers($notifiables, $notification)
    {
        app(NotificationService::class)->sendToMany($notifiables, $notification);
    }
} 