<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontEnd\MiscellaneousController;
use App\Http\Controllers\Payment\AuthorizeController;
use App\Http\Controllers\Payment\FlutterWaveController;
use App\Http\Controllers\Payment\InstamojoController;
use App\Http\Controllers\Payment\MercadopagoController;
use App\Http\Controllers\Payment\MollieController;
use App\Http\Controllers\Payment\StripeController;
use App\Http\Controllers\Payment\PaypalController;
use App\Http\Controllers\Payment\PaystackController;
use App\Http\Controllers\Payment\PaytmController;
use App\Http\Controllers\Payment\PhonePeController;
use App\Http\Controllers\Payment\YocoController;
use App\Http\Controllers\Payment\RazorpayController;
use App\Http\Controllers\Payment\PerfectMoneyController;
use App\Http\Controllers\Payment\ToyyibpayController;
use App\Http\Controllers\Payment\PaytabsController;
use App\Http\Controllers\Payment\IyzicoController;
use App\Http\Controllers\Payment\MyFatoorahController;
use App\Http\Controllers\Payment\MidtransController;
use App\Http\Controllers\Payment\XenditController;
use App\Http\Helpers\BasicMailer;
use App\Http\Helpers\UserPermissionHelper;
use App\Models\BasicSettings\Basic;
use App\Models\Language;
use App\Models\PaymentGateway\OfflineGateway;
use App\Models\User;
use App\Models\UserMembership;
use App\Models\UserPackage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Barryvdh\DomPDF\Facade\Pdf;

class UserPackageController extends Controller
{
    public function index()
    {
        $misc = new MiscellaneousController();
        $breadcrumb = $misc->getBreadcrumb();

        $user = Auth::guard('web')->user();
        $packages = UserPackage::where('status', 1)->orderBy('price', 'ASC')->get();
        $bs = Basic::first();

        // Get current membership info
        $currentMembership = UserPermissionHelper::userPackage($user->id);
        $currentPackage = null;
        if ($currentMembership) {
            $currentPackage = UserPackage::find($currentMembership->package_id);
        }

        return view('frontend.user.packages.index', compact('breadcrumb', 'packages', 'user', 'currentPackage', 'currentMembership', 'bs'));
    }

    public function checkout($id)
    {
        $misc = new MiscellaneousController();
        $breadcrumb = $misc->getBreadcrumb();

        $user = Auth::guard('web')->user();
        $package = UserPackage::findOrFail($id);

        if (!$package->status) {
            Session::flash('error', 'This package is not available.');
            return redirect()->route('user.packages.index');
        }

        // Get current membership info
        $currentMembership = UserPermissionHelper::userPackage($user->id);
        $currentPackage = null;
        if ($currentMembership) {
            $currentPackage = UserPackage::find($currentMembership->package_id);
        }

        $bs = Basic::first();
        
        // Get all available payment gateways
        $offline_gateways = OfflineGateway::where('status', 1)->get();
        $online_gateways = \App\Models\PaymentGateway\OnlineGateway::where('status', 1)->get();

        return view('frontend.user.packages.checkout', compact('breadcrumb', 'package', 'user', 'currentPackage', 'currentMembership', 'bs', 'offline_gateways', 'online_gateways'));
    }

