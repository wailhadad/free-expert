<?php

namespace App\Http\Controllers\Payment;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Seller\SellerCheckoutController;
use App\Http\Controllers\FrontEnd\UserPackageController;
use App\Http\Helpers\MegaMailer;
use App\Http\Helpers\SellerPermissionHelper;
use App\Http\Helpers\UserPermissionHelper;
use App\Models\BasicSettings\Basic;
use App\Models\Package;
use App\Models\UserPackage;
use PHPMailer\PHPMailer\Exception;
use Cartalyst\Stripe\Laravel\Facades\Stripe;
use App\Models\PaymentGateway\OnlineGateway;
use App\Models\SellerInfo;
use Carbon\Carbon;
use Config;
use Illuminate\Support\Facades\Session;

class StripeController extends Controller
{
    public function __construct()
    {
        //Set Spripe Keys
        $stripe = OnlineGateway::where('keyword', 'stripe')->first();
        $stripeConf = json_decode($stripe->information, true);
        Config::set('services.stripe.key', $stripeConf["key"]);
        Config::set('services.stripe.secret', $stripeConf["secret"]);
    }

    public function paymentProcess(Request $request, $_amount, $_title, $_success_url, $_cancel_url)
    {

        $title = $_title;
        $price = $_amount;
        $price = round($price, 2);
        $cancel_url = $_cancel_url;

        Session::put('request', $request->all());

        $stripe = Stripe::make(Config::get('services.stripe.secret'));
        try {

            $token = $request->stripeToken;

            if (!isset($token)) {
                return back()->with('error', 'Token Problem With Your Token.');
            }
            $sellerInfo = SellerInfo::where('seller_id', $request->seller_id)->first();

            $charge = $stripe->charges()->create([
                'source' => $token,
                'currency' =>  "USD",
                'amount' => $price,
                'description' => $title,
                'receipt_email' => $request->email,
                'metadata' => [
                    'customer_name' => $sellerInfo != null ? $sellerInfo->name : '',
                ]
            ]);


            if ($charge['status'] == 'succeeded') {
                $paymentFor = Session::get('paymentFor');
                
                \Log::info('Stripe payment success', [
                    'paymentFor' => $paymentFor,
                    'request' => $request->all(),
                    'session_id' => session()->getId()
                ]);
                
                $package = Package::find($request->package_id);
                $transaction_id = SellerPermissionHelper::uniqidReal(8);
                $transaction_details = json_encode($charge);

                $bs = Basic::first();

                if ($paymentFor == "membership") {
                    $amount = $request->price;
                    $password = $request->password;
                    $checkout = new SellerCheckoutController();
                    
                    // Use database transaction to ensure atomicity
                    \DB::transaction(function () use ($request, $transaction_id, $transaction_details, $amount, $bs, $password, $package) {
                        $user = $checkout->store($request, $transaction_id, $transaction_details, $amount, $bs, $password);

                        $lastMemb = $user->memberships()->orderBy('id', 'DESC')->first();
                        
                        // Log the membership creation for debugging
                        \Log::info('Stripe payment: Membership created', [
                            'membership_id' => $lastMemb->id,
                            'seller_id' => $lastMemb->seller_id,
                            'transaction_id' => $transaction_id
                        ]);
                        
                        $activation = Carbon::parse($lastMemb->start_date);
                        $expire = Carbon::parse($lastMemb->expire_date);
                        $file_name = $this->makeInvoice($request, "membership", $user, $password, $amount, "Stripe", $request['phone'], $bs->base_currency_symbol_position, $bs->base_currency_symbol, $bs->base_currency_text, $transaction_id, $package->title, $lastMemb, "seller-memberships");

                        $basicMail = new BasicMailer();
                        $data = [
                            'invoice' => public_path('assets/file/invoices/seller-memberships/' . $file_name),
                            'recipient' => $user->email,
                            'subject' => "You made your membership purchase successful",
                            'body' => "You made a payment. This is a confirmation mail from us. Please see the invoice attachment below"
                        ];
                        $basicMail->sendMail($data);

                        //store data to transaction and earnings table
                        $transaction_data = [];
                        $transaction_data['order_id'] = $lastMemb->id;
                        $transaction_data['transcation_type'] = 5;
                        $transaction_data['user_id'] = null;
                        $transaction_data['seller_id'] = $lastMemb->seller_id;
                        $transaction_data['payment_status'] = 'completed';
                        $transaction_data['payment_method'] = $lastMemb->payment_method ?: 'Stripe';
                        $transaction_data['grand_total'] = $lastMemb->price;
                        $transaction_data['pre_balance'] = null;
                        $transaction_data['tax'] = null;
                        $transaction_data['after_balance'] = null;
                        $transaction_data['gateway_type'] = 'online';
                        $transaction_data['currency_symbol'] = $lastMemb->currency_symbol;
                        $transaction_data['currency_symbol_position'] = $bs->base_currency_symbol_position;
                        
                        // Log the transaction data for debugging
                        \Log::info('Stripe payment: Creating transaction', $transaction_data);
                        
                        storeTransaction($transaction_data);
                        $data = [
                            'life_time_earning' => $lastMemb->price,
                            'total_profit' => $lastMemb->price,
                        ];
                        storeEarnings($data);
                        
                        \Log::info('Stripe payment: Transaction created successfully', [
                            'membership_id' => $lastMemb->id,
                            'transaction_data' => $transaction_data
                        ]);
                    });

                    session()->flash('success', 'Your payment has been completed.');
                    Session::forget('request');
                    Session::forget('paymentFor');
                    return redirect()->route('success.page');
                } elseif ($paymentFor == "extend") {
                    $amount = $request['price'];
                    $password = uniqid('qrcode');
                    $checkout = new SellerCheckoutController();
                    $user = $checkout->store($request, $transaction_id, $transaction_details, $amount, $bs, $password);

                    $lastMemb = $user->memberships()->orderBy('id', 'DESC')->first();
                    $activation = Carbon::parse($lastMemb->start_date);
                    $expire = Carbon::parse($lastMemb->expire_date);
                                            $file_name = $this->makeInvoice($request, "extend", $user, $password, $amount, $request["payment_method"], $user->phone, $bs->base_currency_symbol_position, $bs->base_currency_symbol, $bs->base_currency_text, $transaction_id, $package->title, $lastMemb, "seller-memberships");

                    $mailer = new MegaMailer();
                    $data = [
                        'toMail' => $user->email,
                        'toName' => $user->fname,
                        'username' => $user->username,
                        'package_title' => $package->title,
                        'package_price' => ($bs->base_currency_text_position == 'left' ? $bs->base_currency_text . ' ' : '') . $package->price . ($bs->base_currency_text_position == 'right' ? ' ' . $bs->base_currency_text : ''),
                        'activation_date' => $activation->toFormattedDateString(),
                        'expire_date' => Carbon::parse($expire->toFormattedDateString())->format('Y') == '9999' ? 'Lifetime' : $expire->toFormattedDateString(),
                        'membership_invoice' => $file_name,
                        'membership_invoice_path' => 'seller-memberships',
                        'website_title' => $bs->website_title,
                        'templateType' => 'seller_membership_extend',
                        'type' => 'membershipExtend'
                    ];
                    \Log::info('Sending seller extension email', $data);
                    $mailer->mailFromAdmin($data);
                                        // Notify all admins of seller package extension
                    $admins = \App\Models\Admin::all();
                    foreach ($admins as $admin) {
                        $notificationService = new \App\Services\NotificationService();
                        $notificationService->sendRealTime($admin, [
                            'type' => 'seller_package_extension',
                            'title' => 'Seller Package Extension',
                            'message' => 'Seller ' . $user->username . ' extended the package: ' . $package->title,
                            'url' => route('admin.payment-log.index'),
                            'icon' => 'fas fa-sync-alt',
                            'extra' => [
                                'seller_id' => $user->id,
                                'package_id' => $package->id,
                                'package_title' => $package->title,
                                'price' => $amount,
                                'payment_method' => 'Stripe'
                            ]
                        ]);
                    }

                    //store data to transaction and earnings table
                    $transaction_data = [];
                    $transaction_data['order_id'] = $lastMemb->id;
                    $transaction_data['transcation_type'] = 5;
                    $transaction_data['user_id'] = null;
                    $transaction_data['seller_id'] = $lastMemb->seller_id;
                    $transaction_data['payment_status'] = 'completed';
                    $transaction_data['payment_method'] = $lastMemb->payment_method ?: 'Stripe';
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

                    session()->flash('success', 'Your payment has been completed.');
                    Session::forget('request');
                    Session::forget('paymentFor');
                    return redirect()->route('success.page');
                }
            }
        } catch (Exception $e) {
            return redirect($cancel_url)->with('error', $e->getMessage());
        } catch (\Cartalyst\Stripe\Exception\CardErrorException $e) {
            return redirect($cancel_url)->with('error', $e->getMessage());
        } catch (\Cartalyst\Stripe\Exception\MissingParameterException $e) {
            return redirect($cancel_url)->with('error', $e->getMessage());
        }
        return redirect($cancel_url)->with('error', 'Please Enter Valid Credit Card Informations.');
    }

