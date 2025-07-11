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
use Maatwebsite\Excel\Facades\Excel;
use Mews\Purifier\Facades\Purifier;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
  public function orders(Request $request)
  {
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

    $orders->map(function ($order) use ($language) {
      $service = $order->service()->first();
      $order['serviceTitle'] = $service->content()->where('language_id', $language->id)->pluck('title')->first();
      $order['serviceSlug'] = $service->content()->where('language_id', $language->id)->pluck('slug')->first();

      $package = $order->package()->first();

      if (is_null($package)) {
        $order['packageName'] = NULL;
      } else {
        $order['packageName'] = $package->name;
      }
    });

    $sellers = Seller::select('id', 'username')->where('id', '!=', 0)->get();

    return view('backend.client-service.order.index', compact('orders', 'sellers'));
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

    $collection->map(function ($order) use ($language) {
      $service = $order->service()->first();
      $order['serviceTitle'] = $service->content()->where('language_id', $language->id)->pluck('title')->first();
      $order['serviceSlug'] = $service->content()->where('language_id', $language->id)->pluck('slug')->first();
    });
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
        
        \Log::info('AdminOrderInvoice: PDF generated successfully', [
          'order_id' => $order->id,
          'invoice' => $invoice
        ]);
      } catch (\Exception $e) {
        \Log::error('AdminOrderInvoice: PDF generation failed', [
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
          $user->notify(new \App\Notifications\OrderNotification([
            'title' => 'Payment Completed',
            'message' => "Payment for order #{$order->order_number} ({$serviceName}) has been completed successfully. Amount: {$order->currency_symbol}{$order->grand_total}",
            'url' => route('user.service_order.details', ['id' => $order->id]),
            'icon' => 'fas fa-credit-card',
            'extra' => $notificationData,
          ]));
        }

        // Notify seller about payment completion
        if ($order->seller_id) {
          $seller = Seller::find($order->seller_id);
          if ($seller) {
            $notificationData['seller_name'] = $seller->username;
            $seller->notify(new \App\Notifications\OrderNotification([
              'title' => 'Payment Received',
              'message' => "Payment received for order #{$order->order_number} ({$serviceName}). Amount: {$order->currency_symbol}{$order->grand_total}",
              'url' => route('seller.service_order.details', ['id' => $order->id]),
              'icon' => 'fas fa-credit-card',
              'extra' => $notificationData,
            ]));
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
          \Log::info('AdminOrderPayment: Email sent via SMTP', [
            'order_id' => $order->id,
              'recipient' => $recipientEmail,
            'smtp_host' => $smtpInfo->smtp_host
          ]);
        } else {
          \Log::warning('AdminOrderPayment: SMTP not configured, email not sent', [
            'order_id' => $order->id,
              'recipient' => $recipientEmail,
            'smtp_status' => $smtpInfo ? $smtpInfo->smtp_status : 'null'
            ]);
          }
        } else {
          \Log::warning('Order email not sent: No valid recipient email for order', [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
            'subuser_id' => $order->subuser_id,
          ]);
        }
        
        \Log::info('AdminOrderPayment: Notifications and email sent successfully', [
          'order_id' => $order->id,
          'status' => 'completed'
        ]);
      } catch (\Exception $e) {
        \Log::error('AdminOrderPayment: Notifications/Email sending failed', [
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
          $user->notify(new \App\Notifications\OrderNotification([
            'title' => 'Payment Pending',
            'message' => "Payment for order #{$order->order_number} ({$serviceName}) is now pending. Amount: {$order->currency_symbol}{$order->grand_total}",
            'url' => route('user.service_order.details', ['id' => $order->id]),
            'icon' => 'fas fa-clock',
            'extra' => $notificationData,
          ]));
        }

        // Send email to customer
        $mailData = [
          'subject' => 'Notification of payment status',
          'body' => 'Hi ' . $order->name . ',<br/><br/>This email is to notify the payment status of your order: #' . $order->order_number . '.<br/>' . $statusMsg,
          'recipient' => $order->email_address,
          'sessionMessage' => 'Payment status updated & mail has been sent successfully!',
        ];

        BasicMailer::sendMail($mailData);
        
        \Log::info('AdminOrderPayment: Notifications and email sent successfully', [
          'order_id' => $order->id,
          'status' => 'pending'
        ]);
      } catch (\Exception $e) {
        \Log::error('AdminOrderPayment: Notifications/Email sending failed', [
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
          $user->notify(new \App\Notifications\OrderNotification([
            'title' => 'Payment Rejected',
            'message' => "Payment for order #{$order->order_number} ({$serviceName}) has been rejected. Please contact support for assistance.",
            'url' => route('user.service_order.details', ['id' => $order->id]),
            'icon' => 'fas fa-times-circle',
            'extra' => $notificationData,
          ]));
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
        
        \Log::info('AdminOrderPayment: Notifications and email sent successfully', [
          'order_id' => $order->id,
          'status' => 'rejected'
        ]);
      } catch (\Exception $e) {
        \Log::error('AdminOrderPayment: Notifications/Email sending failed', [
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
    \Log::info('AdminOrderInvoice: Logo debugging', [
        'logo_from_db' => $websiteInfo->logo,
        'logo_assigned_to_order' => $arrData['orderInfo']->logo,
        'website_title' => $websiteInfo->website_title
    ]);

    // get language
    $language = Language::query()->where('is_default', '=', 1)->first();

    // get service title
    $service = $order->service()->first();
    $arrData['serviceTitle'] = $service->content()->where('language_id', $language->id)->pluck('title')->first();

    // get package title
    $package = $order->package()->first();

    if (is_null($package)) {
      $arrData['packageTitle'] = NULL;
    } else {
      $arrData['packageTitle'] = $package->name;
    }

    \Log::info('AdminOrderInvoice: Starting PDF generation', [
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

      \Log::info('AdminOrderInvoice: PDF generated successfully', [
        'order_id' => $order->id,
        'file_location' => public_path($fileLocation),
        'file_exists' => file_exists(public_path($fileLocation))
      ]);

      return $invoiceName;
    } catch (\Exception $e) {
      \Log::error('AdminOrderInvoice: PDF generation failed', [
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
          $user->notify(new \App\Notifications\OrderNotification([
            'title' => 'Order Completed',
            'message' => "Your order #{$order->order_number} for service: {$serviceName} has been completed successfully!",
            'url' => route('user.service_order.details', ['id' => $order->id]),
            'icon' => 'fas fa-check-circle',
            'extra' => $notificationData,
          ]));
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
          $user->notify(new \App\Notifications\OrderNotification([
            'title' => 'Order Rejected',
            'message' => "Your order #{$order->order_number} for service: {$serviceName} has been rejected. Please contact support for more information.",
            'url' => route('user.service_order.details', ['id' => $order->id]),
            'icon' => 'fas fa-times-circle',
            'extra' => $notificationData,
          ]));
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

    $language = Language::query()->where('is_default', '=', 1)->first();

    // get service title
    $service = $order->service()->first();
    $queryResult['serviceTitle'] = $service->content()->where('language_id', $language->id)->select('title', 'slug')->first();

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
    $queryResult['serviceInfo'] = $service->content()->where('language_id', $language->id)->first();

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
    $this->deleteOrder($id);

    return redirect()->back()->with('success', 'Order deleted successfully!');
  }

  public function bulkDestroy(Request $request)
  {
    $ids = $request->ids;

    foreach ($ids as $id) {
      $this->deleteOrder($id);
    }

    $request->session()->flash('success', 'Orders deleted successfully!');

    return response()->json(['status' => 'success'], 200);
  }

  // order deletion code
  public function deleteOrder($id)
  {
    $order = ServiceOrder::query()->find($id);

    // delete zip file(s) which has uploaded by user
    $informations = json_decode($order->informations);

    if (!is_null($informations)) {
      foreach ($informations as $key => $information) {
        if ($information->type == 8) {
          @unlink(public_path('assets/file/zip-files/' . $information->value));
        }
      }
    }

    // delete the receipt
    @unlink(public_path('assets/img/attachments/service/' . $order->receipt));

    // delete the invoice
    @unlink(public_path('assets/file/invoices/service/' . $order->invoice));

    // delete messages of this service-order
    $messages = $order->message()->get();

    foreach ($messages as $msgInfo) {
      if (!empty($msgInfo->file_name)) {
        @unlink(public_path('assets/file/message-files/' . $msgInfo->file_name));
      }

      $msgInfo->delete();
    }

    // delete review of this service-order
    $review = ServiceReview::query()->where('user_id', '=', $order->user_id)
      ->where('service_id', '=', $order->service_id)
      ->first();

    if (!empty($review)) {
      $review->delete();
    }

    // delete service-order
    $order->delete();
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
