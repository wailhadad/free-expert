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

class PaytmController extends Controller
{
    public function paymentProcess(Request $request, $_amount, $_item_number, $_callback_url)
    {
        $data = OnlineGateway::whereKeyword('paytm')->first();
        $paydata = $data->convertAutoData();
        $data_for_request = $this->handlePaytmRequest($_item_number, $_amount, $_callback_url);
        if ($paydata['environment'] == 'local') {
            $paytm_txn_url = 'https://securegw-stage.paytm.in/theia/processTransaction';
        } else {
            $paytm_txn_url = 'https://securegw.paytm.in/theia/processTransaction';
        }
        $paramList = $data_for_request['paramList'];
        $checkSum = $data_for_request['checkSum'];
        Session::put("request", $request->all());
        return view('frontend.payment.paytm', compact('paytm_txn_url', 'paramList', 'checkSum'));
    }

    public function handlePaytmRequest($_item_number, $amount, $callback_url)
    {
        $data = OnlineGateway::whereKeyword('paytm')->first();
        $paydata = $data->convertAutoData();

        // Load all functions of encdec_paytm.php and config-paytm.php
        $this->getAllEncdecFunc();
        $checkSum = "";
        $paramList = array();
        // Create an array having all required parameters for creating checksum.
        $paramList["MID"] = $paydata['merchant_mid'];
        $paramList["ORDER_ID"] = $_item_number;
        $paramList["CUST_ID"] = $_item_number;
        $paramList["INDUSTRY_TYPE_ID"] = $paydata['industry_type'];
        $paramList["CHANNEL_ID"] = 'WEB';
        $paramList["TXN_AMOUNT"] = $amount;
        $paramList["WEBSITE"] = $paydata['merchant_website'];
        $paramList["CALLBACK_URL"] = $callback_url;

        $paytm_merchant_key = $paydata['merchant_key'];
        //Here checksum string will return by getChecksumFromArray() function.
        $checkSum = getChecksumFromArray($paramList, $paytm_merchant_key);
        return array(
            'checkSum' => $checkSum,
            'paramList' => $paramList
        );
    }

