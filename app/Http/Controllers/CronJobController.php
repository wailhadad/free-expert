<?php

namespace App\Http\Controllers;

use App\Http\Controllers\FrontEnd\ClientService\OrderProcessController;
use App\Http\Helpers\MegaMailer;
use App\Http\Helpers\SellerPermissionHelper;
use App\Jobs\SubscriptionExpiredMail;
use App\Jobs\SubscriptionReminderMail;
use App\Models\BasicSettings\Basic;
use App\Models\ClientService\ServiceOrder;
use App\Models\Membership;
use App\Models\Package;
use App\Models\PaymentGateway\OnlineGateway;
use App\Models\Seller;
use App\Notifications\MembershipExpiredNotification;
use App\Notifications\GracePeriodStartedNotification;
use App\Services\NotificationService;
use Carbon\Carbon;

class CronJobController extends Controller
{
    public function expired()
    {
        try {
            $bs = Basic::first();
            $gracePeriodMinutes = $bs->grace_period_minutes ?? 2;
            $notificationService = new NotificationService();

            // Find memberships that just expired and need to enter grace period
            // (exclude those already in grace period)
            $expired_members = Membership::where('status', 1)
                ->where('expire_date', '<', Carbon::now())
                ->where(function($query) {
                    $query->whereNull('processed_for_renewal')
                          ->orWhere('processed_for_renewal', 0);
                })
                ->where(function($query) {
                    $query->whereNull('in_grace_period')
                          ->orWhere('in_grace_period', 0);
                })
                ->get();
            foreach ($expired_members as $key => $expired_member) {
                if (!empty($expired_member->seller)) {
                    $seller = $expired_member->seller;
                    
                    // Check if seller has sufficient balance for auto-renewal
                    $package = $expired_member->package;
                    $price = $package->price;
                    
                    if ($seller->amount >= $price) {
                        // Sufficient balance - attempt auto-renewal immediately
                        \Log::info("Seller has sufficient balance for auto-renewal", [
                            'seller_id' => $seller->id,
                            'membership_id' => $expired_member->id,
                            'current_balance' => $seller->amount,
                            'package_price' => $price
                        ]);
                        
                        // Deduct price and create new membership
                        $seller->amount -= $price;
                        $seller->save();
                        
                        $now = Carbon::now();
                        $startDate = $now;
                        $expireDate = null;
                        if ($package->term == 'monthly') {
                            $expireDate = $startDate->copy()->addMonth();
                        } elseif ($package->term == 'yearly') {
                            $expireDate = $startDate->copy()->addYear();
                        } elseif ($package->term == 'lifetime') {
                            $expireDate = Carbon::maxValue();
                        }
                        
                        $newMembership = Membership::create([
                            'price' => $package->price,
                            'currency' => $bs->base_currency_text,
                            'currency_symbol' => $bs->base_currency_symbol,
                            'payment_method' => 'balance_auto',
                            'transaction_id' => uniqid(),
                            'status' => 1,
                            'receipt' => NULL,
                            'transaction_details' => NULL,
                            'settings' => null,
                            'package_id' => $package->id,
                            'seller_id' => $seller->id,
                            'start_date' => $startDate,
                            'expire_date' => $expireDate,
                            'is_trial' => 0,
                            'trial_days' => 0,
                        ]);
                        
                        // Generate invoice
                        $file_name = $this->makeInvoice([
                            'payment_method' => 'balance_auto',
                            'start_date' => $startDate,
                            'expire_date' => $expireDate,
                        ], "membership", $seller, null, $price, "balance_auto", $seller->phone, $bs->base_currency_symbol_position, $bs->base_currency_symbol, $bs->base_currency_text, $newMembership->transaction_id, $package->title, $newMembership, 'seller-memberships');
                        
                        // Send email
                        $mailer = new MegaMailer();
                        $data = [
                            'toMail' => $seller->email,
                            'toName' => $seller->username,
                            'username' => $seller->username,
                            'package_title' => $package->title,
                            'package_price' => $bs->base_currency_symbol . number_format($price, 2),
                            'activation_date' => $startDate,
                            'expire_date' => $expireDate,
                            'membership_invoice' => $file_name,
                            'membership_invoice_path' => 'seller-memberships',
                            'website_title' => $bs->website_title,
                            'templateType' => 'seller_membership_invoice',
                            'mail_subject' => __('Your Package Purchase Invoice from ') . $bs->website_title,
                        ];
                        $mailer->mailFromAdmin($data);
                        
                        // Real-time notification
                        $notificationService = new \App\Services\NotificationService();
                        $notificationService->sendRealTime($seller, [
                            'type' => 'seller_package_approved',
                            'title' => 'Your Package Payment Approved',
                            'message' => 'Your package "' . $package->title . '" has been renewed automatically from your balance.',
                            'url' => route('seller.subscription_log'),
                            'icon' => 'fas fa-check-circle',
                            'extra' => [
                                'membership_id' => $newMembership->id,
                                'package_id' => $package->id,
                                'package_title' => $package->title,
                                'price' => $price,
                                'start_date' => $startDate,
                                'expire_date' => $expireDate
                            ]
                        ]);
                        
                        // Transaction log
                        \App\Models\Transaction::create([
                            'transcation_id' => uniqid(),
                            'order_id' => $newMembership->id,
                            'transcation_type' => 5, // 5 = package purchase
                            'seller_id' => $seller->id,
                            'payment_status' => 'completed',
                            'payment_method' => 'balance_auto',
                            'grand_total' => $price,
                            'gateway_type' => 'online',
                            'currency_symbol' => $bs->base_currency_symbol,
                            'currency_symbol_position' => $bs->base_currency_symbol_position,
                        ]);
                        
                        // Mark old membership as processed
                        $expired_member->update(['processed_for_renewal' => 1]);
                        
                        \Log::info("Auto-renewal successful for seller with sufficient balance", [
                            'seller_id' => $seller->id,
                            'membership_id' => $expired_member->id,
                            'new_membership_id' => $newMembership->id,
                            'original_balance' => $seller->amount + $price,
                            'new_balance' => $seller->amount
                        ]);
                        
                    } else {
                        // Insufficient balance - start grace period
                        \Log::info("Starting grace period for seller with insufficient balance", [
                            'seller_id' => $seller->id,
                            'membership_id' => $expired_member->id,
                            'current_balance' => $seller->amount,
                            'package_price' => $price,
                            'shortfall' => $price - $seller->amount,
                            'before_grace_period_until' => $expired_member->grace_period_until,
                            'before_in_grace_period' => $expired_member->in_grace_period
                        ]);
                        
                        try {
                            $expired_member->startGracePeriod($gracePeriodMinutes);
                            
                            // Refresh the model to get updated values
                            $expired_member->refresh();
                            
                            // Send grace period notification
                            $notificationData = [
                                'type' => 'seller_membership_grace_period',
                                'title' => 'Membership in Grace Period',
                                'message' => "Your membership for package '{$expired_member->package->title}' is now in grace period. Please add funds within {$gracePeriodMinutes} minutes to avoid losing access.",
                                'url' => route('seller.plan.extend.index'),
                                'icon' => 'fas fa-clock',
                                'extra' => [
                                    'membership_id' => $expired_member->id,
                                    'package_id' => $expired_member->package_id,
                                    'package_title' => $expired_member->package->title,
                                    'expire_date' => $expired_member->expire_date,
                                    'grace_period_until' => $expired_member->grace_period_until,
                                    'grace_period_minutes' => $gracePeriodMinutes,
                                ]
                            ];

                            $notificationService->sendRealTime($seller, $notificationData);
                            
                            \Log::info("Seller membership grace period started - insufficient balance", [
                                'seller_id' => $seller->id,
                                'membership_id' => $expired_member->id,
                                'package_title' => $expired_member->package->title,
                                'current_balance' => $seller->amount,
                                'package_price' => $price,
                                'shortfall' => $price - $seller->amount,
                                'after_grace_period_until' => $expired_member->grace_period_until,
                                'after_in_grace_period' => $expired_member->in_grace_period
                            ]);
                        } catch (\Exception $e) {
                            \Log::error("Failed to start grace period", [
                                'seller_id' => $seller->id,
                                'membership_id' => $expired_member->id,
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                        }
                    }
                }
            }

            // Find memberships that have truly expired (after grace period)
            $truly_expired_members = Membership::where('status', 1)
                ->where('in_grace_period', 1)
                ->where('grace_period_until', '<', Carbon::now())
                ->where(function($query) {
                    $query->whereNull('processed_for_renewal')
                          ->orWhere('processed_for_renewal', 0);
                })
                ->get();

            foreach ($truly_expired_members as $key => $expired_member) {
                if (!empty($expired_member->seller)) {
                    $seller = $expired_member->seller;
                    $package = $expired_member->package;
                    $price = $package->price;
                    
                    // Grace period has expired - deduct balance (can go negative) and send expired email
                    $originalBalance = $seller->amount;
                    $seller->amount -= $price;
                    $seller->save();
                    
                    \Log::info("Grace period expired - balance deducted (can be negative)", [
                        'seller_id' => $seller->id,
                        'membership_id' => $expired_member->id,
                        'package_title' => $package->title,
                        'original_balance' => $originalBalance,
                        'package_price' => $price,
                        'new_balance' => $seller->amount,
                        'balance_went_negative' => $seller->amount < 0
                    ]);
                    
                    // Mark membership as processed and set pending payment if balance is negative
                    // Also store the original balance for potential restoration
                    $expired_member->update([
                        'processed_for_renewal' => 1,
                        'pending_payment' => $seller->amount < 0,
                        'original_balance' => $originalBalance
                    ]);
                    
                    // Send expiration notification
                    $notificationData = [
                        'type' => 'seller_membership_expired',
                        'title' => 'Membership Expired',
                        'message' => "Your membership for package '{$expired_member->package->title}' has expired. Please renew to continue accessing premium features.",
                        'url' => route('seller.plan.extend.index'),
                        'icon' => 'fas fa-calendar-times',
                        'extra' => [
                            'membership_id' => $expired_member->id,
                            'package_id' => $expired_member->package_id,
                            'package_title' => $expired_member->package->title,
                            'expire_date' => $expired_member->expire_date,
                        ]
                    ];

                    $notificationService->sendRealTime($seller, $notificationData);
                    
                    // Send expiration email
                    SubscriptionExpiredMail::dispatch($seller, $bs);
                    
                    \Log::info("Expiration notification and email sent for seller after grace period", [
                        'seller_id' => $seller->id,
                        'membership_id' => $expired_member->id
                    ]);
                }
            }

            // Process reminder notifications
            $remind_members = Membership::whereDate('expire_date', Carbon::now()->addDays($bs->expiration_reminder))
                ->where(function($query) {
                    $query->whereNull('reminder_sent')
                          ->orWhere('reminder_sent', 0);
                })
                ->get();

            foreach ($remind_members as $key => $remind_member) {
                if (!empty($remind_member->seller)) {
                    $seller = $remind_member->seller;

                    $nextPacakgeCount = Membership::where([
                        ['seller_id', $seller->id],
                        ['start_date', '>', Carbon::now()]
                    ])->where('status', '<>', '2')->count();

                    if ($nextPacakgeCount == 0) {
                        // Send email reminder
                        SubscriptionReminderMail::dispatch($seller, $bs, $remind_member->expire_date);

                        // Send real-time notification
                        $notificationData = [
                            'type' => 'seller_membership_reminder',
                            'title' => 'Membership Expiring Soon',
                            'message' => "Your membership for package '{$remind_member->package->title}' will expire on {$remind_member->expire_date}. Please renew to avoid service interruption.",
                            'url' => route('seller.plan.extend.index'),
                            'icon' => 'fas fa-clock',
                            'extra' => [
                                'membership_id' => $remind_member->id,
                                'package_id' => $remind_member->package_id,
                                'package_title' => $remind_member->package->title,
                                'expire_date' => $remind_member->expire_date,
                                'days_remaining' => $bs->expiration_reminder,
                            ]
                        ];

                        $notificationService->sendRealTime($seller, $notificationData);

                        // Mark reminder as sent
                        $remind_member->update(['reminder_sent' => 1]);

                        \Log::info("Seller membership reminder sent", [
                            'seller_id' => $seller->id,
                            'membership_id' => $remind_member->id,
                            'package_title' => $remind_member->package->title,
                            'expire_date' => $remind_member->expire_date
                        ]);
                    }
                }
                \Artisan::call("queue:work --stop-when-empty");
            }
        } catch (\Exception $e) {
        }
    }

    public function check_payment()
    {
        // check memberships
        $memberships = Membership::where([['payment_method', 'Iyzico'], ['status', 0]])->get();
        foreach ($memberships as $membership) {
            if (!is_null($membership->conversation_id)) {
                $result = $this->IyzicoPaymentStatus($membership->conversation_id);
                if ($result == 'success') {
                    $seller = Seller::where('id', $membership->seller_id)->first();
                    $lastMemb = Membership::where('seller_id', $membership->seller_id)->orderBy('id', 'DESC')->first();
                    $lastMemb->status = 1;
                    $lastMemb->save();
                    $activation = Carbon::parse($lastMemb->start_date);
                    $expire = Carbon::parse($lastMemb->expire_date);
                    $requestData = [
                        'payment_method' => $membership->payment_method,
                        'start_date' => $membership->start_date,
                        'expire_date' => $membership->expire_date,
                    ];
                    $password = null;
                    $amount = $membership->price;
                    $transaction_id = $membership->transaction_id;
                    $bs = Basic::first();
                    $package = Package::where('id', $membership->package_id)->first();

                    $file_name = $this->makeInvoice($requestData, "extend", $seller, $password, $amount, $requestData["payment_method"], $seller->phone, $bs->base_currency_symbol_position, $bs->base_currency_symbol, $bs->base_currency_text, $transaction_id, $package->title, $lastMemb, "seller-memberships");

                    $mailer = new MegaMailer();
                    $data = [
                        'toMail' => $seller->email,
                        'toName' => $seller->fname,
                        'username' => $seller->username,
                        'package_title' => $package->title,
                        'package_price' => ($bs->base_currency_text_position == 'left' ? $bs->base_currency_text . ' ' : '') . $package->price . ($bs->base_currency_text_position == 'right' ? ' ' . $bs->base_currency_text : ''),
                        'activation_date' => $activation->toFormattedDateString(),
                        'expire_date' => Carbon::parse($expire->toFormattedDateString())->format('Y') == '9999' ? 'Lifetime' : $expire->toFormattedDateString(),
                        'membership_invoice' => $file_name,
                        'website_title' => $bs->website_title,
                        'templateType' => 'membership_extend',
                        'type' => 'membershipExtend'
                    ];
                    $mailer->mailFromAdmin($data);
                    @unlink(public_path('assets/front/invoices/' . $file_name));

                    //store data to transaction and earnings table
                    $transaction_data = [];
                    $transaction_data['order_id'] = $lastMemb->id;
                    $transaction_data['transcation_type'] = 5;
                    $transaction_data['user_id'] = null;
                    $transaction_data['seller_id'] = $lastMemb->seller_id;
                    $transaction_data['payment_status'] = 'completed';
                    $transaction_data['payment_method'] = $lastMemb->payment_method;
                    $transaction_data['grand_total'] = $lastMemb->price;
                    $transaction_data['pre_balance'] = null;
                    $transaction_data['tax'] = null;
                    $transaction_data['after_balance'] = null;
                    $transaction_data['gateway_type'] = 'online';
                    $transaction_data['currency_symbol'] = $lastMemb->currency_symbol;
                    $transaction_data['currency_symbol_position'] = $bs->base_currency_symbol_position;
                    storeTransaction($transaction_data);
                    $data = [
                        'life_time_earning' => $lastMemb->price,
                        'total_profit' => $lastMemb->price,
                    ];
                    storeEarnings($data);
                }
            }
        }
        //check service orders 
        $service_orders = ServiceOrder::where([['payment_method', 'Iyzico'], ['payment_status', 'pending']])->get();
        foreach ($service_orders as $orderInfo) {
            if (!is_null($orderInfo->conversation_id)) {
                $result = $this->IyzicoPaymentStatus($orderInfo->conversation_id);
                if ($result == 'success') {
                    $orderProcess = new OrderProcessController();
                    // generate an invoice in pdf format
                    $invoice = $orderProcess->generateInvoice($orderInfo);

                    // then, update the invoice field info in database
                    $orderInfo->invoice = $invoice;
                    $orderInfo->payment_status = 'completed';
                    $orderInfo->save();

                    // send a mail to the customer with the invoice
                    $orderProcess->prepareMail($orderInfo);
                }
            }
        }
    }

    /*~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    ----------- Get iyzico payment status from iyzico server ---------
    ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~*/
    private function IyzicoPaymentStatus($conversation_id)
    {
        $paymentMethod = OnlineGateway::where('keyword', 'iyzico')->first();
        $paydata = $paymentMethod->convertAutoData();

        $options = new \Iyzipay\Options();
        $options->setApiKey($paydata['api_key']);
        $options->setSecretKey($paydata['secrect_key']);
        if ($paydata['sandbox_status'] == 1) {
            $options->setBaseUrl("https://sandbox-api.iyzipay.com");
        } else {
            $options->setBaseUrl("https://api.iyzipay.com"); // production mode
        }

        $request = new \Iyzipay\Request\ReportingPaymentDetailRequest();
        $request->setPaymentConversationId($conversation_id);

        $paymentResponse = \Iyzipay\Model\ReportingPaymentDetail::create($request, $options);
        $result = (array) $paymentResponse;

        foreach ($result as $key => $data) {
            $data = json_decode($data, true);
            if ($data['status'] == 'success' && !empty($data['payments'])) {
                if (is_array($data['payments'])) {
                    if ($data['payments'][0]['paymentStatus'] == 1) {
                        return 'success';
                    } else {
                        return 'not found';
                    }
                } else {
                    return 'not found';
                }
            } else {
                return 'not found';
            }
        }
        return 'not found';
    }
}
