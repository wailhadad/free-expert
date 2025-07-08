<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrderTransaction implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $transactionData;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(array $transactionData)
    {
        $this->transactionData = $transactionData;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Set execution time limit for transaction processing
            set_time_limit(60);
            
            Log::info('ProcessOrderTransaction Job: Starting transaction processing', [
                'order_id' => $this->transactionData['order_id'] ?? 'unknown'
            ]);

            // Process transaction using existing helper functions
            storeTransaction($this->transactionData);
            
            $data = [
                'life_time_earning' => $this->transactionData['grand_total'],
                'total_profit' => is_null($this->transactionData['seller_id']) ? $this->transactionData['grand_total'] : $this->transactionData['tax'],
            ];
            storeEarnings($data);

            Log::info('ProcessOrderTransaction Job: Transaction processed successfully', [
                'order_id' => $this->transactionData['order_id'] ?? 'unknown'
            ]);

        } catch (\Exception $e) {
            Log::error('ProcessOrderTransaction Job: Transaction processing failed', [
                'order_id' => $this->transactionData['order_id'] ?? 'unknown',
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
        Log::error('ProcessOrderTransaction Job: Job failed permanently', [
            'order_id' => $this->transactionData['order_id'] ?? 'unknown',
            'error' => $exception->getMessage()
        ]);
    }
} 