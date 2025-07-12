<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Seller;
use App\Models\User;
use App\Notifications\RealTimeNotification;
use App\Events\NotificationReceived;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Send a notification to a notifiable entity (admin, seller, user).
     *
     * @param  \Illuminate\Database\Eloquent\Model  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     */
    public function send($notifiable, $notification)
    {
        Notification::send($notifiable, $notification);
    }

    /**
     * Send a notification to multiple notifiables.
     *
     * @param  iterable $notifiables
     * @param  \Illuminate\Notifications\Notification $notification
     * @return void
     */
    public function sendToMany($notifiables, $notification)
    {
        Notification::send($notifiables, $notification);
    }

    /**
     * Send a real-time notification to a notifiable entity.
     *
     * @param  \Illuminate\Database\Eloquent\Model  $notifiable
     * @param  array  $data
     * @return void
     */
    public function sendRealTime($notifiable, array $data)
    {
        \Log::info('NotificationService::sendRealTime called', [
            'notifiable' => class_basename($notifiable),
            'notifiable_id' => $notifiable->id,
            'data' => $data
        ]);
        
        // Create and send the database notification
        $notification = new RealTimeNotification($data);
        $notifiable->notify($notification);
        
        // Trigger the broadcast event (same pattern as MessageStored)
        event(new NotificationReceived($data, class_basename($notifiable), $notifiable->id));
        
        return $notification;
    }



    /**
     * Send a real-time notification to multiple notifiables.
     *
     * @param  iterable $notifiables
     * @param  array  $data
     * @return void
     */
    public function sendRealTimeToMany($notifiables, array $data)
    {
        $notification = new RealTimeNotification($data);
        Notification::send($notifiables, $notification);
        
        // Trigger broadcast events for each notifiable
        foreach ($notifiables as $notifiable) {
            event(new NotificationReceived($data, class_basename($notifiable), $notifiable->id));
        }
    }

    /**
     * Send a notification to all admins.
     *
     * @param  array  $data
     * @return void
     */
    public function notifyAdmins(array $data)
    {
        $admins = Admin::all();
        $this->sendRealTimeToMany($admins, $data);
    }

    /**
     * Send a notification to all sellers.
     *
     * @param  array  $data
     * @return void
     */
    public function notifySellers(array $data)
    {
        $sellers = Seller::all();
        $this->sendRealTimeToMany($sellers, $data);
    }

    /**
     * Send a notification to all users.
     *
     * @param  array  $data
     * @return void
     */
    public function notifyUsers(array $data)
    {
        $users = User::all();
        $this->sendRealTimeToMany($users, $data);
    }

    /**
     * Send a notification to all user types.
     *
     * @param  array  $data
     * @return void
     */
    public function notifyAll(array $data)
    {
        $this->notifyAdmins($data);
        $this->notifySellers($data);
        $this->notifyUsers($data);
    }
} 