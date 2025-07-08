<?php

namespace App\Jobs;

use App\Http\Helpers\BasicMailer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendOrderStatusEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $mailData;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $mailData)
    {
        $this->mailData = $mailData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Set execution time limit for email sending
            set_time_limit(60);
            
            Log::info('OrderStatusEmail Job: Starting email sending', [
                'order_id' => $this->mailData['order_id'] ?? 'unknown',
                'recipient' => $this->mailData['recipient'] ?? 'unknown'
            ]);

            // Use BasicMailer to send the email
            BasicMailer::sendMail($this->mailData);

            Log::info('OrderStatusEmail Job: Email sent successfully', [
                'order_id' => $this->mailData['order_id'] ?? 'unknown',
                'recipient' => $this->mailData['recipient'] ?? 'unknown'
            ]);

        } catch (\Exception $e) {
            Log::error('OrderStatusEmail Job: Email sending failed', [
                'order_id' => $this->mailData['order_id'] ?? 'unknown',
                'recipient' => $this->mailData['recipient'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            
            // Re-throw the exception to mark the job as failed
            throw $e;
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
        Log::error('OrderStatusEmail Job: Job failed permanently', [
            'order_id' => $this->mailData['order_id'] ?? 'unknown',
            'recipient' => $this->mailData['recipient'] ?? 'unknown',
            'error' => $exception->getMessage()
        ]);
    }
} 