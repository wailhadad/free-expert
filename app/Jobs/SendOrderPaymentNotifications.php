<?php

namespace App\Jobs;

use App\Models\Admin;
use App\Models\Seller;
use App\Notifications\OrderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderPaymentNotifications implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $notificationData;
    protected $status;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $notificationData, string $status)
    {
        $this->notificationData = $notificationData;
        $this->status = $status;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Set execution time limit for notifications
            set_time_limit(120);
            
            Log::info('OrderPaymentNotifications Job: Starting notifications', [
                'order_id' => $this->notificationData['order_id'] ?? 'unknown',
                'status' => $this->status
            ]);

            $orderId = $this->notificationData['order_id'];
            $orderNumber = $this->notificationData['order_number'];
            $serviceName = $this->notificationData['service_name'];
            $amount = $this->notificationData['amount'];
            $currency = $this->notificationData['currency'];
            $customerName = $this->notificationData['customer_name'];

            switch ($this->status) {
                case 'completed':
                    $this->sendCompletedNotifications($orderId, $orderNumber, $serviceName, $amount, $currency, $customerName);
                    break;
                case 'pending':
                    $this->sendPendingNotifications($orderId, $orderNumber, $serviceName, $amount, $currency, $customerName);
                    break;
                case 'rejected':
                    $this->sendRejectedNotifications($orderId, $orderNumber, $serviceName, $amount, $currency, $customerName);
                    break;
            }

            Log::info('OrderPaymentNotifications Job: Notifications sent successfully', [
                'order_id' => $orderId,
                'status' => $this->status
            ]);

        } catch (\Exception $e) {
            Log::error('OrderPaymentNotifications Job: Notifications failed', [
                'order_id' => $this->notificationData['order_id'] ?? 'unknown',
                'status' => $this->status,
                'error' => $e->getMessage()
            ]);
            
            // Re-throw the exception to mark the job as failed
            throw $e;
        }
    }

    /**
     * Send notifications for completed payment status
     */
    private function sendCompletedNotifications($orderId, $orderNumber, $serviceName, $amount, $currency, $customerName)
    {
        // Notify user about payment completion
        $user = \App\Models\User::find($this->notificationData['user_id'] ?? null);
        if ($user) {
            $user->notify(new OrderNotification([
                'title' => 'Payment Completed',
                'message' => "Payment for order #{$orderNumber} ({$serviceName}) has been completed successfully. Amount: {$currency}{$amount}",
                'url' => route('user.service_order.details', ['id' => $orderId]),
                'icon' => 'fas fa-credit-card',
                'extra' => $this->notificationData,
            ]));
        }

        // Notify seller about payment completion
        if ($this->notificationData['seller_id']) {
            $seller = Seller::find($this->notificationData['seller_id']);
            if ($seller) {
                $this->notificationData['seller_name'] = $seller->username;
                $seller->notify(new OrderNotification([
                    'title' => 'Payment Received',
                    'message' => "Payment received for order #{$orderNumber} ({$serviceName}). Amount: {$currency}{$amount}",
                    'url' => route('seller.service_order.details', ['id' => $orderId]),
                    'icon' => 'fas fa-credit-card',
                    'extra' => $this->notificationData,
                ]));
            }
        }

        // Notify all admins about payment completion
        $admins = Admin::all();
        foreach ($admins as $admin) {
            $admin->notify(new OrderNotification([
                'title' => 'Payment Completed',
                'message' => "Payment completed for order #{$orderNumber} by {$customerName} ({$serviceName}). Amount: {$currency}{$amount}",
                'url' => route('admin.service_order.details', ['id' => $orderId]),
                'icon' => 'fas fa-credit-card',
                'extra' => $this->notificationData,
            ]));
        }
    }

    /**
     * Send notifications for pending payment status
     */
    private function sendPendingNotifications($orderId, $orderNumber, $serviceName, $amount, $currency, $customerName)
    {
        // Notify user about payment pending
        $user = \App\Models\User::find($this->notificationData['user_id'] ?? null);
        if ($user) {
            $user->notify(new OrderNotification([
                'title' => 'Payment Pending',
                'message' => "Payment for order #{$orderNumber} ({$serviceName}) is now pending. Amount: {$currency}{$amount}",
                'url' => route('user.service_order.details', ['id' => $orderId]),
                'icon' => 'fas fa-clock',
                'extra' => $this->notificationData,
            ]));
        }
    }

    /**
     * Send notifications for rejected payment status
     */
    private function sendRejectedNotifications($orderId, $orderNumber, $serviceName, $amount, $currency, $customerName)
    {
        // Notify user about payment rejection
        $user = \App\Models\User::find($this->notificationData['user_id'] ?? null);
        if ($user) {
            $user->notify(new OrderNotification([
                'title' => 'Payment Rejected',
                'message' => "Payment for order #{$orderNumber} ({$serviceName}) has been rejected. Please contact support for assistance.",
                'url' => route('user.service_order.details', ['id' => $orderId]),
                'icon' => 'fas fa-times-circle',
                'extra' => $this->notificationData,
            ]));
        }

        // Notify seller about payment rejection
        if ($this->notificationData['seller_id']) {
            $seller = Seller::find($this->notificationData['seller_id']);
            if ($seller) {
                $this->notificationData['seller_name'] = $seller->username;
                $seller->notify(new OrderNotification([
                    'title' => 'Payment Rejected',
                    'message' => "Payment rejected for order #{$orderNumber} ({$serviceName}). Amount: {$currency}{$amount}",
                    'url' => route('seller.service_order.details', ['id' => $orderId]),
                    'icon' => 'fas fa-times-circle',
                    'extra' => $this->notificationData,
                ]));
            }
        }
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error('OrderPaymentNotifications Job: Job failed permanently', [
            'order_id' => $this->notificationData['order_id'] ?? 'unknown',
            'status' => $this->status,
            'error' => $exception->getMessage()
        ]);
    }
} 