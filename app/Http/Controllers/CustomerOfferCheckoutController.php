<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FrontEnd\ClientService\OrderProcessController;
use App\Models\CustomerOffer;
use App\Models\ClientService\Form;
use App\Models\ClientService\Service;
use App\Models\ClientService\ServiceOrder;
use App\Models\PaymentGateway\OfflineGateway;
use App\Models\PaymentGateway\OnlineGateway;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CustomerOfferCheckoutController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    public function checkout(Request $request, $offerId)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('user.login');
        }

        $offer = CustomerOffer::with(['chat', 'seller', 'form.input'])->findOrFail($offerId);
        $user = Auth::guard('web')->user();

        // Verify user owns this offer
        if ($offer->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        // Only allow checkout if offer status is 'checkout_pending'
        if ($offer->status !== 'checkout_pending') {
            return redirect()->back()->with('error', 'Offer must be accepted and pending checkout');
        }

        // Check if order already exists
        if ($offer->accepted_order_id) {
            return redirect()->route('customer.offer.complete', $offer->id);
        }

        // Get payment gateways
        $onlineGateways = OnlineGateway::where('status', 1)->get();
        $offlineGateways = OfflineGateway::where('status', 1)->get();

        // Get form fields if form is attached
        $formFields = [];
        if ($offer->form) {
            $formFields = $offer->form->input()->orderBy('order_no', 'asc')->get();
        }

        // Get breadcrumb image (same as service checkout)
        $breadcrumb = app(\App\Http\Controllers\FrontEnd\MiscellaneousController::class)::getBreadcrumb();

        return view('frontend.customer-offer.checkout', compact(
            'offer',
            'formFields',
            'onlineGateways',
            'offlineGateways',
            'breadcrumb'
        ));
    }

    public function processCheckout(Request $request, $offerId)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('user.login');
        }

        $offer = CustomerOffer::with(['chat', 'seller', 'form'])->findOrFail($offerId);
        $user = Auth::guard('web')->user();

        // Verify user owns this offer
        if ($offer->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        // Only allow checkout if offer status is 'checkout_pending'
        if ($offer->status !== 'checkout_pending') {
            return redirect()->back()->with('error', 'Offer must be accepted and pending checkout');
        }

        // Check if order already exists
        if ($offer->accepted_order_id) {
            return redirect()->route('customer.offer.complete', $offer->id);
        }

        // Validate form data if form is attached
        $validationRules = [
            'payment_method' => 'required|string',
            'subuser_id' => 'nullable|integer|exists:subusers,id',
        ];

        if ($offer->form) {
            $formFields = $offer->form->input()->orderBy('order_no', 'asc')->get();
            foreach ($formFields as $field) {
                if ($field->is_required) {
                    if ($field->type == 8) { // File
                        $validationRules['form_builder_' . $field->name] = 'required|file|max:' . ($field->file_size * 1024);
                    } else {
                        $validationRules[$field->name] = 'required';
                    }
                }
            }
        }

        $validator = Validator::make($request->all(), $validationRules);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Create a dummy service for the order
        $dummyService = new Service();
        $dummyService->id = 0; // Dummy ID
        $dummyService->seller_id = $offer->seller_id;

        // Prepare order data
        $orderData = [
            'userId' => $user->id,
            'subuser_id' => $request->input('subuser_id'),
            'seller_id' => $offer->seller_id,
            'orderNumber' => uniqid(),
            'name' => $offer->subuser ? $offer->subuser->full_name : ($user->first_name . ' ' . $user->last_name),
            'emailAddress' => $user->email_address,
            'serviceId' => 0, // Dummy service ID
            'packageId' => null,
            'packagePrice' => $offer->price,
            'addons' => null,
            'addonPrice' => 0,
            'tax_percentage' => 0,
            'tax' => 0,
            'grandTotal' => $offer->price,
            'currencyText' => 'USD',
            'currencyTextPosition' => 'left',
            'currencySymbol' => $offer->currency_symbol,
            'currencySymbolPosition' => 'left',
            'paymentMethod' => $request->payment_method,
            'gatewayType' => $this->getGatewayType($request->payment_method),
            'paymentStatus' => 'pending',
            'orderStatus' => 'pending',
            'infos' => $this->prepareFormData($request, $offer->form),
            'conversation_id' => 'customer_offer_' . $offer->id,
        ];

        // Create order
        $orderProcess = new OrderProcessController();
        $order = $orderProcess->storeData($orderData);

        // After order is created, set offer status to 'accepted' and calculate dead_line
        $deadLine = null;
        if ($offer->delivery_time) {
            $deadLine = now()->addDays($offer->delivery_time);
        }
        $offer->update(['accepted_order_id' => $order->id, 'status' => 'accepted', 'dead_line' => $deadLine]);
        // Broadcast real-time event for offer acceptance
        event(new \App\Events\CustomerOfferEvent($offer->load(['form.input', 'seller', 'user', 'subuser']), $offer->chat_id, 'accepted'));

        // Send notifications
        // Notify seller
        $notificationData = [
            'type' => 'customer_offer_order_created',
            'title' => 'Customer Offer Order Created',
            'message' => "Order #{$order->order_number} has been created from your customer offer '{$offer->title}'",
            'url' => route('seller.service_order.details', $order->id),
            'icon' => 'fas fa-shopping-cart',
            'extra' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'offer_id' => $offer->id,
                'offer_title' => $offer->title,
                'amount' => $order->grand_total,
            ],
        ];
        $this->notificationService->sendRealTime($offer->seller, $notificationData);

        // Notify user
        $userNotification = [
            'type' => 'customer_offer_order_placed',
            'title' => 'Customer Offer Order Placed',
            'message' => "Your customer offer order #{$order->order_number} has been placed successfully.",
            'url' => route('customer.offer.order.details', $order->id),
            'icon' => 'fas fa-gift',
            'extra' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'offer_id' => $offer->id,
                'offer_title' => $offer->title,
                'amount' => $order->grand_total,
            ],
        ];
        $this->notificationService->sendRealTime($user, $userNotification);

        // Redirect based on payment method
        if ($this->isOnlineGateway($request->payment_method)) {
            // Redirect to payment gateway
            return $this->redirectToPaymentGateway($request, $orderData, $order);
        } else {
            // Offline payment - mark as pending and redirect to completion
            return redirect()->route('customer.offer.complete', $offer->id);
        }
    }

    public function complete(Request $request, $offerId)
    {
        if (!Auth::guard('web')->check()) {
            return redirect()->route('user.login');
        }

        $offer = CustomerOffer::with(['chat', 'seller', 'acceptedOrder'])->findOrFail($offerId);
        $user = Auth::guard('web')->user();

        // Verify user owns this offer
        if ($offer->user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }

        // Get breadcrumb image (same as service checkout)
        $breadcrumb = app(\App\Http\Controllers\FrontEnd\MiscellaneousController::class)::getBreadcrumb();

        return view('frontend.customer-offer.complete', compact('offer', 'breadcrumb'));
    }

    // Add this method to support the new route for customer offer order details
    public function orderDetails($orderId)
    {
        $order = \App\Models\CustomerOffer::find($orderId);
        if (!$order) {
            abort(404, 'Order not found');
        }
        // Optionally, check if the authenticated user is allowed to view this order
        if (auth()->id() !== $order->user_id) {
            abort(403, 'Unauthorized');
        }
        return view('frontend.customer-offer.order-details', compact('order'));
    }

    private function getGatewayType($paymentMethod)
    {
        $onlineGateways = OnlineGateway::where('status', 1)->pluck('name')->toArray();
        return in_array($paymentMethod, $onlineGateways) ? 'online' : 'offline';
    }

    private function isOnlineGateway($paymentMethod)
    {
        return $this->getGatewayType($paymentMethod) === 'online';
    }

    private function prepareFormData($request, $form)
    {
        if (!$form) {
            return [];
        }

        $formData = [];
        $formFields = $form->input()->orderBy('order_no', 'asc')->get();

        foreach ($formFields as $field) {
            $fieldName = $field->name;
            $fieldValue = '';

            if ($field->type == 8) { // File
                $fieldName = 'form_builder_' . $field->name;
                if ($request->hasFile($fieldName)) {
                    $file = $request->file($fieldName);
                    $fileName = \App\Http\Helpers\UploadFile::store('./assets/file/orders/', $file);
                    $fieldValue = $fileName;
                }
            } else {
                $fieldValue = $request->input($fieldName, '');
            }

            $formData[$fieldName] = $fieldValue;
        }

        return $formData;
    }

    private function redirectToPaymentGateway($request, $orderData, $order)
    {
        $paymentMethod = $request->payment_method;
        
        // Map payment method to controller
        $gatewayMap = [
            'PayPal' => 'PayPalController',
            'Stripe' => 'StripeController',
            'Razorpay' => 'RazorpayController',
            'Paystack' => 'PaystackController',
            'Flutterwave' => 'FlutterwaveController',
            'MercadoPago' => 'MercadoPagoController',
            'Mollie' => 'MollieController',
            'Paytm' => 'PaytmController',
            'Authorize.Net' => 'AuthorizeNetController',
            'PhonePe' => 'PhonePeController',
            'Yoco' => 'YocoController',
            'Perfect Money' => 'PerfectMoneyController',
            'Toyyibpay' => 'ToyyibpayController',
        ];

        if (isset($gatewayMap[$paymentMethod])) {
            $controllerClass = 'App\\Http\\Controllers\\FrontEnd\\PaymentGateway\\' . $gatewayMap[$paymentMethod];
            if (class_exists($controllerClass)) {
                $controller = new $controllerClass();
                return $controller->index($request, $orderData, 'service');
            }
        }

        // Fallback to completion page
        return redirect()->route('customer.offer.complete', $order->id);
    }
} 