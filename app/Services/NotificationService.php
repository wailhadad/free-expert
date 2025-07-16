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

    /**
     * Notify sellers about new customer briefs that match their service tags.
     *
     * @param  \App\Models\CustomerBrief  $brief
     * @return void
     */
    public function notifySellersAboutNewBrief($brief)
    {
        // Get all sellers
        $sellers = Seller::all();
        
        // Get brief tags
        $briefTags = array_map('trim', explode(',', $brief->tags));
        
        // Filter sellers whose services match the brief tags
        $matchingSellers = $sellers->filter(function($seller) use ($briefTags) {
            // Get tags from seller's service contents
            $serviceTags = $seller->services()->with('content')->get()->flatMap(function($service) {
                return $service->content->pluck('tags')->filter();
            })->toArray();
            
            $allTags = collect($serviceTags)->flatMap(function($tags) {
                return array_map('trim', explode(',', $tags));
            })->unique()->filter()->values()->all();
            
            // Check if there's any intersection between brief tags and seller tags
            return count(array_intersect($briefTags, $allTags)) > 0;
        });
        
        if ($matchingSellers->count() > 0) {
            // Prepare notification data
            $notificationData = [
                'type' => 'customer_brief',
                'title' => 'New Customer Brief Available',
                'message' => "A new customer brief matching your services has been posted: \"{$brief->title}\"",
                'url' => route('seller.customer-briefs.index'),
                'icon' => 'fas fa-briefcase',
                'extra' => [
                    'brief_id' => $brief->id,
                    'brief_title' => $brief->title,
                    'brief_tags' => $briefTags,
                    'delivery_time' => $brief->delivery_time,
                    'price' => $brief->price,
                    'request_quote' => $brief->request_quote,
                    'customer_name' => $brief->user ? $brief->user->username : 'Unknown',
                ],
            ];
            
            // Send notifications to matching sellers
            $this->sendRealTimeToMany($matchingSellers, $notificationData);
            
            \Log::info('Customer brief notifications sent', [
                'brief_id' => $brief->id,
                'brief_title' => $brief->title,
                'matching_sellers_count' => $matchingSellers->count(),
                'seller_ids' => $matchingSellers->pluck('id')->toArray(),
            ]);
        }
    }
} 