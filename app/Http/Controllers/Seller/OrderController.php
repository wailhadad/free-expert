<?php

namespace App\Http\Controllers\Seller;

use App\Events\MessageStored;
use App\Exports\ServiceOrdersExport;
use App\Http\Controllers\Controller;
use App\Http\Helpers\BasicMailer;
use App\Http\Helpers\SellerPermissionHelper;
use App\Http\Helpers\UploadFile;
use App\Http\Requests\MessageRequest;
use App\Models\BasicSettings\Basic;
use App\Models\ClientService\ServiceOrder;
use App\Models\ClientService\ServiceOrderMessage;
use App\Models\ClientService\ServiceReview;
use App\Models\Language;
use App\Models\Seller;
use App\Models\PaymentGateway\OfflineGateway;
use App\Models\PaymentGateway\OnlineGateway;
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
        $orderNumber = $paymentStatus = $orderStatus = null;

        if ($request->filled('order_no')) {
            $orderNumber = $request['order_no'];
        }
        if ($request->filled('payment_status')) {
            $paymentStatus = $request['payment_status'];
        }
        if ($request->filled('order_status')) {
            $orderStatus = $request['order_status'];
        }

        $orders = ServiceOrder::query()->where('seller_id', Auth::guard('seller')->user()->id)
            ->when($orderNumber, function (Builder $query, $orderNumber) {
                return $query->where('order_number', 'like', '%' . $orderNumber . '%');
            })
            ->when($paymentStatus, function (Builder $query, $paymentStatus) {
                return $query->where('payment_status', '=', $paymentStatus);
            })
            ->when($orderStatus, function (Builder $query, $orderStatus) {
                return $query->where('order_status', '=', $orderStatus);
            })
            ->orderByDesc('id')
            ->paginate(10);

        $language = Language::query()->where('is_default', '=', 1)->first();

        // Use foreach instead of map() to avoid issues with paginated collections
        foreach ($orders as $order) {
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
        }

        return view('seller.order.index', compact('orders'));
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
                
                \Log::info('OrderInvoice: PDF generated successfully', [
                    'order_id' => $order->id,
                    'invoice' => $invoice
                ]);
            } catch (\Exception $e) {
                \Log::error('OrderInvoice: PDF generation failed', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage()
                ]);
                // Continue without invoice if generation fails
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
        } else {
            if ($order->invoice) {
                @unlink(public_path('assets/file/invoices/order-invoices/' . $order->invoice));
            }
            $order->update([
                'payment_status' => 'rejected',
                'invoice' => null
            ]);
            $statusMsg = 'payment has been rejected.';
        }

        // Send email immediately to ensure it works
        try {
                    // Send email to customer
        // Get the real user's name, not the order name (which can be subuser name)
        $realUserName = $order->real_user_name;
            
            $mailData = [
              'subject' => 'Notification of payment status',
              'body' => 'Hi ' . $realUserName . ',<br/><br/>This email is to notify the payment status of your order: #' . $order->order_number . '.<br/>' . $statusMsg,
              'recipient' => $order->email_address,
              'sessionMessage' => 'Payment status updated & mail has been sent successfully!',
            ];

            // Add invoice attachment if available
            if (isset($invoice) && $invoice) {
              $mailData['invoice'] = public_path('assets/file/invoices/order-invoices/' . $invoice);
            }

            // Check SMTP configuration before sending
            $smtpInfo = \App\Models\BasicSettings\Basic::select('smtp_status', 'smtp_host', 'from_mail')->first();
            if ($smtpInfo && $smtpInfo->smtp_status == 1) {
              // Only send email if recipient email is valid
              if (!empty($mailData['recipient']) && filter_var($mailData['recipient'], FILTER_VALIDATE_EMAIL)) {
                \App\Http\Helpers\BasicMailer::sendMail($mailData);
                \Log::info('OrderPayment: Email sent via SMTP', [
                    'order_id' => $order->id,
                    'recipient' => $order->email_address,
                    'smtp_host' => $smtpInfo->smtp_host
                ]);
              } else {
                \Log::warning('OrderPayment: Skipping email - invalid recipient email', [
                    'order_id' => $order->id,
                    'recipient' => $order->email_address ?? 'null'
                ]);
              }
            } else {
              \Log::warning('OrderPayment: SMTP not configured, email not sent', [
                  'order_id' => $order->id,
                  'recipient' => $order->email_address,
                  'smtp_status' => $smtpInfo ? $smtpInfo->smtp_status : 'null'
              ]);
            }
            
            \Log::info('OrderPayment: Email sent successfully', [
                'order_id' => $order->id,
                'recipient' => $order->email_address
            ]);
        } catch (\Exception $e) {
            \Log::error('OrderPayment: Email sending failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
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
        $directory = './assets/file/invoices/order-invoices/';

        @mkdir(public_path($directory), 0775, true);

        $fileLocation = $directory . $invoiceName;
        $arrData['orderInfo'] = $order;

        // Get website info for logo and title
        $websiteInfo = \App\Models\BasicSettings\Basic::first();
        $arrData['orderInfo']->logo = $websiteInfo->logo;
        $arrData['orderInfo']->website_title = $websiteInfo->website_title;

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

        \Log::info('OrderInvoice: Starting PDF generation', [
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

            \Log::info('OrderInvoice: PDF generated successfully', [
                'order_id' => $order->id,
                'file_location' => public_path($fileLocation),
                'file_exists' => file_exists(public_path($fileLocation))
            ]);

            return $invoiceName;
        } catch (\Exception $e) {
            \Log::error('OrderInvoice: PDF generation failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
                'file_location' => public_path($fileLocation)
            ]);
            throw $e;
        }
    }

    public function show($id)
    {
        $order = ServiceOrder::query()->where([['id', $id], ['seller_id', Auth::guard('seller')->user()->id]])->firstOrFail();
        $queryResult['orderInfo'] = $order;

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

        $queryResult['customerAvatar'] = $order->subuser ? $order->subuser->image : ($order->user ? $order->user->image : null);

        return view('seller.order.details', $queryResult);
    }

    public function message($id)
    {
        $order = ServiceOrder::query()->where([['id', $id], ['seller_id', Auth::guard('seller')->user()->id]])->firstOrFail();

        //check live chat status active or not for this user
        if (!is_null($order->seller_id)) {
            // First try the stored membership ID, if that fails, check current active membership
            $checkPermission = SellerPermissionHelper::getPackageInfo($order->seller_id, $order->seller_membership_id);
            
            // If stored membership check fails, check current active membership
            if ($checkPermission != true) {
                $currentMembership = SellerPermissionHelper::userPackage($order->seller_id);
                if ($currentMembership) {
                    $checkPermission = SellerPermissionHelper::getPackageInfoByMembership($currentMembership->id);
                }
            }
            
            if ($checkPermission != true) {
                Session::flash('success', 'Live chat is not active for this order.');
                return redirect()->route('seller.dashboard');
            }
        }

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
            } elseif ($message->person_type == 'seller') {
                $message['seller'] = $message->seller()->first();
            } else {
                $message['user'] = $message->user()->first();
            }
        });

        $queryResult['messages'] = $messages;

        $queryResult['bs'] = Basic::query()->select('pusher_key', 'pusher_cluster')->first();

        return view('seller.order.message', $queryResult);
    }

    public function storeMessage(MessageRequest $request, $id)
    {
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $fileName = UploadFile::store('./assets/file/message-files/', $file);
            $fileOriginalName = $file->getClientOriginalName();
        }

        $orderMsg = new ServiceOrderMessage();
        $orderMsg->person_id = Auth::guard('seller')->user()->id;
        $orderMsg->person_type = 'seller';
        $orderMsg->order_id = $id;
        $orderMsg->message = $request->filled('msg') ? Purifier::clean($request->msg, 'youtube') : null;
        $orderMsg->file_name = isset($fileName) ? $fileName : null;
        $orderMsg->file_original_name = isset($fileOriginalName) ? $fileOriginalName : null;
        $orderMsg->save();

        // Get order details for notification
        $order = ServiceOrder::findOrFail($id);
        $seller = Auth::guard('seller')->user();
        
        // Send notification to user using NotificationService for real-time delivery
        if ($order->user_id) {
            $user = \App\Models\User::find($order->user_id);
            if ($user) {
                $notificationService = new \App\Services\NotificationService();
                $notificationService->sendRealTime($user, [
                    'type' => 'chat',
                    'title' => 'New Message from Seller',
                    'message' => "You have received a new message from {$seller->username} regarding order #{$order->order_number}",
                    'url' => route('user.service_order.message', ['id' => $order->id]),
                    'icon' => 'fas fa-comment',
                    'extra' => [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'seller_name' => $seller->username,
                        'message_preview' => $request->filled('msg') ? substr($request->msg, 0, 100) : 'File attachment',
                        'has_attachment' => $request->hasFile('attachment')
                    ],
                ]);
            }
        }

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
        try {
        $order = ServiceOrder::query()->find($id);
            
            if (!$order) {
                \Log::error('SellerOrderDelete: Order not found', ['order_id' => $id]);
                throw new \Exception('Order not found');
            }

            \Log::info('SellerOrderDelete: Found order', [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'conversation_id' => $order->conversation_id,
                'is_customer_offer' => $order->conversation_id && strpos($order->conversation_id, 'customer_offer_') === 0
            ]);

            // Use a database transaction to ensure data consistency
            \DB::beginTransaction();
            
            try {
                // Check if this is a customer offer order and handle the relationship
                if ($order->conversation_id && strpos($order->conversation_id, 'customer_offer_') === 0) {
                    try {
                        $offerId = str_replace('customer_offer_', '', $order->conversation_id);
                        
                        \Log::info('SellerOrderDelete: Processing customer offer order', [
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
                            \Log::info('SellerOrderDelete: Updated customer offer successfully', ['offer_id' => $offerId]);
                        } else {
                            // If update fails, try to delete the customer offer
                            $deleted = \DB::delete("DELETE FROM customer_offers WHERE id = ?", [$offerId]);
                            if ($deleted > 0) {
                                \Log::info('SellerOrderDelete: Deleted customer offer successfully', ['offer_id' => $offerId]);
                            } else {
                                \Log::warning('SellerOrderDelete: Customer offer not found or already deleted', ['offer_id' => $offerId]);
                            }
                        }
                        
                    } catch (\Exception $e) {
                        \Log::error('SellerOrderDelete: Error handling customer offer', [
                            'error' => $e->getMessage(),
                            'error_code' => $e->getCode(),
                            'trace' => $e->getTraceAsString()
                        ]);
                        // Continue with order deletion even if customer offer handling fails
                        \Log::warning('SellerOrderDelete: Continuing with order deletion despite customer offer error');
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

            // delete review of this service-order
            $review = ServiceReview::query()->where('user_id', '=', $order->user_id)
                ->where('service_id', '=', $order->service_id)
                ->first();

            if (!empty($review)) {
                $review->delete();
            }

                // delete the order using raw SQL to bypass any model constraints
                $deleted = \DB::delete("DELETE FROM service_orders WHERE id = ?", [$order->id]);
                
                if ($deleted > 0) {
                    \DB::commit();
                    \Log::info('SellerOrderDelete: Order deleted successfully', ['order_id' => $id]);
                } else {
                    \DB::rollback();
                    throw new \Exception('Failed to delete order from database');
                }
                
            } catch (\Exception $e) {
                \DB::rollback();
                throw $e;
            }
            
        } catch (\Exception $e) {
            \Log::error('SellerOrderDelete: Error deleting order', [
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
                ->where('seller_id', Auth::guard('seller')->user()->id)
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

        return view('seller.order.report', $queryResult);
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
            return response()->json(['errors' => $validator->getMessageBag()], 400);
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

        // Always send to main user's email_address, even if order has subuser_id
        $mainUser = $serviceOrder->user;
        $recipientEmail = ($mainUser && !empty($mainUser->email_address)) ? $mainUser->email_address : null;

        if ($recipientEmail) {
            $mailData['recipient'] = $recipientEmail;
            $mailData['sessionMessage'] = 'Mail has been sent successfully!';

            // Only send email if recipient email is valid
            if (!empty($mailData['recipient']) && filter_var($mailData['recipient'], FILTER_VALIDATE_EMAIL)) {
                BasicMailer::sendMail($mailData);
            } else {
                \Log::warning('SellerOrderMail: Skipping email - invalid recipient email', [
                    'order_id' => $serviceOrder->id,
                    'recipient' => $mailData['recipient'] ?? 'null'
                ]);
                Session::flash('warning', 'Mail could not be sent. Invalid recipient email address.');
            }
        } else {
            \Log::warning('SellerOrderMail: No valid recipient email for order', [
                'order_id' => $serviceOrder->id,
                'user_id' => $serviceOrder->user_id,
                'subuser_id' => $serviceOrder->subuser_id,
            ]);
            Session::flash('warning', 'Mail could not be sent. No valid recipient email address.');
        }
        
        return redirect()->back();
    }
}
