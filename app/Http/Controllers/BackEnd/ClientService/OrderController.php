<?php

namespace App\Http\Controllers\BackEnd\ClientService;

use App\Events\MessageStored;
use App\Exports\ServiceOrdersExport;
use App\Http\Controllers\Controller;
use App\Http\Helpers\BasicMailer;
use App\Http\Helpers\UploadFile;
use App\Http\Requests\MessageRequest;
use App\Models\BasicSettings\Basic;
use App\Models\ClientService\ServiceOrder;
use App\Models\ClientService\ServiceOrderMessage;
use App\Models\ClientService\ServiceReview;
use App\Models\Language;
use App\Models\PaymentGateway\OfflineGateway;
use App\Models\PaymentGateway\OnlineGateway;
use App\Models\Seller;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Mews\Purifier\Facades\Purifier;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
  public function orders(Request $request)
  {
    try {
    $orderNumber = $paymentStatus = $orderStatus = $seller = null;

    if ($request->filled('order_no')) {
      $orderNumber = $request['order_no'];
    }
    if ($request->filled('payment_status')) {
      $paymentStatus = $request['payment_status'];
    }
    if ($request->filled('order_status')) {
      $orderStatus = $request['order_status'];
    }
    if ($request->filled('seller')) {
      $seller = $request['seller'];
    }

    $orders = ServiceOrder::query()->when($orderNumber, function (Builder $query, $orderNumber) {
      return $query->where('order_number', 'like', '%' . $orderNumber . '%');
    })
      ->when($paymentStatus, function (Builder $query, $paymentStatus) {
        return $query->where('payment_status', '=', $paymentStatus);
      })
      ->when($orderStatus, function (Builder $query, $orderStatus) {
        return $query->where('order_status', '=', $orderStatus);
      })
      ->when($seller, function (Builder $query, $seller) {
        if ($seller == 'admin') {
          $seller_id = null;
        } else {
          $seller_id = $seller;
        }
        return $query->where('seller_id', '=', $seller_id);
      })
      ->orderByDesc('id')
      ->paginate(10);

    $language = Language::query()->where('is_default', '=', 1)->first();

      // Use foreach instead of map() to avoid issues with paginated collections
      foreach ($orders as $order) {
        try {
      $service = $order->service()->first();
      if ($service) {
            $order->serviceTitle = $service->content()->where('language_id', $language->id)->pluck('title')->first();
            $order->serviceSlug = $service->content()->where('language_id', $language->id)->pluck('slug')->first();
      } else {
        // Fallback for customer offer orders (no real service)
            $order->serviceTitle = $order->order_number ?? 'Custom Offer';
            $order->serviceSlug = null;
      }
      $package = $order->package()->first();
      if (is_null($package)) {
            $order->packageName = NULL;
      } else {
            $order->packageName = $package->name;
      }
        } catch (\Exception $e) {
          // Log error for individual order processing
          \Log::error('Error processing order in admin orders list', [
            'order_id' => $order->id,
            'error' => $e->getMessage()
          ]);
          // Set fallback values
          $order->serviceTitle = 'Error loading service';
          $order->serviceSlug = null;
          $order->packageName = null;
        }
      }

    $sellers = Seller::select('id', 'username')->where('id', '!=', 0)->get();

      return view('backend.client-service.order.index', compact('orders', 'sellers'));
    } catch (\Exception $e) {
      \Log::error('Error in admin orders method', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      
      // Return empty results instead of crashing
      $orders = collect([])->paginate(10);
      $sellers = collect([]);

    return view('backend.client-service.order.index', compact('orders', 'sellers'));
    }
  }

  public function disputs(Request $request)
  {
    $order_no = null;
    if ($request->filled('order_no')) {
      $order_no = $request->order_no;
    }
    $collection = ServiceOrder::where('raise_status', '!=', 0)
      ->when($order_no, function ($query) use ($order_no) {
        return $query->where('order_number', 'like', '%' . $order_no . '%');
      })
      ->orderByDesc('id')
      ->paginate(10);

    $language = Language::query()->where('is_default', '=', 1)->first();

    // Use foreach instead of map() to avoid issues with paginated collections
    foreach ($collection as $order) {
      $service = $order->service()->first();
      if ($service) {
        $order->serviceTitle = $service->content()->where('language_id', $language->id)->pluck('title')->first();
        $order->serviceSlug = $service->content()->where('language_id', $language->id)->pluck('slug')->first();
      } else {
        $order->serviceTitle = $order->order_number ?? 'Custom Offer';
        $order->serviceSlug = null;
      }
    }
    return view('backend.client-service.order.disputs', compact('collection'));
  }

  //disput_update 
  public function disput_update(Request $request, $id)
  {
    $order = ServiceOrder::findOrFail($id);
    $order->update([
      'raise_status' => $request->raise_status
    ]);
    Session::flash('success', 'Raise disput status has been updated successfully.');
    return back();
  }

  public function updatePaymentStatus(Request $request, $id)
  {
    // Set execution time limit for this operation
    set_time_limit(120);
    
    $order = ServiceOrder::query()->find($id);
    if (!$order) {
      Session::flash('error', 'Order not found!');
      return redirect()->back();
    }
    
    $oldPaymentStatus = $order->payment_status;
    $newPaymentStatus = $request['payment_status'];

    // Get service and package details for notifications (optimized queries)
    $service = $order->service()->first();
    $package = $order->package()->first();
    $serviceName = $service ? $service->content()->where('language_id', 1)->pluck('title')->first() : 'Unknown Service';
    $packageName = $package ? $package->name : 'Basic Package';

    // Prepare detailed notification data
    $notificationData = [
      'order_id' => $order->id,
      'order_number' => $order->order_number,
      'service_name' => $serviceName,
      'service_id' => $order->service_id,
      'order_status' => $order->order_status,
      'payment_status' => $newPaymentStatus,
      'amount' => $order->grand_total,
      'currency' => $order->currency_symbol,
      'customer_name' => $order->name,
      'package_name' => $packageName,
      'payment_method' => $order->payment_method,
      'gateway_type' => $order->gateway_type,
      'old_payment_status' => $oldPaymentStatus,
    ];

    if ($newPaymentStatus == 'completed') {
      $order->update([
        'payment_status' => 'completed'
      ]);
      $statusMsg = 'Your payment is complete.';
      
      // Generate invoice immediately to ensure it works
      try {
        $invoice = $this->generateInvoice($order);
        $order->update([
          'invoice' => $invoice
        ]);
        
        Log::info('AdminOrderInvoice: PDF generated successfully', [
          'order_id' => $order->id,
          'invoice' => $invoice
        ]);
      } catch (\Exception $e) {
        Log::error('AdminOrderInvoice: PDF generation failed', [
          'order_id' => $order->id,
          'error' => $e->getMessage()
        ]);
        // Continue without invoice if generation fails
      }

      // Send notifications immediately to ensure they work
      try {
        // Notify user about payment completion
        $user = \App\Models\User::find($order->user_id);
        if ($user) {
          $notifyData = [
            'title' => 'Payment Completed',
            'message' => "Payment for order #{$order->order_number} ({$serviceName}) has been completed successfully. Amount: {$order->currency_symbol}{$order->grand_total}",
            'url' => route('user.service_order.details', ['id' => $order->id]),
            'icon' => 'fas fa-credit-card',
            'extra' => $notificationData,
          ];
          $user->notify(new \App\Notifications\OrderNotification($notifyData));
          $notificationService = new \App\Services\NotificationService();
          $notificationService->sendRealTime($user, $notifyData);
        }

        // Notify seller about payment completion
        if ($order->seller_id) {
          $seller = Seller::find($order->seller_id);
          if ($seller) {
            $notificationData['seller_name'] = $seller->username;
            $notifyData = [
              'title' => 'Payment Received',
              'message' => "Payment received for order #{$order->order_number} ({$serviceName}). Amount: {$order->currency_symbol}{$order->grand_total}",
              'url' => route('seller.service_order.details', ['id' => $order->id]),
              'icon' => 'fas fa-credit-card',
              'extra' => $notificationData,
            ];
            $seller->notify(new \App\Notifications\OrderNotification($notifyData));
            $notificationService = new \App\Services\NotificationService();
            $notificationService->sendRealTime($seller, $notifyData);
          }
        }

        // Send email to customer
        // Always send to main user's email_address, even if order has subuser_id
        $mainUser = $order->user;
        $recipientEmail = ($mainUser && !empty($mainUser->email_address)) ? $mainUser->email_address : null;

        if ($recipientEmail) {
        $mailData = [
          'subject' => 'Notification of payment status',
          'body' => 'Hi ' . $order->name . ',<br/><br/>This email is to notify the payment status of your order: #' . $order->order_number . '.<br/>' . $statusMsg,
            'recipient' => $recipientEmail,
          'sessionMessage' => 'Payment status updated & mail has been sent successfully!',
        ];

        // Add invoice attachment if available
        if (isset($invoice) && $invoice) {
          $mailData['invoice'] = public_path('assets/file/invoices/order-invoices/' . $invoice);
        }

        // Check SMTP configuration before sending
        $smtpInfo = \App\Models\BasicSettings\Basic::select('smtp_status', 'smtp_host', 'from_mail')->first();
        if ($smtpInfo && $smtpInfo->smtp_status == 1) {
          BasicMailer::sendMail($mailData);
          Log::info('AdminOrderPayment: Email sent via SMTP', [
            'order_id' => $order->id,
              'recipient' => $recipientEmail,
            'smtp_host' => $smtpInfo->smtp_host
          ]);
        } else {
          Log::warning('AdminOrderPayment: SMTP not configured, email not sent', [
            'order_id' => $order->id,
              'recipient' => $recipientEmail,
            'smtp_status' => $smtpInfo ? $smtpInfo->smtp_status : 'null'
            ]);
          }
        } else {
          Log::warning('Order email not sent: No valid recipient email for order', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'subuser_id' => $order->subuser_id,
          ]);
        }
        
        Log::info('AdminOrderPayment: Notifications and email sent successfully', [
          'order_id' => $order->id,
          'status' => 'completed'
        ]);
      } catch (\Exception $e) {
        Log::error('AdminOrderPayment: Notifications/Email sending failed', [
          'order_id' => $order->id,
          'error' => $e->getMessage()
        ]);
      }

    } else if ($newPaymentStatus == 'pending') {
      if ($order->invoice) {
        @unlink(public_path('assets/file/invoices/order-invoices/' . $order->invoice));
      }
      $order->update([
        'payment_status' => 'pending',
        'invoice' => null,
      ]);

      $statusMsg = 'payment is pending.';

      // Send notifications immediately
      try {
        // Notify user about payment pending
        $user = \App\Models\User::find($order->user_id);
        if ($user) {
          $notifyData = [
            'title' => 'Payment Pending',
            'message' => "Payment for order #{$order->order_number} ({$serviceName}) is now pending. Amount: {$order->currency_symbol}{$order->grand_total}",
            'url' => route('user.service_order.details', ['id' => $order->id]),
            'icon' => 'fas fa-clock',
            'extra' => $notificationData,
          ];
          $user->notify(new \App\Notifications\OrderNotification($notifyData));
          $notificationService = new \App\Services\NotificationService();
          $notificationService->sendRealTime($user, $notifyData);
        }

        // Send email to customer
        $mailData = [
          'subject' => 'Notification of payment status',
          'body' => 'Hi ' . $order->name . ',<br/><br/>This email is to notify the payment status of your order: #' . $order->order_number . '.<br/>' . $statusMsg,
          'recipient' => $order->email_address,
          'sessionMessage' => 'Payment status updated & mail has been sent successfully!',
        ];

        BasicMailer::sendMail($mailData);
        
        Log::info('AdminOrderPayment: Notifications and email sent successfully', [
          'order_id' => $order->id,
          'status' => 'pending'
        ]);
      } catch (\Exception $e) {
        Log::error('AdminOrderPayment: Notifications/Email sending failed', [
          'order_id' => $order->id,
          'error' => $e->getMessage()
        ]);
      }

    } else {
      if ($order->invoice) {
        @unlink(public_path('assets/file/invoices/order-invoices/' . $order->invoice));
      }
      $order->update([
        'payment_status' => 'rejected',
        'invoice' => null
      ]);

      $statusMsg = 'payment has been rejected.';

      // Send notifications immediately
      try {
        // Notify user about payment rejection
        $user = \App\Models\User::find($order->user_id);
        if ($user) {
          $notifyData = [
            'title' => 'Payment Rejected',
            'message' => "Payment for order #{$order->order_number} ({$serviceName}) has been rejected. Please contact support for assistance.",
            'url' => route('user.service_order.details', ['id' => $order->id]),
            'icon' => 'fas fa-times-circle',
            'extra' => $notificationData,
          ];
          $user->notify(new \App\Notifications\OrderNotification($notifyData));
          $notificationService = new \App\Services\NotificationService();
          $notificationService->sendRealTime($user, $notifyData);
        }

        // Notify seller about payment rejection
        if ($order->seller_id) {
          $seller = Seller::find($order->seller_id);
          if ($seller) {
            $notificationData['seller_name'] = $seller->username;
            $seller->notify(new \App\Notifications\OrderNotification([
              'title' => 'Payment Rejected',
              'message' => "Payment rejected for order #{$order->order_number} ({$serviceName}). Amount: {$order->currency_symbol}{$order->grand_total}",
              'url' => route('seller.service_order.details', ['id' => $order->id]),
              'icon' => 'fas fa-times-circle',
              'extra' => $notificationData,
            ]));
          }
        }

        // Send email to customer
        $mailData = [
          'subject' => 'Notification of payment status',
          'body' => 'Hi ' . $order->name . ',<br/><br/>This email is to notify the payment status of your order: #' . $order->order_number . '.<br/>' . $statusMsg,
          'recipient' => $order->email_address,
          'sessionMessage' => 'Payment status updated & mail has been sent successfully!',
        ];

        BasicMailer::sendMail($mailData);
        
        Log::info('AdminOrderPayment: Notifications and email sent successfully', [
          'order_id' => $order->id,
          'status' => 'rejected'
        ]);
      } catch (\Exception $e) {
        Log::error('AdminOrderPayment: Notifications/Email sending failed', [
          'order_id' => $order->id,
          'error' => $e->getMessage()
        ]);
      }
    }

    Session::flash('success', 'Payment status updated successfully!');
    return redirect()->back();
  }

  public function generateInvoice($order)
  {
    // Set execution time limit for PDF generation
    set_time_limit(120);
    
    // Increase memory limit for PDF generation
    ini_set('memory_limit', '256M');
    
    $invoiceName = $order->order_number . '.pdf';
    $directory = 'assets/file/invoices/order-invoices/';
    $fullDirectory = public_path($directory);
    if (!file_exists($fullDirectory)) {
      mkdir($fullDirectory, 0775, true);
    }
    $fileLocation = $directory . $invoiceName;
    $arrData['orderInfo'] = $order;

    // Get website info for logo and title
    $websiteInfo = \App\Models\BasicSettings\Basic::first();
    $arrData['orderInfo']->logo = $websiteInfo->logo;
    $arrData['orderInfo']->website_title = $websiteInfo->website_title;

    // Debug: Log the logo value
    Log::info('AdminOrderInvoice: Logo debugging', [
        'logo_from_db' => $websiteInfo->logo,
        'logo_assigned_to_order' => $arrData['orderInfo']->logo,
        'website_title' => $websiteInfo->website_title
    ]);

    // get language
    $language = Language::query()->where('is_default', '=', 1)->first();

    // get service title
    $service = $order->service()->first();
    if ($service) {
      $arrData['serviceTitle'] = $service->content()->where('language_id', $language->id)->pluck('title')->first();
    } else {
      // Fallback for customer offer orders (no real service)
      $arrData['serviceTitle'] = $order->order_number ?? 'Custom Offer';
    }

    // get package title
    $package = $order->package()->first();

    if (is_null($package)) {
      $arrData['packageTitle'] = NULL;
    } else {
      $arrData['packageTitle'] = $package->name;
    }

    Log::info('AdminOrderInvoice: Starting PDF generation', [
      'order_id' => $order->id,
      'file_location' => public_path($fileLocation)
    ]);

    try {
      Pdf::loadView('frontend.service.invoice', $arrData)
        ->setPaper('a4')
        ->setOptions([
          'isRemoteEnabled' => false, // Disable remote resources for faster generation
          'isHtml5ParserEnabled' => true,
          'isFontSubsettingEnabled' => true,
          'defaultFont' => 'DejaVu Sans',
        ])
        ->save(public_path($fileLocation));

      Log::info('AdminOrderInvoice: PDF generated successfully', [
        'order_id' => $order->id,
        'file_location' => public_path($fileLocation),
        'file_exists' => file_exists(public_path($fileLocation))
      ]);

      return $invoiceName;
    } catch (\Exception $e) {
      Log::error('AdminOrderInvoice: PDF generation failed', [
        'order_id' => $order->id,
        'error' => $e->getMessage(),
        'file_location' => public_path($fileLocation)
      ]);
      throw $e;
    }
  }

  public function updateOrderStatus(Request $request, $id)
  {
    // Set execution time limit for this operation
    set_time_limit(120);
    
    $order = ServiceOrder::query()->find($id);
    if (!$order) {
      Session::flash('error', 'Order not found!');
      return redirect()->back();
    }
    
    $oldStatus = $order->order_status;
    $newStatus = $request['order_status'];

    // Get service and package details for notifications (optimized queries)
    $service = $order->service()->first();
    $package = $order->package()->first();
    $serviceName = $service ? $service->content()->where('language_id', 1)->pluck('title')->first() : 'Unknown Service';
    $packageName = $package ? $package->name : 'Basic Package';

    // Prepare detailed notification data
    $notificationData = [
      'order_id' => $order->id,
      'order_number' => $order->order_number,
      'service_name' => $serviceName,
      'service_id' => $order->service_id,
      'order_status' => $newStatus,
      'payment_status' => $order->payment_status,
      'amount' => $order->grand_total,
      'currency' => $order->currency_symbol,
      'customer_name' => $order->name,
      'package_name' => $packageName,
      'payment_method' => $order->payment_method,
      'gateway_type' => $order->gateway_type,
      'old_status' => $oldStatus,
      'user_id' => $order->user_id,
      'seller_id' => $order->seller_id,
    ];

    if ($newStatus == 'completed') {
      $order->update([
        'order_status' => 'completed'
      ]);

      $statusMsg = 'Your order is completed.';

      if ($order->seller_id != null) {
        $seller = Seller::where('id', $order->seller_id)->first();
        if ($seller) {
          $pre_balance = $seller->amount;
          $after_balance = $seller->amount + ($order->grand_total - $order->tax);
          $seller->amount = $after_balance;
          $seller->save();
          
          // Add seller info to notification data
          $notificationData['seller_name'] = $seller->username;
          $notificationData['seller_earnings'] = $order->grand_total - $order->tax;
        } else {
          $pre_balance = null;
          $after_balance = null;
        }
      } else {
        $pre_balance = null;
        $after_balance = null;
      }

      // Process transaction data immediately
      try {
        $transaction_data = [
          'order_id' => $order->id,
          'transcation_type' => 1,
          'user_id' => $order->user_id,
          'seller_id' => $order->seller_id,
          'payment_status' => $order->payment_status,
          'payment_method' => $order->payment_method,
          'grand_total' => $order->grand_total,
          'tax' => $order->tax,
          'pre_balance' => $pre_balance,
          'after_balance' => $after_balance,
          'gateway_type' => $order->gateway_type,
          'currency_symbol' => $order->currency_symbol,
          'currency_symbol_position' => $order->currency_symbol_position,
        ];
        
        // Process transaction using existing helper functions
        storeTransaction($transaction_data);
        
        $data = [
          'life_time_earning' => $order->grand_total,
          'total_profit' => is_null($order->seller_id) ? $order->grand_total : $order->tax,
        ];
        storeEarnings($data);
        
        Log::info('AdminOrderStatus: Transaction processed successfully', [
          'order_id' => $order->id
        ]);
      } catch (\Exception $e) {
        Log::error('AdminOrderStatus: Transaction processing failed', [
          'order_id' => $order->id,
          'error' => $e->getMessage()
        ]);
      }

      // Send notifications and emails immediately
      try {
        // Notify user about order completion
        $user = \App\Models\User::find($order->user_id);
        if ($user) {
          $notifyData = [
            'title' => 'Order Completed',
            'message' => "Your order #{$order->order_number} for service: {$serviceName} has been completed successfully!",
            'url' => route('user.service_order.details', ['id' => $order->id]),
            'icon' => 'fas fa-check-circle',
            'extra' => $notificationData,
          ];
          $user->notify(new \App\Notifications\OrderNotification($notifyData));
          $notificationService = new \App\Services\NotificationService();
          $notificationService->sendRealTime($user, $notifyData);
        }

        // Notify seller about order completion
        if ($order->seller_id && isset($seller)) {
          $earnings = $order->grand_total - $order->tax;
          $currency = $order->currency_symbol;
          $seller->notify(new \App\Notifications\OrderNotification([
            'title' => 'Order Completed',
            'message' => "Order #{$order->order_number} for service: {$serviceName} has been completed. You earned {$currency}{$earnings}",
            'url' => route('seller.service_order.details', ['id' => $order->id]),
            'icon' => 'fas fa-check-circle',
            'extra' => $notificationData,
          ]));
        }

        // Send emails
        $mailData = [
          'body' => 'Hi ' . $order->name . ',<br/><br/>We are pleased to inform you that your recent order with order number: #' . $order->order_number . ' has been successfully completed.',
          'subject' => 'Notification of order status',
          'recipient' => $order->email_address,
        ];

        BasicMailer::sendMail($mailData);
        
        if ($order->seller_id && isset($seller)) {
          $mailData['recipient'] = $seller->email;
          $mailData['body'] = 'Hi ' . $seller->username . ',<br/><br/>We are pleased to inform you that your recent project with order number: #' . $order->order_number . ' has been successfully completed.';
          $mailData['sessionMessage'] = 'Order status updated & mail has been sent successfully!';
          BasicMailer::sendMail($mailData);
        }
        
        Log::info('AdminOrderStatus: Notifications and emails sent successfully', [
          'order_id' => $order->id,
          'status' => 'completed'
        ]);
      } catch (\Exception $e) {
        Log::error('AdminOrderStatus: Notifications/Email sending failed', [
          'order_id' => $order->id,
          'error' => $e->getMessage()
        ]);
      }
    } else {
      $order->update([
        'order_status' => 'rejected'
      ]);

      // Send notifications immediately
      try {
        // Notify user about order rejection
        $user = \App\Models\User::find($order->user_id);
        if ($user) {
          $notifyData = [
            'title' => 'Order Rejected',
            'message' => "Your order #{$order->order_number} for service: {$serviceName} has been rejected. Please contact support for more information.",
            'url' => route('user.service_order.details', ['id' => $order->id]),
            'icon' => 'fas fa-times-circle',
            'extra' => $notificationData,
          ];
          $user->notify(new \App\Notifications\OrderNotification($notifyData));
          $notificationService = new \App\Services\NotificationService();
          $notificationService->sendRealTime($user, $notifyData);
        }

        // Notify seller about order rejection
        if ($order->seller_id) {
          $seller = Seller::find($order->seller_id);
          if ($seller) {
            $seller->notify(new \App\Notifications\OrderNotification([
              'title' => 'Order Rejected',
              'message' => "Order #{$order->order_number} for service: {$serviceName} has been rejected by admin.",
              'url' => route('seller.service_order.details', ['id' => $order->id]),
              'icon' => 'fas fa-times-circle',
              'extra' => $notificationData,
            ]));
          }
        }

        // Send email to customer
        $mailData = [
          'body' => 'Hi ' . $order->name . ',<br/><br/>We are sorry to inform you that your recent project with order number: #' . $order->order_number . ' has been rejected.',
          'subject' => 'Notification of order status',
          'recipient' => $order->email_address,
          'sessionMessage' => 'Order status updated & mail has been sent successfully!',
        ];

        BasicMailer::sendMail($mailData);
        
        Log::info('AdminOrderStatus: Notifications and emails sent successfully', [
          'order_id' => $order->id,
          'status' => 'rejected'
        ]);
      } catch (\Exception $e) {
        Log::error('AdminOrderStatus: Notifications/Email sending failed', [
          'order_id' => $order->id,
          'error' => $e->getMessage()
        ]);
      }
    }

    Session::flash('success', 'Order status updated successfully!');
    return redirect()->back();
  }

  public function show($id)
  {
    $order = ServiceOrder::query()->findOrFail($id);
    $queryResult['orderInfo'] = $order;
    $queryResult['userUsername'] = $order->user ? $order->user->username : null;
    $queryResult['subuserUsername'] = $order->subuser ? $order->subuser->username : null;
    $queryResult['displayEmail'] = $order->user ? $order->user->email_address : 'N/A';

    $language = Language::query()->where('is_default', '=', 1)->first();

    // get service title
    $service = $order->service()->first();
    if ($service) {
      $queryResult['serviceTitle'] = $service->content()->where('language_id', $language->id)->select('title', 'slug')->first();
    } else {
      // Fallback for customer offer orders (no real service)
      $queryResult['serviceTitle'] = (object)[
        'title' => $order->order_number ?? 'Custom Offer',
        'slug' => null
      ];
    }

    // get package title
    $package = $order->package()->first();

    if (is_null($package)) {
      $queryResult['packageTitle'] = NULL;
    } else {
      $queryResult['packageTitle'] = $package->name;
    }

    return view('backend.client-service.order.details', $queryResult);
  }

  public function message($id)
  {

    $order = ServiceOrder::query()->findOrFail($id);
    $queryResult['order'] = $order;
    $language = Language::query()->where('is_default', '=', 1)->first();
    $service = $order->service()->first();
    if ($service) {
      $queryResult['serviceInfo'] = $service->content()->where('language_id', $language->id)->first();
    } else {
      // Fallback for customer offer orders (no real service)
      $queryResult['serviceInfo'] = (object)[
        'title' => $order->order_number ?? 'Custom Offer',
        'slug' => null
      ];
    }

    $messages = $order->message()->get();

    $messages->map(function ($message) {
      if ($message->person_type == 'admin') {
        $message['admin'] = $message->admin()->first();
      } else {
        $message['user'] = $message->user()->first();
      }
    });

    $queryResult['messages'] = $messages;

    $queryResult['bs'] = Basic::query()->select('pusher_key', 'pusher_cluster')->first();

    return view('backend.client-service.order.message', $queryResult);
  }

  public function storeMessage(MessageRequest $request, $id)
  {
    if ($request->hasFile('attachment')) {
      $file = $request->file('attachment');
      $fileName = UploadFile::store('./assets/file/message-files/', $file);
      $fileOriginalName = $file->getClientOriginalName();
    }

    $orderMsg = new ServiceOrderMessage();
    $orderMsg->person_id = Auth::guard('admin')->user()->id;
    $orderMsg->person_type = 'admin';
    $orderMsg->order_id = $id;
    $orderMsg->message = $request->filled('msg') ? Purifier::clean($request->msg, 'youtube') : NULL;
    $orderMsg->file_name = isset($fileName) ? $fileName : NULL;
    $orderMsg->file_original_name = isset($fileOriginalName) ? $fileOriginalName : NULL;
    $orderMsg->save();

    event(new MessageStored());

    return response()->json(['status' => 'Message stored.', 200]);
  }

  public function destroy($id)
  {
    try {
      \Log::info('AdminOrderDelete: Starting delete process', ['order_id' => $id]);
      
    $this->deleteOrder($id);
      
      \Log::info('AdminOrderDelete: Delete process completed successfully', ['order_id' => $id]);
      
      // Check if this is an AJAX request
      if (request()->ajax()) {
        return response()->json(['status' => 'success', 'message' => 'Order deleted successfully!']);
      }

    return redirect()->back()->with('success', 'Order deleted successfully!');
    } catch (\Exception $e) {
      \Log::error('AdminOrderDelete: Delete process failed', [
        'order_id' => $id,
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
      ]);
      
      // Check if this is an AJAX request
      if (request()->ajax()) {
        return response()->json(['status' => 'error', 'message' => 'Failed to delete order: ' . $e->getMessage()], 500);
      }
      
      return redirect()->back()->with('error', 'Failed to delete order: ' . $e->getMessage());
    }
  }

  public function bulkDestroy(Request $request)
  {
    $ids = $request->ids;
    $errors = [];
    $successCount = 0;

    \Log::info('AdminBulkDelete: Starting bulk delete', [
      'total_orders' => count($ids),
      'order_ids' => $ids
    ]);

    foreach ($ids as $id) {
      try {
        \Log::info('AdminBulkDelete: Attempting to delete order', ['order_id' => $id]);
        
        // Check if order exists first
        $order = ServiceOrder::find($id);
        if (!$order) {
          throw new \Exception('Order not found');
        }
        
        \Log::info('AdminBulkDelete: Order found', [
          'order_id' => $order->id,
          'order_number' => $order->order_number,
          'conversation_id' => $order->conversation_id
        ]);
        
        $this->deleteOrder($id);
        $successCount++;
        \Log::info('AdminBulkDelete: Successfully deleted order', ['order_id' => $id]);
      } catch (\Exception $e) {
        \Log::error('AdminBulkDelete: Failed to delete order', [
          'order_id' => $id,
          'error' => $e->getMessage(),
          'error_code' => $e->getCode(),
          'trace' => $e->getTraceAsString()
        ]);
        $errors[] = [
          'order_id' => $id,
          'error' => $e->getMessage()
        ];
      }
    }

    \Log::info('AdminBulkDelete: Completed bulk delete', [
      'total_orders' => count($ids),
      'success_count' => $successCount,
      'error_count' => count($errors),
      'errors' => $errors
    ]);

    if (count($errors) > 0) {
      $request->session()->flash('error', 'Some orders could not be deleted.');
      return response()->json([
        'status' => 'partial',
        'message' => 'Some orders could not be deleted.',
        'errors' => $errors,
        'success_count' => $successCount,
        'total_count' => count($ids)
      ], 207); // 207 Multi-Status
    }

    $request->session()->flash('success', 'Orders deleted successfully!');
    return response()->json(['status' => 'success'], 200);
  }

  // order deletion code
  public function deleteOrder($id)
  {
    try {
      $order = ServiceOrder::query()->find($id);
      
      if (!$order) {
        \Log::error('AdminOrderDelete: Order not found', ['order_id' => $id]);
        throw new \Exception('Order not found');
      }

      \Log::info('AdminOrderDelete: Found order', [
        'order_id' => $order->id,
        'order_number' => $order->order_number,
        'conversation_id' => $order->conversation_id,
        'is_customer_offer' => $order->conversation_id && strpos($order->conversation_id, 'customer_offer_') === 0
      ]);

      // Use a database transaction to ensure data consistency
      \DB::beginTransaction();
      
      try {
        // For customer offer orders, try to handle the relationship but don't fail if it doesn't work
        if ($order->conversation_id && strpos($order->conversation_id, 'customer_offer_') === 0) {
          try {
            $offerId = str_replace('customer_offer_', '', $order->conversation_id);
            
            \Log::info('AdminOrderDelete: Processing customer offer order', [
              'order_id' => $order->id,
              'offer_id' => $offerId
            ]);
            
            // Try to update the customer offer without foreign key checks
            $updated = \DB::update("
              UPDATE customer_offers 
              SET accepted_order_id = NULL, status = 'expired' 
              WHERE id = ?
            ", [$offerId]);
            
            if ($updated > 0) {
              \Log::info('AdminOrderDelete: Updated customer offer successfully', ['offer_id' => $offerId]);
            } else {
              // If update fails, try to delete the customer offer
              $deleted = \DB::delete("DELETE FROM customer_offers WHERE id = ?", [$offerId]);
              if ($deleted > 0) {
                \Log::info('AdminOrderDelete: Deleted customer offer successfully', ['offer_id' => $offerId]);
              } else {
                \Log::warning('AdminOrderDelete: Customer offer not found or already deleted', ['offer_id' => $offerId]);
              }
            }
          } catch (\Exception $e) {
            \Log::error('AdminOrderDelete: Error handling customer offer', [
              'error' => $e->getMessage(),
              'error_code' => $e->getCode(),
              'trace' => $e->getTraceAsString()
            ]);
            // Continue with order deletion even if customer offer handling fails
            \Log::warning('AdminOrderDelete: Continuing with order deletion despite customer offer error');
          }
        }

        // delete zip file(s) which has uploaded by user
        $informations = json_decode($order->informations);

        if (!is_null($informations) && is_array($informations)) {
          foreach ($informations as $key => $information) {
            // Check if $information is an object and has a type property
            if (is_object($information) && isset($information->type) && $information->type == 8) {
              if (isset($information->value) && !empty($information->value)) {
                @unlink(public_path('assets/file/zip-files/' . $information->value));
              }
            }
          }
        }

        // delete the receipt
        if ($order->receipt) {
          @unlink(public_path('assets/img/attachments/service/' . $order->receipt));
        }

        // delete the invoice
        if ($order->invoice) {
          @unlink(public_path('assets/file/invoices/service/' . $order->invoice));
        }

        // delete messages of this service-order
        $messages = $order->message()->get();

        foreach ($messages as $msgInfo) {
          if (!empty($msgInfo->file_name)) {
            @unlink(public_path('assets/file/message-files/' . $msgInfo->file_name));
          }

          $msgInfo->delete();
        }

        // delete the order using raw SQL to bypass any model constraints
        $deleted = \DB::delete("DELETE FROM service_orders WHERE id = ?", [$order->id]);
        
        if ($deleted > 0) {
          \DB::commit();
          \Log::info('AdminOrderDelete: Order deleted successfully', ['order_id' => $id]);
        } else {
          \DB::rollback();
          throw new \Exception('Failed to delete order from database');
        }
        
      } catch (\Exception $e) {
        \DB::rollback();
        throw $e;
      }
      
    } catch (\Exception $e) {
      \Log::error('AdminOrderDelete: Error deleting order', [
        'order_id' => $id,
        'order_number' => $order->order_number ?? 'unknown',
        'error' => $e->getMessage(),
        'error_code' => $e->getCode(),
        'trace' => $e->getTraceAsString()
      ]);
      
      // Provide more specific error message for customer offer orders
      if ($order && $order->conversation_id && strpos($order->conversation_id, 'customer_offer_') === 0) {
        throw new \Exception('Customer offer order cannot be deleted: ' . $e->getMessage());
      }
      
      throw $e;
    }
  }

  // Test method to debug customer offer deletion
  public function testCustomerOfferDeletion($orderId)
  {
    try {
      $order = ServiceOrder::find($orderId);
      
      if (!$order) {
        return response()->json(['error' => 'Order not found'], 404);
      }
      
      if (!$order->conversation_id || strpos($order->conversation_id, 'customer_offer_') !== 0) {
        return response()->json(['error' => 'Not a customer offer order'], 400);
      }
      
      $offerId = str_replace('customer_offer_', '', $order->conversation_id);
      
      // Test the customer offer deletion logic
      \DB::statement('SET FOREIGN_KEY_CHECKS = 0');
      
      try {
        $updated = \DB::table('customer_offers')
          ->where('id', $offerId)
          ->update([
            'accepted_order_id' => null,
            'status' => 'expired'
          ]);
        
        if ($updated > 0) {
          $order->delete();
          \DB::statement('SET FOREIGN_KEY_CHECKS = 1');
          return response()->json(['success' => 'Customer offer order deleted successfully']);
        } else {
          $deleted = \DB::table('customer_offers')->where('id', $offerId)->delete();
          if ($deleted > 0) {
            $order->delete();
            \DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            return response()->json(['success' => 'Customer offer and order deleted successfully']);
          } else {
            \DB::statement('SET FOREIGN_KEY_CHECKS = 1');
            return response()->json(['error' => 'Customer offer not found'], 404);
          }
        }
      } catch (\Exception $e) {
        \DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        return response()->json(['error' => $e->getMessage()], 500);
      }
      
    } catch (\Exception $e) {
      return response()->json(['error' => $e->getMessage()], 500);
    }
  }


  public function report(Request $request)
  {
    $queryResult['onlineGateways'] = OnlineGateway::query()->where('status', '=', 1)->get();
    $queryResult['offlineGateways'] = OfflineGateway::query()->where('status', '=', 1)->orderBy('serial_number', 'asc')->get();

    $from = $to = $paymentGateway = $paymentStatus = $orderStatus = null;

    if ($request->filled('payment_gateway')) {
      $paymentGateway = $request->payment_gateway;
    }
    if ($request->filled('payment_status')) {
      $paymentStatus = $request->payment_status;
    }
    if ($request->filled('order_status')) {
      $orderStatus = $request->order_status;
    }

    if ($request->filled('from') && $request->filled('to')) {
      $from = Carbon::parse($request->from)->toDateString();
      $to = Carbon::parse($request->to)->toDateString();

      $records = ServiceOrder::query()
        ->whereDate('created_at', '>=', $from)
        ->whereDate('created_at', '<=', $to)
        ->when($paymentGateway, function (Builder $query, $paymentGateway) {
          return $query->where('payment_method', '=', $paymentGateway);
        })
        ->when($paymentStatus, function (Builder $query, $paymentStatus) {
          return $query->where('payment_status', '=', $paymentStatus);
        })
        ->when($orderStatus, function (Builder $query, $orderStatus) {
          return $query->where('order_status', '=', $orderStatus);
        })
        ->select('order_number', 'name', 'email_address', 'service_id', 'package_id', 'package_price', 'addons', 'addon_price', 'tax', 'grand_total', 'currency_symbol', 'currency_symbol_position', 'payment_method', 'payment_status', 'order_status', 'created_at')
        ->orderByDesc('id');

      $collection_1 = $this->manipulateCollection($records->get());
      Session::put('service_orders', $collection_1);

      $collection_2 = $this->manipulateCollection($records->paginate(10));
      $queryResult['orders'] = $collection_2;
    } else {
      Session::put('service_orders', null);
      $queryResult['orders'] = [];
    }

    return view('backend.client-service.order.report', $queryResult);
  }

  public function manipulateCollection($orders)
  {
    $language = Language::query()->where('is_default', '=', 1)->first();

    $orders->map(function ($order) use ($language) {
      // service title
      $service = $order->service()->first();
      $order['serviceTitle'] = $service->content()->where('language_id', $language->id)->pluck('title')->first();

      // package name
      $package = $order->package()->first();

      if (is_null($package)) {
        $order['packageName'] = NULL;
      } else {
        $order['packageName'] = $package->name;
      }

      // addons name
      $addonIds = json_decode($order->addons);

      if (empty($addonIds)) {
        $addons = [];
      } else {
        $addons = [];

        foreach ($addonIds as $key => $value) {
          $addonName = $service->addon()->where('id', $value->id)->pluck('name')->first();

          array_push($addons, $addonName);
        }
      }

      $order['addonNames'] = $addons;

      // format created_at date
      $dateObj = Carbon::parse($order->created_at);
      $order['createdAt'] = $dateObj->format('M d, Y');
    });

    return $orders;
  }

  public function exportReport()
  {
    if (Session::has('service_orders')) {
      $serviceOrders = Session::get('service_orders');

      if (count($serviceOrders) == 0) {
        Session::flash('warning', 'No order found to export!');

        return redirect()->back();
      } else {
        return Excel::download(new ServiceOrdersExport($serviceOrders), 'service-orders.csv');
      }
    } else {
      Session::flash('error', 'There has no order to export.');

      return redirect()->back();
    }
  }

  public function sendMail(Request $request, $id)
  {
    // validation
    $rules = [
      'subject' => 'required|max:255'
    ];
    $validator = Validator::make($request->all(), $rules);
    if ($validator->fails()) {
      Session::flash('error', 'Subject feild is required.');
      return back();
    }
    $serviceOrder = ServiceOrder::query()->find($id);
    $mailData = [];
    $mailData['subject'] = $request->subject;

    if ($request->filled('message')) {
      $msg = $request->message;
    } else {
      $msg = '';
    }

    $mailData['body'] = $msg;

    $mailData['recipient'] = $serviceOrder->email_address;

    $mailData['sessionMessage'] = 'Mail has been sent successfully!';

    BasicMailer::sendMail($mailData);
    return redirect()->back();
  }
}