    public function cancelPayment()
    {
        $requestData = Session::get('request');
        $paymentFor = Session::get('paymentFor');
        session()->flash('error', 'Payment has been canceled');
        if ($paymentFor == "membership") {
            return redirect()->route('front.register.view', ['status' => $requestData['package_type'], 'id' => $requestData['package_id']])->withInput($requestData);
        } else {
            return redirect()->route('seller.plan.extend.checkout', ['package_id' => $requestData['package_id']])->withInput($requestData);
        }
    }


    public function userPackageSuccess(Request $request)
    {
        $requestData = Session::get('request');
        $bs = Basic::first();
        
        if (Session::get('paymentFor') == 'user_package') {
            $package = \App\Models\UserPackage::find($requestData['package_id']);
            $transaction_id = \App\Http\Helpers\UserPermissionHelper::uniqidReal(8);
            $transaction_details = 'Payment completed via Stripe';
            
            $amount = $requestData['price'];
            $password = uniqid('qrcode');
            $checkout = new \App\Http\Controllers\FrontEnd\UserPackageController();
            
            $user = $checkout->store($requestData, $transaction_id, $transaction_details, $amount, $bs, $password);
            
            $lastMemb = $user->userMemberships()->orderBy('id', 'DESC')->first();
            $activation = Carbon::parse($lastMemb->start_date);
            $expire = Carbon::parse($lastMemb->expire_date);
            
            $file_name = $checkout->makeInvoice($requestData, 'user_package', $user, $password, $amount, $requestData['payment_method'], $user->phone, $bs->base_currency_symbol_position, $bs->base_currency_symbol, $bs->base_currency_text, $transaction_id, $package->title, $lastMemb);
            
            $mailer = new MegaMailer();
            $data = [
                'toMail' => $user->email,
                'toName' => $user->fname,
                'username' => $user->username,
                'package_title' => $package->title,
                'package_price' => ($bs->base_currency_text_position == 'left' ? $bs->base_currency_text . ' ' : '') . $package->price . ($bs->base_currency_text_position == 'right' ? ' ' . $bs->base_currency_text : ''),
                'activation_date' => $activation->toFormattedDateString(),
                'expire_date' => Carbon::parse($expire->toFormattedDateString())->format('Y') == '9999' ? 'Lifetime' : $expire->toFormattedDateString(),
                'membership_invoice' => $file_name,

                'membership_invoice_path' => 'seller-memberships',

                'website_title' => $bs->website_title,
                'templateType' => 'user_package_purchase',
                'type' => 'userPackagePurchase'
            ];
            $mailer->mailFromAdmin($data);
                        // Create transaction record for user package purchase
            storeUserPackageTransaction($lastMemb, $requestData['payment_method'], $bs);
            
            session()->flash('success', 'Your payment has been completed.');
            Session::forget('request');
            Session::forget('paymentFor');
            return redirect()->route('user.packages.success');
        }
        
        return redirect()->route('user.packages.index');
    }

    public function userPackageCancel()
    {
        $requestData = Session::get('request');
        $paymentFor = Session::get('paymentFor');
        session()->flash('warning', __('cancel_payment'));
        if ($paymentFor == 'user_package') {
            return redirect()->route('user.packages.checkout', ['id' => $requestData['package_id']])->withInput($requestData);
        }
        return redirect()->route('user.packages.index');
    }
}
