<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Seller\SellerCheckoutController;
use App\Models\PaymentGateway\OnlineGateway;
use App\Http\Helpers\SellerPermissionHelper;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\Controller;
use App\Models\BasicSettings\Basic;
use App\Http\Helpers\MegaMailer;
use Illuminate\Http\Request;
use App\Models\Membership;
use App\Models\Package;
use Carbon\Carbon;
use Ixudra\Curl\Facades\Curl;
use Illuminate\Support\Facades\Auth;

class PhonePeController extends Controller
{
    public function paymentProcess(Request $request, $_amount, $_success_url, $_cancel_url, $_title, $bex)
    {
        $info = OnlineGateway::where('keyword', 'phonepe')->first();
        $information = json_decode($info->information, true);
        $randomNo = substr(uniqid(), 0, 3);

        $cancel_url = $_cancel_url;
        $notify_url = $_success_url;

        $data = array(
            'merchantId' => $information['merchant_id'],
            'merchantTransactionId' => uniqid(),
            'merchantUserId' => 'MUID' . $randomNo,
            'amount' => $_amount * 100,
            'redirectUrl' => $notify_url,
            'redirectMode' => 'POST',
            'callbackUrl' => $notify_url,
            'mobileNumber' => Auth::guard('seller')->user()->phone ? Auth::guard('seller')->user()->phone : '9999999999',
            'paymentInstrument' =>
            array(
                'type' => 'PAY_PAGE',
            ),
        );

        $encode = base64_encode(json_encode($data));

        $saltKey = $information['salt_key']; // sandbox salt key
        $saltIndex = $information['salt_index'];

        $string = $encode . '/pg/v1/pay' . $saltKey;
        $sha256 = hash('sha256', $string);

        $finalXHeader = $sha256 . '###' . $saltIndex;

        if ($information['sandbox_status'] == 1) {
            $url = "https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay"; // sandbox payment URL
        } else {
            $url = "https://api.phonepe.com/apis/hermes/pg/v1/pay"; // prod payment URL
        }

        $response = Curl::to($url)
            ->withHeader('Content-Type:application/json')
            ->withHeader('X-VERIFY:' . $finalXHeader)
            ->withData(json_encode(['request' => $encode]))
            ->post();

        $rData = json_decode($response);
        if ($rData->success == true) {
            if (!empty($rData->data->instrumentResponse->redirectInfo->url)) {
                // put some data in session before redirect
                Session::put("request", $request->all());
                Session::put('cancel_url', $cancel_url);
                return redirect()->to($rData->data->instrumentResponse->redirectInfo->url);
            } else {
                return redirect($cancel_url);
            }
        } else {
            return redirect($cancel_url);
        }
    }

    public function successPayment(Request $request)
    {
        $requestData = Session::get('request');
        $bs = Basic::first();
        $cancel_url = Session::get('cancel_url');
        /** Get the payment ID before session clear **/
        $info = OnlineGateway::where('keyword', 'phonepe')->first();
        $information = json_decode($info->information, true);
        if ($request->code == 'PAYMENT_SUCCESS' && $information['merchant_id'] == $request->merchantId) {
            $paymentFor = Session::get('paymentFor');
            $package = Package::find($requestData['package_id']);
            $transaction_id = SellerPermissionHelper::uniqidReal(8);
            $transaction_details = json_encode($request['payment_request_id']);
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
                            // Create transaction record for user package purchase
            storeUserPackageTransaction($lastMemb, $requestData['payment_method'], $bs);

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
                    'templateType' => 'membership_extend',
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
                $transaction_data['payment_method'] = $lastMemb->payment_method ?: 'PhonePe';
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
