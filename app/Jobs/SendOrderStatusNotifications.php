<?php

namespace App\Jobs;

use App\Http\Helpers\BasicMailer;
use App\Models\Admin;
use App\Models\Seller;
use App\Notifications\OrderNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderStatusNotifications implements ShouldQueue
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
            // Set execution time limit for notifications and emails
            set_time_limit(120);
            
            Log::info('SendOrderStatusNotifications Job: Starting notifications and emails', [
                'order_id' => $this->notificationData['order_id'] ?? 'unknown',
                'status' => $this->status
            ]);

            $orderId = $this->notificationData['order_id'];
            $orderNumber = $this->notificationData['order_number'];
            $serviceName = $this->notificationData['service_name'];
            $customerName = $this->notificationData['customer_name'];

            switch ($this->status) {
                case 'completed':
                    $this->sendCompletedNotifications($orderId, $orderNumber, $serviceName, $customerName);
                    $this->sendCompletedEmails($orderId, $orderNumber, $serviceName, $customerName);
                    break;
                case 'rejected':
                    $this->sendRejectedNotifications($orderId, $orderNumber, $serviceName, $customerName);
                    $this->sendRejectedEmails($orderId, $orderNumber, $serviceName, $customerName);
                    break;
            }

            Log::info('SendOrderStatusNotifications Job: Notifications and emails sent successfully', [
                'order_id' => $orderId,
                'status' => $this->status
            ]);

        } catch (\Exception $e) {
            Log::error('SendOrderStatusNotifications Job: Notifications and emails failed', [
                'order_id' => $this->notificationData['order_id'] ?? 'unknown',
                'status' => $this->status,
                'error' => $e->getMessage()
            ]);
            
            // Re-throw the exception to mark the job as failed
            throw $e;
        }
    }

    /**
     * Send notifications for completed order status
     */
    private function sendCompletedNotifications($orderId, $orderNumber, $serviceName, $customerName)
    {
        // Notify user about order completion
        $user = \App\Models\User::find($this->notificationData['user_id'] ?? null);
        if ($user) {
            $user->notify(new OrderNotification([
                'title' => 'Order Completed',
                'message' => "Your order #{$orderNumber} for service: {$serviceName} has been completed successfully!",
                'url' => route('user.service_order.details', ['id' => $orderId]),
                'icon' => 'fas fa-check-circle',
                'extra' => $this->notificationData,
            ]));
        }

        // Notify seller about order completion
        if ($this->notificationData['seller_id']) {
            $seller = Seller::find($this->notificationData['seller_id']);
            if ($seller) {
                $earnings = $this->notificationData['seller_earnings'] ?? 0;
                $currency = $this->notificationData['currency'];
                $seller->notify(new OrderNotification([
                    'title' => 'Order Completed',
                    'message' => "Order #{$orderNumber} for service: {$serviceName} has been completed. You earned {$currency}{$earnings}",
                    'url' => route('seller.service_order.details', ['id' => $orderId]),
                    'icon' => 'fas fa-check-circle',
                    'extra' => $this->notificationData,
                ]));
            }
        }
    }

    /**
     * Send notifications for rejected order status
     */
    private function sendRejectedNotifications($orderId, $orderNumber, $serviceName, $customerName)
    {
        // Notify user about order rejection
        $user = \App\Models\User::find($this->notificationData['user_id'] ?? null);
        if ($user) {
            $user->notify(new OrderNotification([
                'title' => 'Order Rejected',
                'message' => "Your order #{$orderNumber} for service: {$serviceName} has been rejected. Please contact support for more information.",
                'url' => route('user.service_order.details', ['id' => $orderId]),
                'icon' => 'fas fa-times-circle',
                'extra' => $this->notificationData,
            ]));
        }

        // Notify seller about order rejection
        if ($this->notificationData['seller_id']) {
            $seller = Seller::find($this->notificationData['seller_id']);
            if ($seller) {
                $seller->notify(new OrderNotification([
                    'title' => 'Order Rejected',
                    'message' => "Order #{$orderNumber} for service: {$serviceName} has been rejected by admin.",
                    'url' => route('seller.service_order.details', ['id' => $orderId]),
                    'icon' => 'fas fa-times-circle',
                    'extra' => $this->notificationData,
                ]));
            }
        }
    }

    /**
     * Send emails for completed order status
     */
    private function sendCompletedEmails($orderId, $orderNumber, $serviceName, $customerName)
    {
        // Send email to customer
        $mailData = [
            'body' => 'Hi ' . $customerName . ',<br/><br/>We are pleased to inform you that your recent order with order number: #' . $orderNumber . ' has been successfully completed.',
            'subject' => 'Notification of order status',
            'recipient' => $this->notificationData['customer_email'] ?? '',
        ];

        if (!empty($mailData['recipient'])) {
            BasicMailer::sendMail($mailData);
        }
        
        // Send email to seller if exists
        if ($this->notificationData['seller_id']) {
            $seller = Seller::find($this->notificationData['seller_id']);
            if ($seller) {
                $mailData['recipient'] = $seller->email;
                $mailData['body'] = 'Hi ' . $seller->username . ',<br/><br/>We are pleased to inform you that your recent project with order number: #' . $orderNumber . ' has been successfully completed.';
                $mailData['sessionMessage'] = 'Order status updated & mail has been sent successfully!';
                BasicMailer::sendMail($mailData);
            }
        }
    }

    /**
     * Send emails for rejected order status
     */
    private function sendRejectedEmails($orderId, $orderNumber, $serviceName, $customerName)
    {
        // Send email to customer
        $mailData = [
            'body' => 'Hi ' . $customerName . ',<br/><br/>We are sorry to inform you that your recent project with order number: #' . $orderNumber . ' has been rejected.',
            'subject' => 'Notification of order status',
            'recipient' => $this->notificationData['customer_email'] ?? '',
            'sessionMessage' => 'Order status updated & mail has been sent successfully!',
        ];

        if (!empty($mailData['recipient'])) {
            BasicMailer::sendMail($mailData);
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
        Log::error('SendOrderStatusNotifications Job: Job failed permanently', [
            'order_id' => $this->notificationData['order_id'] ?? 'unknown',
            'status' => $this->status,
            'error' => $exception->getMessage()
        ]);
    }
} 