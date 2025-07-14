<?php

namespace App\Http\Controllers;

use App\Models\CustomerOffer;
use App\Models\ClientService\Form;
use App\Models\DirectChat;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CustomerOfferController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get forms for seller to create offers
     */
    public function getForms(Request $request)
    {
        if (!Auth::guard('seller')->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $seller = Auth::guard('seller')->user();
        $forms = Form::where('seller_id', $seller->id)
            ->orWhere('seller_id', null) // Admin forms
            ->with('input')
            ->get();

        return response()->json(['forms' => $forms]);
    }

    /**
     * Create a new customer offer
     */
    public function create(Request $request)
    {
        if (!Auth::guard('seller')->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'chat_id' => 'required|exists:direct_chats,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'delivery_time' => 'required|integer|min:1',
            'form_id' => 'nullable|exists:forms,id',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $seller = Auth::guard('seller')->user();
        $chat = DirectChat::findOrFail($request->chat_id);

        // Verify seller owns this chat
        if ($chat->seller_id !== $seller->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Verify form belongs to seller or is admin form
        if ($request->form_id) {
            $form = Form::findOrFail($request->form_id);
            if ($form->seller_id && $form->seller_id !== $seller->id) {
                return response()->json(['error' => 'Form not found'], 404);
            }
        }

        $offer = CustomerOffer::create([
            'chat_id' => $request->chat_id,
            'seller_id' => $seller->id,
            'user_id' => $chat->user_id,
            'subuser_id' => $chat->subuser_id,
            'form_id' => $request->form_id,
            'title' => $request->title,
            'description' => $request->description,
            'price' => $request->price,
            'currency_symbol' => '$', // Default currency
            'status' => 'pending',
            'expires_at' => $request->expires_at,
            'delivery_time' => $request->delivery_time,
        ]);

        // Broadcast real-time event for offer creation
        event(new \App\Events\CustomerOfferEvent($offer->load(['form.input', 'seller', 'user', 'subuser']), $chat->id, 'created'));

        // Send notification to user
        $notificationData = [
            'type' => 'customer_offer',
            'title' => 'New Customer Offer',
            'message' => "You have received a new offer: {$offer->title}",
            'url' => route('user.discussions') . '?chat_id=' . $chat->id . ($chat->subuser_id ? '&subuser_id=' . $chat->subuser_id : ''),
            'icon' => 'fas fa-gift',
            'extra' => [
                'offer_id' => $offer->id,
                'chat_id' => $chat->id,
                'seller_name' => $seller->username,
                'price' => $offer->formatted_price,
            ],
        ];
        $this->notificationService->sendRealTime($chat->user, $notificationData);

        // Send notification to all admins
        $adminNotificationData = [
            'type' => 'customer_offer',
            'title' => 'New Customer Offer Created',
            'message' => "A new customer offer '{$offer->title}' was created by seller {$seller->username} for user ID {$chat->user_id}.",
            'url' => route('admin.discussions') . '?chat_id=' . $chat->id . ($chat->subuser_id ? '&subuser_id=' . $chat->subuser_id : ''),
            'icon' => 'fas fa-gift',
            'extra' => [
                'offer_id' => $offer->id,
                'chat_id' => $chat->id,
                'seller_name' => $seller->username,
                'user_id' => $chat->user_id,
                'price' => $offer->formatted_price,
            ],
        ];
        $this->notificationService->notifyAdmins($adminNotificationData);

        return response()->json([
            'success' => true,
            'offer' => $offer->load(['form.input']),
            'message' => 'Offer created successfully'
        ]);
    }

    /**
     * Get offers for a chat
     */
    public function getOffers(Request $request, $chatId)
    {
        $chat = DirectChat::findOrFail($chatId);
        
        // Check authorization based on user type
        if (Auth::guard('web')->check()) {
            $user = Auth::guard('web')->user();
            if ($chat->user_id !== $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } elseif (Auth::guard('seller')->check()) {
            $seller = Auth::guard('seller')->user();
            if ($chat->seller_id !== $seller->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } elseif (Auth::guard('admin')->check()) {
            // Admin can view all offers
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $offers = CustomerOffer::where('chat_id', $chatId)
            ->with(['form.input', 'seller', 'user', 'subuser'])
            ->orderBy('created_at', 'asc') // Fix: chronological order
            ->get();

        return response()->json(['offers' => $offers]);
    }

    /**
     * Accept an offer
     */
    public function accept(Request $request, $offerId)
    {
        if (!Auth::guard('web')->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::guard('web')->user();
        $offer = CustomerOffer::with(['chat', 'seller', 'form'])->findOrFail($offerId);

        // Verify user owns this offer
        if ($offer->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!$offer->canBeAccepted()) {
            return response()->json(['error' => 'Offer cannot be accepted'], 400);
        }

        // Set status to 'checkout_pending' instead of 'accepted'
        $offer->update(['status' => 'checkout_pending']);

        // Broadcast real-time event for offer acceptance (now 'checkout_pending')
        event(new \App\Events\CustomerOfferEvent($offer->load(['form.input', 'seller', 'user', 'subuser']), $offer->chat_id, 'checkout_pending'));

        // Do NOT send notification to seller or admins here; only after successful checkout

        return response()->json([
            'success' => true,
            'message' => 'Offer accepted, proceed to checkout',
            'redirect_url' => route('customer.offer.checkout', $offer->id)
        ]);
    }

    /**
     * Decline an offer
     */
    public function decline(Request $request, $offerId)
    {
        if (!Auth::guard('web')->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::guard('web')->user();
        $offer = CustomerOffer::with(['chat', 'seller'])->findOrFail($offerId);

        // Verify user owns this offer
        if ($offer->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if (!in_array($offer->status, ['pending', 'checkout_pending'])) {
            return response()->json(['error' => 'Offer cannot be declined'], 400);
        }

        $offer->update(['status' => 'declined']);

        // Broadcast real-time event for offer decline
        event(new \App\Events\CustomerOfferEvent($offer->load(['form.input', 'seller', 'user', 'subuser']), $offer->chat_id, 'declined'));

        // Send notification to seller
        $notificationData = [
            'type' => 'customer_offer_declined',
            'title' => 'Customer Offer Declined',
            'message' => "Your offer '{$offer->title}' has been declined",
            'url' => route('seller.discussions') . '?chat_id=' . $offer->chat_id . ($offer->subuser_id ? '&subuser_id=' . $offer->subuser_id : ''),
            'icon' => 'fas fa-times-circle',
            'extra' => [
                'offer_id' => $offer->id,
                'chat_id' => $offer->chat_id,
                'customer_name' => $offer->subuser ? $offer->subuser->username : $user->username,
                'price' => $offer->formatted_price,
            ],
        ];
        $this->notificationService->sendRealTime($offer->seller, $notificationData);

        // Send notification to all admins
        $adminNotificationData = [
            'type' => 'customer_offer_declined',
            'title' => 'Customer Offer Declined',
            'message' => "Customer offer '{$offer->title}' was declined by user ID {$user->id}.",
            'url' => route('admin.discussions') . '?chat_id=' . $offer->chat_id . ($offer->subuser_id ? '&subuser_id=' . $offer->subuser_id : ''),
            'icon' => 'fas fa-times-circle',
            'extra' => [
                'offer_id' => $offer->id,
                'chat_id' => $offer->chat_id,
                'user_id' => $user->id,
                'seller_name' => $offer->seller->username,
                'price' => $offer->formatted_price,
            ],
        ];
        $this->notificationService->notifyAdmins($adminNotificationData);

        return response()->json([
            'success' => true,
            'message' => 'Offer declined successfully'
        ]);
    }

    /**
     * Get offer details
     */
    public function getOfferDetails(Request $request, $offerId)
    {
        if (!Auth::guard('web')->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $user = Auth::guard('web')->user();
        $offer = CustomerOffer::with(['chat', 'seller', 'form.input', 'user', 'subuser'])->findOrFail($offerId);

        // Verify user owns this offer
        if ($offer->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json(['offer' => $offer]);
    }
} 