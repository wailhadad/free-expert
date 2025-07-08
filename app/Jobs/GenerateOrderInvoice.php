<?php

namespace App\Jobs;

use App\Models\ClientService\ServiceOrder;
use App\Models\Language;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateOrderInvoice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(ServiceOrder $order)
    {
        $this->order = $order;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Set execution time limit for PDF generation
            set_time_limit(300);
            
            // Increase memory limit for PDF generation
            ini_set('memory_limit', '512M');
            
            $invoiceName = $this->order->order_number . '.pdf';
            $directory = 'assets/file/invoices/order-invoices/';
            $fullDirectory = public_path($directory);
            
            if (!file_exists($fullDirectory)) {
                mkdir($fullDirectory, 0775, true);
            }
            
            $fileLocation = $directory . $invoiceName;
            $arrData['orderInfo'] = $this->order;

            // Get website info for logo and title
            $websiteInfo = \App\Models\BasicSettings\Basic::first();
            $arrData['orderInfo']->logo = $websiteInfo->logo;
            $arrData['orderInfo']->website_title = $websiteInfo->website_title;

            // Get language
            $language = Language::query()->where('is_default', '=', 1)->first();

            // Get service title
            $service = $this->order->service()->first();
            $arrData['serviceTitle'] = $service ? $service->content()->where('language_id', $language->id)->pluck('title')->first() : 'Unknown Service';

            // Get package title
            $package = $this->order->package()->first();
            $arrData['packageTitle'] = $package ? $package->name : null;

            Log::info('OrderInvoice Job: Starting PDF generation', [
                'order_id' => $this->order->id,
                'file_location' => public_path($fileLocation)
            ]);

            Pdf::loadView('frontend.service.invoice', $arrData)
                ->setPaper('a4')
                ->setOptions([
                    'isRemoteEnabled' => false, // Disable remote resources for faster generation
                    'isHtml5ParserEnabled' => true,
                    'isFontSubsettingEnabled' => true,
                    'defaultFont' => 'DejaVu Sans',
                ])
                ->save(public_path($fileLocation));

            // Update order with invoice filename
            $this->order->update([
                'invoice' => $invoiceName
            ]);

            Log::info('OrderInvoice Job: PDF generated successfully', [
                'order_id' => $this->order->id,
                'file_location' => public_path($fileLocation),
                'file_exists' => file_exists(public_path($fileLocation))
            ]);

        } catch (\Exception $e) {
            Log::error('OrderInvoice Job: PDF generation failed', [
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
                'file_location' => isset($fileLocation) ? public_path($fileLocation) : 'unknown'
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
        Log::error('OrderInvoice Job: Job failed permanently', [
            'order_id' => $this->order->id,
            'error' => $exception->getMessage()
        ]);
    }
} 