    function getAllEncdecFunc()
    {
        function encrypt_e($input, $ky)
        {
            $key = html_entity_decode($ky);
            $iv = "@@@@&&&&####$$$$";
            $data = openssl_encrypt($input, "AES-128-CBC", $key, 0, $iv);
            return $data;
        }

        function decrypt_e($crypt, $ky)
        {
            $key = html_entity_decode($ky);
            $iv = "@@@@&&&&####$$$$";
            $data = openssl_decrypt($crypt, "AES-128-CBC", $key, 0, $iv);
            return $data;
        }

        function pkcs5_pad_e($text, $blocksize)
        {
            $pad = $blocksize - (strlen($text) % $blocksize);
            return $text . str_repeat(chr($pad), $pad);
        }

        function pkcs5_unpad_e($text)
        {
            $pad = ord($text[strlen($text) - 1]);
            if ($pad > strlen($text))
                return false;
            return substr($text, 0, -1 * $pad);
        }

        function generateSalt_e($length)
        {
            $random = "";
            srand((float)microtime() * 1000000);
            $data = "AbcDE123IJKLMN67QRSTUVWXYZ";
            $data .= "aBCdefghijklmn123opq45rs67tuv89wxyz";
            $data .= "0FGH45OP89";
            for ($i = 0; $i < $length; $i++) {
                $random .= substr($data, (rand() % (strlen($data))), 1);
            }
            return $random;
        }

        function checkString_e($value)
        {
            if ($value == 'null')
                $value = '';
            return $value;
        }

        function getChecksumFromArray($arrayList, $key, $sort = 1)
        {
            if ($sort != 0) {
                ksort($arrayList);
            }
            $str = getArray2Str($arrayList);
            $salt = generateSalt_e(4);
            $finalString = $str . "|" . $salt;
            $hash = hash("sha256", $finalString);
            $hashString = $hash . $salt;
            $checksum = encrypt_e($hashString, $key);
            return $checksum;
        }

        function getChecksumFromString($str, $key)
        {
            $salt = generateSalt_e(4);
            $finalString = $str . "|" . $salt;
            $hash = hash("sha256", $finalString);
            $hashString = $hash . $salt;
            $checksum = encrypt_e($hashString, $key);
            return $checksum;
        }

        function verifychecksum_e($arrayList, $key, $checksumvalue)
        {
            $arrayList = removeCheckSumParam($arrayList);
            ksort($arrayList);
            $str = getArray2StrForVerify($arrayList);
            $paytm_hash = decrypt_e($checksumvalue, $key);
            $salt = substr($paytm_hash, -4);
            $finalString = $str . "|" . $salt;
            $website_hash = hash("sha256", $finalString);
            $website_hash .= $salt;
            $validFlag = "FALSE";
            if ($website_hash == $paytm_hash) {
                $validFlag = "TRUE";
            } else {
                $validFlag = "FALSE";
            }
            return $validFlag;
        }

        function verifychecksum_eFromStr($str, $key, $checksumvalue)
        {
            $paytm_hash = decrypt_e($checksumvalue, $key);
            $salt = substr($paytm_hash, -4);
            $finalString = $str . "|" . $salt;
            $website_hash = hash("sha256", $finalString);
            $website_hash .= $salt;
            $validFlag = "FALSE";
            if ($website_hash == $paytm_hash) {
                $validFlag = "TRUE";
            } else {
                $validFlag = "FALSE";
            }
            return $validFlag;
        }

        function getArray2Str($arrayList)
        {
            $findme = 'REFUND';
            $findmepipe = '|';
            $paramStr = "";
            $flag = 1;
            foreach ($arrayList as $key => $value) {
                $pos = strpos($value, $findme);
                $pospipe = strpos($value, $findmepipe);
                if ($pos !== false || $pospipe !== false) {
                    continue;
                }
                if ($flag) {
                    $paramStr .= checkString_e($value);
                    $flag = 0;
                } else {
                    $paramStr .= "|" . checkString_e($value);
                }
            }
            return $paramStr;
        }

        function getArray2StrForVerify($arrayList)
        {
            $paramStr = "";
            $flag = 1;
            foreach ($arrayList as $key => $value) {
                if ($flag) {
                    $paramStr .= checkString_e($value);
                    $flag = 0;
                } else {
                    $paramStr .= "|" . checkString_e($value);
                }
            }
            return $paramStr;
        }

        function redirect2PG($paramList, $key)
        {
            $hashString = getchecksumFromArray($paramList, $key);
            $checksum = encrypt_e($hashString, $key);
        }

        function removeCheckSumParam($arrayList)
        {
            if (isset($arrayList["CHECKSUMHASH"])) {
                unset($arrayList["CHECKSUMHASH"]);
            }
            return $arrayList;
        }

        function getTxnStatus($requestParamList)
        {
            return callAPI(PAYTM_STATUS_QUERY_URL, $requestParamList);
        }

        function getTxnStatusNew($requestParamList)
        {
            return callNewAPI(PAYTM_STATUS_QUERY_NEW_URL, $requestParamList);
        }

        function initiateTxnRefund($requestParamList)
        {
            $CHECKSUM = getRefundChecksumFromArray($requestParamList, PAYTM_MERCHANT_KEY, 0);
            $requestParamList["CHECKSUM"] = $CHECKSUM;
            return callAPI(PAYTM_REFUND_URL, $requestParamList);
        }

        function callAPI($apiURL, $requestParamList)
        {
            $jsonResponse = "";
            $responseParamList = array();
            $JsonData = json_encode($requestParamList);
            $postData = 'JsonData=' . urlencode($JsonData);
            $ch = curl_init($apiURL);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($postData)
                )
            );
            $jsonResponse = curl_exec($ch);
            $responseParamList = json_decode($jsonResponse, true);
            return $responseParamList;
        }

        function callNewAPI($apiURL, $requestParamList)
        {
            $jsonResponse = "";
            $responseParamList = array();
            $JsonData = json_encode($requestParamList);
            $postData = 'JsonData=' . urlencode($JsonData);
            $ch = curl_init($apiURL);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($postData)
                )
            );
            $jsonResponse = curl_exec($ch);
            $responseParamList = json_decode($jsonResponse, true);
            return $responseParamList;
        }

        function getRefundChecksumFromArray($arrayList, $key, $sort = 1)
        {
            if ($sort != 0) {
                ksort($arrayList);
            }
            $str = getRefundArray2Str($arrayList);
            $salt = generateSalt_e(4);
            $finalString = $str . "|" . $salt;
            $hash = hash("sha256", $finalString);
            $hashString = $hash . $salt;
            $checksum = encrypt_e($hashString, $key);
            return $checksum;
        }

        function getRefundArray2Str($arrayList)
        {
            $findmepipe = '|';
            $paramStr = "";
            $flag = 1;
            foreach ($arrayList as $key => $value) {
                $pospipe = strpos($value, $findmepipe);
                if ($pospipe !== false) {
                    continue;
                }
                if ($flag) {
                    $paramStr .= checkString_e($value);
                    $flag = 0;
                } else {
                    $paramStr .= "|" . checkString_e($value);
                }
            }
            return $paramStr;
        }

        function callRefundAPI($refundApiURL, $requestParamList)
        {
            $jsonResponse = "";
            $responseParamList = array();
            $JsonData = json_encode($requestParamList);
            $postData = 'JsonData=' . urlencode($JsonData);
            $ch = curl_init($refundApiURL);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_URL, $refundApiURL);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $headers = array();
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $jsonResponse = curl_exec($ch);
            return json_decode($jsonResponse, true);
        }
    }

    public function paymentStatus(Request $request)
    {
        $requestData = Session::get('request');
        $bs = Basic::first();
        $paymentFor = Session::get('paymentFor');
        if ($request["STATUS"] === "TXN_FAILURE") {
            $paymentFor = Session::get('paymentFor');
            session()->flash('warning', $request['RESPMSG']);

            return redirect()->route('seller.plan.extend.checkout', ['package_id' => $requestData['package_id']])->withInput($requestData);
        } elseif ($request['STATUS'] === 'TXN_SUCCESS') {
            $package = Package::find($requestData['package_id']);
            $transaction_id = SellerPermissionHelper::uniqidReal(8);
            $transaction_details = json_encode($request);

            if (
                $paymentFor == "membership"
            ) {
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
                            'payment_method' => 'Paytm'
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
                $transaction_data['payment_method'] = $lastMemb->payment_method ?: 'Paytm';
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
                            'payment_method' => 'Paytm'
                        ]
                    ]);
                }

                Session::forget('request');
                Session::forget('paymentFor');
                return redirect()->route('success.page');
            }
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