    public function processPayment(Request $request, $id)
    {
        try {
            $offline_payment_gateways = OfflineGateway::all()->pluck('name')->toArray();
            $currentLang = session()->has('lang') ?
                (Language::where('code', session()->get('lang'))->first())
                : (Language::where('is_default', 1)->first());
            $bs = Basic::first();
            $user = Auth::guard('web')->user();
            $package = UserPackage::findOrFail($id);

            if (!$package->status) {
                Session::flash('error', 'This package is not available.');
                return redirect()->route('user.packages.index');
            }

            // Check if user already has a pending membership
            $pendingMembership = UserMembership::where('user_id', $user->id)
                ->where('status', '0')
                ->first();

            if ($pendingMembership) {
                Session::flash('error', 'You already have a pending membership.');
                return redirect()->route('user.packages.index');
            }

            $request['status'] = "1";
            $request['receipt_name'] = null;
            $request['email'] = $user->email;
            $request['user_id'] = $user->id;
            $request['package_id'] = $package->id;
            $request['price'] = $package->price;
            $request['start_date'] = Carbon::now()->format('Y-m-d');
            $request['expire_date'] = Carbon::now()->format('Y-m-d'); // Will be set when approved

            Session::put('paymentFor', 'user_package');
            $title = "You are purchasing a user package";
            $description = "Congratulations! You are going to purchase a user package. Please make a payment to confirm your purchase!";

            if ($package->price == 0) {
                $request['price'] = 0.00;
                $request['payment_method'] = "-";
                $transaction_details = "Free";
                $password = uniqid('qrcode');
                $transaction_id = UserPermissionHelper::uniqidReal(8);
                $this->store($request->all(), $transaction_id, $transaction_details, $request['price'], $bs, $password);
                $subject = "You made your user package purchase successful";
                $body = "You made a payment. This is a confirmation mail from us. Please see the invoice attachment below";

                $lastMemb = $user->userMemberships()->orderBy('id', 'DESC')->first();

                $file_name = $this->makeInvoice($request->all(), "user_package", $user, $password, $request['price'], $request["payment_method"], $user->phone_number, $bs->base_currency_symbol_position, $bs->base_currency_symbol, $bs->base_currency_text, $transaction_id, $package->title, $lastMemb);
                $basicMail = new BasicMailer();
                $data = [
                    'invoice' => public_path('assets/front/invoices/' . $file_name),
                    'recipient' => $user->email,
                    'subject' => $subject,
                    'body' => $body
                ];
                $basicMail->sendMail($data);
                Session::forget('paymentFor');
                return redirect()->route('user.packages.success', ['type' => 'free']);
            } elseif ($request->payment_method == 'PayPal') {
                $amount = round(($request->price / $bs->base_currency_rate), 2);
                $paypal = new PaypalController;
                $cancel_url = route('user.packages.paypal.cancel');
                $success_url = route('user.packages.paypal.success');
                return $paypal->paymentProcess($request, $amount, $title, $success_url, $cancel_url);
            } elseif ($request->payment_method == 'Stripe') {
                $amount = round(($request->price / $bs->base_currency_rate), 2);
                $stripe = new StripeController();
                $cancel_url = route('user.packages.stripe.cancel');
                return $stripe->paymentProcess($request, $amount, $title, NULL, $cancel_url);
            } elseif ($request->payment_method == 'Paytm') {
                if ($bs->base_currency_text != 'INR') {
                    session()->flash('warning', 'Only INR is supported currency for Paytm');
                    return back()->withInput($request->all());
                }
                $amount = $request->price;
                $item_number = uniqid('paytm-') . time();
                $callback_url = route('user.packages.paytm.status');
                $paytm = new PaytmController();
                return $paytm->paymentProcess($request, $amount, $item_number, $callback_url);
            } elseif ($request->payment_method == 'Paystack') {
                if ($bs->base_currency_text != "NGN") {
                    session()->flash('warning', 'Only NGN is supported currency for Paystack');
                    return back()->withInput($request->all());
                }
                $amount = $request->price * 100;
                $email = $request->email;
                $success_url = route('user.packages.paystack.success');
                $payStack = new PaystackController();
                return $payStack->paymentProcess($request, $amount, $email, $success_url, $bs);
            } elseif ($request->payment_method == 'Razorpay') {
                if ($bs->base_currency_text != "INR") {
                    session()->flash('warning', $bs->base_currency_text . " is not allowed for Razorpay");
                    return back()->with($request->all());
                }
                $amount = $request->price;
                $item_number = uniqid('razorpay-') . time();
                $cancel_url = route('user.packages.razorpay.cancel');
                $success_url = route('user.packages.razorpay.success');
                $razorpay = new RazorpayController();
                return $razorpay->paymentProcess($request, $amount, $item_number, $cancel_url, $success_url, $title, $description, $bs);
            } elseif ($request->payment_method == 'Instamojo') {
                if ($bs->base_currency_text != "INR") {
                    session()->flash('warning', $bs->base_currency_text . " is not allowed for Instamojo");
                    return back()->withInput($request->all());
                }
                if ($request->price < 9) {
                    return redirect()->back()->with('error', 'Minimum 10 INR required for this payment gateway')->withInput($request->all());
                }
                $amount = $request->price;
                $success_url = route('user.packages.instamojo.success');
                $cancel_url = route('user.packages.instamojo.cancel');
                $instaMoJo = new InstamojoController();
                return $instaMoJo->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $bs);
            } elseif ($request->payment_method == 'MercadoPago') {
                if ($bs->base_currency_text != "BRL") {
                    session()->flash('warning', $bs->base_currency_text . " is not allowed for MercadoPago");
                    return back()->withInput($request->all());
                }
                $amount = $request->price;
                $email = $request->email;
                $success_url = route('user.packages.mercadopago.success');
                $cancel_url = route('user.packages.mercadopago.cancel');
                $mercadopagoPayment = new MercadopagoController();
                return $mercadopagoPayment->paymentProcess($request, $amount, $success_url, $cancel_url, $email, $title, $description, $bs);
            } elseif ($request->payment_method == 'Flutterwave') {
                $available_currency = array(
                    'BIF', 'CAD', 'CDF', 'CVE', 'EUR', 'GBP', 'GHS', 'GMD', 'GNF', 'KES', 'LRD', 'MWK', 'NGN', 'RWF', 'SLL', 'STD', 'TZS', 'UGX', 'USD', 'XAF', 'XOF', 'ZMK', 'ZMW', 'ZWD'
                );
                if (!in_array($bs->base_currency_text, $available_currency)) {
                    session()->flash('warning', $bs->base_currency_text . " is not allowed for Flutterwave.");
                    return back()->withInput($request->all());
                }
                $amount = $request->price;
                $email = $request->email;
                $item_number = uniqid('flutterwave-') . time();
                $cancel_url = route('user.packages.flutterwave.cancel');
                $success_url = route('user.packages.flutterwave.success');
                $flutterWave = new FlutterWaveController();
                return $flutterWave->paymentProcess($request, $amount, $email, $item_number, $success_url, $cancel_url, $bs);
            } elseif ($request->payment_method == 'Authorize.Net') {
                $available_currency = array('USD', 'CAD', 'CHF', 'DKK', 'EUR', 'GBP', 'NOK', 'PLN', 'SEK', 'AUD', 'NZD');
                if (!in_array($bs->base_currency_text, $available_currency)) {
                    session()->flash('warning', $bs->base_currency_text . " is not allowed for Authorize.Net");
                    return back()->withInput($request->all());
                }
                $amount = $request->price;
                $success_url = route('user.packages.authorize.success');
                $cancel_url = route('user.packages.authorize.cancel');
                $authorizePayment = new AuthorizeController();
                return $authorizePayment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $bs);
            } elseif ($request->payment_method == 'Mollie') {
                $available_currency = array('AED', 'AUD', 'BGN', 'BRL', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR', 'GBP', 'HKD', 'HRK', 'HUF', 'ILS', 'ISK', 'JPY', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN', 'RON', 'RUB', 'SEK', 'SGD', 'THB', 'TWD', 'USD', 'ZAR');
                if (!in_array($bs->base_currency_text, $available_currency)) {
                    session()->flash('warning', $bs->base_currency_text . " is not allowed for Mollie");
                    return back()->withInput($request->all());
                }
                $amount = $request->price;
                $success_url = route('user.packages.mollie.success');
                $cancel_url = route('user.packages.mollie.cancel');
                $molliePayment = new MollieController();
                return $molliePayment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $bs);
            } elseif ($request->payment_method == 'Phonepe') {
                $available_currency = array('INR');
                if (!in_array($bs->base_currency_text, $available_currency)) {
                    session()->flash('warning', $bs->base_currency_text . " is not allowed for PhonePe");
                    return back()->withInput($request->all());
                }
                $amount = $request->price;
                $success_url = route('user.packages.phonepe.success');
                $cancel_url = route('user.packages.phonepe.cancel');
                $payment = new PhonePeController();
                return $payment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $bs);
            } elseif ($request->payment_method == 'Yoco') {
                $available_currency = array('ZAR');
                if (!in_array($bs->base_currency_text, $available_currency)) {
                    session()->flash('warning', $bs->base_currency_text . " is not allowed for Yoco");
                    return back()->withInput($request->all());
                }
                $amount = $request->price;
                $success_url = route('user.packages.yoco.success');
                $cancel_url = route('user.packages.yoco.cancel');
                $payment = new YocoController();
                return $payment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $bs);
            } elseif ($request->payment_method == 'Perfect Money') {
                $available_currency = array('USD');
                if (!in_array($bs->base_currency_text, $available_currency)) {
                    session()->flash('warning', $bs->base_currency_text . " is not allowed for Perfect Money");
                    return back()->withInput($request->all());
                }
                $amount = $request->price;
                $success_url = route('user.packages.perfect_money.success');
                $cancel_url = route('user.packages.perfect_money.cancel');
                $payment = new PerfectMoneyController();
                return $payment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $bs);
            } elseif ($request->payment_method == 'Toyyibpay') {
                $available_currency = array('RM');
                if (!in_array($bs->base_currency_text, $available_currency)) {
                    session()->flash('warning', $bs->base_currency_text . " is not allowed for Toyyibpay");
                    return back()->withInput($request->all());
                }
                $amount = $request->price;
                $success_url = route('user.packages.toyyibpay.success');
                $cancel_url = route('user.packages.toyyibpay.cancel');
                $payment = new ToyyibpayController();
                return $payment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $bs);
            } elseif ($request->payment_method == 'Paytabs') {
                $paytabInfo = paytabInfo();
                if ($bs->base_currency_text != $paytabInfo['currency']) {
                    session()->flash('warning', $bs->base_currency_text . " is not allowed for Paytabs");
                    return back()->withInput($request->all());
                }
                $amount = $request->price;
                $success_url = route('user.packages.paytabs.success');
                $cancel_url = route('user.packages.paytabs.cancel');
                $payment = new PaytabsController();
                return $payment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $bs);
            } elseif ($request->payment_method == 'Iyzico') {
                $available_currency = array('TRY');
                if (!in_array($bs->base_currency_text, $available_currency)) {
                    session()->flash('warning', $bs->base_currency_text . " is not allowed for Iyzico");
                    return back()->withInput($request->all());
                }
                $amount = $request->price;
                $success_url = route('user.packages.iyzico.success');
                $cancel_url = route('user.packages.iyzico.cancel');
                $payment = new IyzicoController();
                return $payment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $bs);
            } elseif ($request->payment_method == 'Myfatoorah') {
                $available_currency = array('KWD', 'SAR', 'BHD', 'AED', 'QAR', 'OMR', 'JOD');
                if (!in_array($bs->base_currency_text, $available_currency)) {
                    session()->flash('warning', $bs->base_currency_text . " is not allowed for Myfatoorah");
                    return back()->withInput($request->all());
                }
                $amount = $request->price;
                $success_url = route('user.packages.myfatoorah.success');
                $cancel_url = route('user.packages.myfatoorah.cancel');
                $payment = new MyFatoorahController();
                return $payment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $bs);
            } elseif ($request->payment_method == 'Midtrans') {
                $available_currency = array('IDR');
                if (!in_array($bs->base_currency_text, $available_currency)) {
                    session()->flash('warning', $bs->base_currency_text . " is not allowed for Midtrans");
                    return back()->withInput($request->all());
                }
                $amount = $request->price;
                $success_url = null;
                $cancel_url = route('user.packages.midtrans.cancel');
                $payment = new MidtransController();
                return $payment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $bs);
            } elseif ($request->payment_method == 'Xendit') {
                $available_currency = array('IDR', 'PHP', 'USD', 'SGD', 'MYR');
                if (!in_array($bs->base_currency_text, $available_currency)) {
                    session()->flash('warning', $bs->base_currency_text . " is not allowed for Xendit");
                    return back()->withInput($request->all());
                }
                $amount = $request->price;
                $success_url = route('user.packages.xendit.success');
                $cancel_url = route('user.packages.xendit.cancel');
                $payment = new XenditController();
                return $payment->paymentProcess($request, $amount, $success_url, $cancel_url, $title, $bs);
            }
            // Debug: Log offline gateways and selected payment method
            \Log::info('Offline gateways:', $offline_payment_gateways);
            \Log::info('Selected payment method:', [$request->payment_method]);
            // Robust offline gateway match (case-insensitive, trimmed)
            $selectedMethod = trim(strtolower($request->payment_method));
            $offlineGatewayNames = array_map(function($name) { return trim(strtolower($name)); }, $offline_payment_gateways);
            if (in_array($selectedMethod, $offlineGatewayNames)) {
                \Log::info('Offline payment block triggered for: ' . $request->payment_method);
                try {
                    $request['status'] = "0";
                    if ($request->hasFile('receipt')) {
                        $filename = time() . '.' . $request->file('receipt')->getClientOriginalExtension();
                        $directory = public_path('assets/front/img/user-packages/receipt');
                        @mkdir($directory, 0777, true);
                        $request->file('receipt')->move($directory, $filename);
                        $request['receipt_name'] = $filename;
                    }
                    $amount = $request->price;
                    $transaction_id = UserPermissionHelper::uniqidReal(8);
                    $transaction_details = "offline";
                    $password = uniqid('qrcode');
                    $user = $this->store($request->all(), $transaction_id, json_encode($transaction_details), $amount, $bs, $password);
                    
                    // Get package details for notification
                    $package = UserPackage::find($request->package_id);
                    
                    // Notify all admins of new user offline payment submission
                    $admins = \App\Models\Admin::all();
                    foreach ($admins as $admin) {
                        $notificationService = new \App\Services\NotificationService();
                        $notificationService->sendRealTime($admin, [
                            'type' => 'user_package_purchase',
                            'title' => 'New Customer Package Purchase',
                            'message' => 'Customer ' . $user->username . ' purchased the package: ' . $package->name,
                            'url' => route('admin.user_membership.index'),
                            'icon' => 'fas fa-box',
                            'extra' => [
                                'user_id' => $user->id,
                                'package_id' => $package->id,
                                'package_name' => $package->name,
                                'price' => $amount,
                                'payment_method' => $request->payment_method,
                                'transaction_id' => $transaction_id,
                                'receipt_name' => $request['receipt_name'] ?? null
                            ]
                        ]);
                    }
                    
                    Session::flash('success', 'package bought successfully and sent to admin to validate the payment');
                    return redirect()->route('pricing');
                } catch (\Exception $e) {
                    dd($e->getMessage(), $e->getTraceAsString());
                    \Log::error('Exception in offline payment block: ' . $e->getMessage());
                    Session::flash('error', 'Something went wrong in offline payment.');
                    return back();
                }
            }
            \Log::info('Catch-all error triggered in processPayment');
            // Catch-all for unmatched payment methods
            \Log::error('UserPackageController@processPayment: Unhandled payment method or unknown error', [
                'user_id' => $user->id ?? null,
                'package_id' => $id,
                'request' => $request->all(),
            ]);
            Session::flash('error', 'Something went wrong. Please try again or contact support.');
            return redirect()->back();
        } catch (\Exception $e) {
            Session::flash('warning', 'Something went wrong');
            return back();
        }
    }

    public function store($request, $transaction_id, $transaction_details, $amount, $bs, $password)
    {
        $abs = Basic::first();
        Config::set('app.timezone', $abs->timezone);

        $user = User::query()->find($request['user_id']);
        $currentDate = \App\Http\Helpers\UserPermissionHelper::getCurrentDate();
        $previousMembership = UserMembership::query()
            ->select('id', 'package_id', 'is_trial')
            ->where([
                ['user_id', $user->id],
                ['start_date', '<=', $currentDate],
                ['expire_date', '>=', $currentDate]
            ])
            ->where('status', '1')
            ->orderBy('created_at', 'DESC')
            ->first();

        if (!is_null($previousMembership)) {
            $previousPackage = UserPackage::query()
                ->select('term')
                ->where('id', $previousMembership->package_id)
                ->first();

            if (($previousPackage->term === 'lifetime' || $previousMembership->is_trial == 1) && $transaction_details != '"offline"') {
                $membership = UserMembership::find($previousMembership->id);
                $membership->expire_date = Carbon::parse($request['start_date']);
                $membership->save();
            }
        }

        if ($user) {
            $membership = UserMembership::create([
                'price' => $request['price'],
                'package_price' => $request['price'], // Fix: set package_price
                'currency' => $abs->base_currency_text,
                'currency_symbol' => $abs->base_currency_symbol,
                'payment_method' => $request["payment_method"],
                'transaction_id' => $transaction_id,
                'status' => $transaction_details != '"offline"' ? $request["status"] : '0',
                'receipt' => $request["receipt_name"],
                'transaction_details' => $transaction_details,
                'settings' => json_encode($abs),
                'package_id' => $request['package_id'],
                'user_id' => $user->id,
                'start_date' => Carbon::parse($request['start_date']),
                'expire_date' => Carbon::parse($request['expire_date']),
                'is_trial' => 0,
                'trial_days' => 0,
                'conversation_id' => $request['conversation_id'] ?? null,
            ]);
        }
        return $user;
    }

    public function makeInvoice($request, $key, $member, $password, $amount, $payment_method, $phone, $base_currency_symbol_position, $base_currency_symbol, $base_currency_text, $order_id, $package_title, $membership, $folder = 'user-memberships')
    {
        $fileName = $key . '_' . $member->id . '_' . $order_id . '.pdf';
        $directory = public_path('assets/file/invoices/' . $folder . '/');
        @mkdir($directory, 0777, true);
        $fileLocated = $directory . $fileName;
        
        // Get website info
        $bs = Basic::first();
        
        // Get package info
        $package = UserPackage::find($membership->package_id);
        
        $data = [
            'user' => $member,
            'membership' => $membership,
            'package' => $package,
            'bs' => $bs,
        ];
        
        $pdf = Pdf::loadView('frontend.user.packages.invoice', $data);
        $pdf->save($fileLocated);
        return $fileName;
    }

    public function onlineSuccess()
    {
        return view('frontend.user.packages.success');
    }

    public function offlineSuccess()
    {
        return view('frontend.user.packages.offline-success');
    }

    public function paymentInstruction(Request $request)
    {
        $offline = OfflineGateway::where('name', $request->name)
            ->select('short_description', 'instructions', 'has_attachment')
            ->first();
        return response()->json([
            'description' => $offline->short_description,
            'instructions' => $offline->instructions, 
            'has_attachment' => $offline->has_attachment
        ]);
    }

    public function processOfflinePayment(Request $request, $id)
    {
        $user = Auth::guard('web')->user();
        $package = UserPackage::findOrFail($id);
        $bs = Basic::first();

        if (!$package->status) {
            Session::flash('error', 'This package is not available.');
            return redirect()->route('user.packages.index');
        }

        // Check if user already has a pending membership
        $pendingMembership = UserMembership::where('user_id', $user->id)
            ->where('status', '0')
            ->first();

        if ($pendingMembership) {
            Session::flash('error', 'You already have a pending membership.');
            return redirect()->route('user.packages.index');
        }

        // Create membership record
        $membership = new UserMembership();
        $membership->user_id = $user->id;
        $membership->package_id = $package->id;
        $membership->price = $package->price;
        $membership->currency = $bs->base_currency_text;
        $membership->currency_symbol = $bs->base_currency_symbol;
        $membership->payment_method = 'offline';
        $membership->transaction_id = UserPermissionHelper::uniqidReal();
        $membership->status = '0'; // Pending
        $membership->start_date = Carbon::now()->format('Y-m-d');
        $membership->expire_date = Carbon::now()->format('Y-m-d'); // Will be set when approved
        $membership->save();

        Session::flash('success', 'Your package purchase request has been submitted and is pending admin approval.');
        return redirect()->route('user.packages.index');
    }

    public function subscriptionLog()
    {
        $misc = new MiscellaneousController();
        $breadcrumb = $misc->getBreadcrumb();

        $user = Auth::guard('web')->user();
        $memberships = $user->userMemberships()->with('package')->orderBy('created_at', 'DESC')->get();

        return view('frontend.user.packages.subscription-log', compact('breadcrumb', 'memberships', 'user'));
    }

    public function extend($id)
    {
        $user = Auth::guard('web')->user();
        $package = UserPackage::findOrFail($id);
        $bs = Basic::first();

        // Check if user has an active membership for this package
        $activeMembership = UserMembership::where('user_id', $user->id)
            ->where('package_id', $package->id)
            ->where('status', '1')
            ->first();
        if (!$activeMembership) {
            Session::flash('error', 'You do not have an active membership for this package.');
            return redirect()->route('user.packages.index');
        }

        // Check if user already has a pending extension
        $pendingExtension = UserMembership::where('user_id', $user->id)
            ->where('package_id', $package->id)
            ->where('status', '0')
            ->first();
        if ($pendingExtension) {
            Session::flash('error', 'You already have a pending extension request for this package.');
            return redirect()->route('user.packages.subscription_log');
        }

        // Create new pending membership (extension)
        $membership = new UserMembership();
        $membership->user_id = $user->id;
        $membership->package_id = $package->id;
        $membership->price = $package->price;
        $membership->currency = $bs->base_currency_text;
        $membership->currency_symbol = $bs->base_currency_symbol;
        $membership->payment_method = 'offline';
        $membership->transaction_id = UserPermissionHelper::uniqidReal();
        $membership->status = '0'; // Pending
        $membership->start_date = Carbon::now()->format('Y-m-d');
        $membership->expire_date = Carbon::now()->format('Y-m-d'); // Will be set when approved
        $membership->save();

        Session::flash('success', 'Your package extension request has been submitted and is pending admin approval.');
        return redirect()->route('user.packages.subscription_log');
    }
} 