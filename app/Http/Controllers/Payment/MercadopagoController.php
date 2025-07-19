<?php

namespace App\Http\Controllers\Payment;


use App\Models\Package;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Seller\SellerCheckoutController;
use App\Http\Helpers\MegaMailer;
use App\Http\Helpers\SellerPermissionHelper;
use App\Models\BasicSettings\Basic;
use App\Models\Membership;
use App\Models\PaymentGateway\OnlineGateway;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class MercadopagoController extends Controller
{
    private $access_token;
    private $sandbox;

    public function __construct()
    {
        $data = OnlineGateway::whereKeyword('mercadopago')->first();
        $paydata = $data->convertAutoData();
        $this->access_token = $paydata['token'];
        $this->sandbox = $paydata['sandbox_status'];
    }

    public function paymentProcess(Request $request, $_amount, $_success_url, $_cancel_url, $email, $_title, $_description, $bex)
    {
        $return_url = $_success_url;
        $cancel_url = $_cancel_url;
        $notify_url = $_success_url;

        $curl = curl_init();
        $preferenceData = [
            'items' => [
                [
                    'id' => uniqid("mercadopago-"),
                    'title' => $_title,
                    'description' => $_description,
                    'quantity' => 1,
                    'currency_id' => "BRL", //unfortunately mercadopago only support BRL currency
                    'unit_price' => round($_amount, 2),
                ]
            ],
            'payer' => [
                'email' => $email,
            ],
            'back_urls' => [
                'success' => $return_url,
                'pending' => '',
                'failure' => $cancel_url,
            ],
            'notification_url' => $notify_url,
            'auto_return' => 'approved',

        ];

        $httpHeader = [
            "Content-Type: application/json",
        ];
        $url = "https://api.mercadopago.com/checkout/preferences?access_token=" . $this->access_token;
        $opts = [
            CURLOPT_URL => $url,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($preferenceData, true),
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => $httpHeader
        ];

        curl_setopt_array($curl, $opts);
        $response = curl_exec($curl);
        $payment = json_decode($response, true);
        $err = curl_error($curl);
        curl_close($curl);


        Session::put('request', $request->all());
        Session::put('success_url', $_success_url);
        Session::put('cancel_url', $_cancel_url);

        if ($this->sandbox == 1) {
            return redirect($payment['sandbox_init_point']);
        } else {
            return redirect($payment['init_point']);
        }
    }

    public function curlCalls($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $paymentData = curl_exec($ch);
        curl_close($ch);
        return $paymentData;
    }

    public function paycancle()
    {
        return redirect()->back()->with('error', 'Payment Cancelled.');
    }

    public function payreturn()
    {
        if (Session::has('tempcart')) {
            $oldCart = Session::get('tempcart');
            $tempcart = new Cart($oldCart);
            $order = Session::get('temporder');
        } else {
            $tempcart = '';
            return redirect()->back();
        }

        return view('front.success', compact('tempcart', 'order'));
    }

    public function successPayment(Request $request)
    {
        $requestData = Session::get('request');

        $bs = Basic::first();

        $success_url = Session::get('success_url');
        $cancel_url = Session::get('cancel_url');
        $paymentUrl = "https://api.mercadopago.com/v1/payments/" . $request['payment_id'] . "?access_token=" . $this->access_token;
        $paymentData = $this->curlCalls($paymentUrl);
        $payment = json_decode($paymentData, true);
        if ($payment['status'] == 'approved') {
            $paymentFor = Session::get('paymentFor');
            $package = Package::find($requestData['package_id']);
            $transaction_id = SellerPermissionHelper::uniqidReal(8);
            $transaction_details = json_encode($payment);
            if ($paymentFor == "membership") {
                $amount = $requestData['price'];
                $password = $requestData['password'];
                $checkout = new SellerCheckoutController();

                $seller = $checkout->store($requestData, $transaction_id, $transaction_details, $amount, $bs, $password);

                $lastMemb = $seller->memberships()->orderBy('id', 'DESC')->first();

                $activation = Carbon::parse($lastMemb->start_date);
                $expire = Carbon::parse($lastMemb->expire_date);
                $file_name = $this->makeInvoice($requestData, "membership", $seller, $password, $amount, "Paypal", $requestData['phone'], $bs->base_currency_symbol_position, $bs->base_currency_symbol, $bs->base_currency_text, $transaction_id, $package->title, $lastMemb, "seller-memberships");

                $mailer = new MegaMailer();
                $data = [
                    'toMail' => $seller->email,
                    'toName' => $seller->fname,
                    'username' => $seller->username,
                    'package_title' => $package->title,
                    'package_price' => ($bs->base_currency_text_position == 'left' ? $bs->base_currency_text . ' ' : '') . $package->price . ($bs->base_currency_text_position == 'right' ? ' ' . $bs->base_currency_text : ''),
                    'discount' => ($bs->base_currency_text_position == 'left' ? $bs->base_currency_text . ' ' : '') . $lastMemb->discount . ($bs->base_currency_text_position == 'right' ? ' ' . $bs->base_currency_text : ''),
                    'total' => ($bs->base_currency_text_position == 'left' ? $bs->base_currency_text . ' ' : '') . $lastMemb->price . ($bs->base_currency_text_position == 'right' ? ' ' . $bs->base_currency_text : ''),
                    'activation_date' => $activation->toFormattedDateString(),
                    'expire_date' => Carbon::parse($expire->toFormattedDateString())->format('Y') == '9999' ? 'Lifetime' : $expire->toFormattedDateString(),
                    'membership_invoice' => $file_name,

                    'membership_invoice_path' => 'seller-memberships',

                    'website_title' => $bs->website_title,
                    'templateType' => 'registration_with_premium_package',
                    'type' => 'registrationWithPremiumPackage'
                ];
                $mailer->mailFromAdmin($data);
                                // Notify all admins of new seller package purchase
                $admins = \App\Models\Admin::all();
                foreach ($admins as $admin) {
                    $notificationService = new \App\Services\NotificationService();
                    $notificationService->sendRealTime($admin, [
                        'type' => 'seller_package_purchase',
                        'title' => 'New Seller Package Purchase',
                        'message' => 'Seller ' . $seller->username . ' purchased the package: ' . $package->title,
                        'url' => route('admin.payment-log.index'),
                        'icon' => 'fas fa-box',
                        'extra' => [
                            'seller_id' => $seller->id,
                            'package_id' => $package->id,
                            'package_title' => $package->title,
                            'price' => $amount,
                            'payment_method' => 'MercadoPago'
                        ]
                    ]);
                }

                session()->flash('success', 'Your payment has been completed.');
                Session::forget('request');
                Session::forget('paymentFor');
                return redirect()->route('success.page');
            } elseif ($paymentFor == "extend") {
                $amount = $requestData['price'];
                $password = uniqid('qrcode');
                $checkout = new SellerCheckoutController();
                $seller = $checkout->store($requestData, $transaction_id, $transaction_details, $amount, $bs, $password);

                $lastMemb = Membership::where('seller_id', $seller->id)->orderBy('id', 'DESC')->first();
                $activation = Carbon::parse($lastMemb->start_date);
                $expire = Carbon::parse($lastMemb->expire_date);

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

                    'membership_invoice_path' => 'seller-memberships',

                    'website_title' => $bs->website_title,
                    'templateType' => 'seller_membership_extend',
                    'type' => 'membershipExtend'
                ];
                $mailer->mailFromAdmin($data);
                                //store data to transaction and earnings table
                $transaction_data = [];
                $transaction_data['order_id'] = $lastMemb->id;
                $transaction_data['transcation_type'] = 5;
                $transaction_data['user_id'] = null;
                $transaction_data['seller_id'] = $lastMemb->seller_id;
                $transaction_data['payment_status'] = 'completed';
                $transaction_data['payment_method'] = $lastMemb->payment_method ?: 'MercadoPago';
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

                // Notify all admins of seller package extension
                $admins = \App\Models\Admin::all();
                foreach ($admins as $admin) {
                    $notificationService = new \App\Services\NotificationService();
                    $notificationService->sendRealTime($admin, [
                        'type' => 'seller_package_extension',
                        'title' => 'Seller Package Extension',
                        'message' => 'Seller ' . $seller->username . ' extended the package: ' . $package->title,
                        'url' => route('admin.payment-log.index'),
                        'icon' => 'fas fa-sync-alt',
                        'extra' => [
                            'seller_id' => $seller->id,
                            'package_id' => $package->id,
                            'package_title' => $package->title,
                            'price' => $amount,
                            'payment_method' => 'MercadoPago'
                        ]
                    ]);
                }

                Session::forget('request');
                Session::forget('paymentFor');
                return redirect()->route('success.page');
            }
        }


        return redirect($cancel_url);
    }

    public function cancelPayment()
    {
        $requestData = Session::get('request');
        $paymentFor = Session::get('paymentFor');
        session()->flash('warning', __('cancel_payment'));
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
            $transaction_details = 'Payment completed via ' . $controllerName;
            
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
