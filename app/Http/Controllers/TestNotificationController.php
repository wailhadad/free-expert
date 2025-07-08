<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TestNotificationController extends Controller
{
    protected $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Send a test notification to the current user
     */
    public function sendTestNotification(Request $request)
    {
        $user = Auth::user();
        $admin = Auth::guard('admin')->user();
        $seller = Auth::guard('seller')->user();

        $notifiable = $user ?? $admin ?? $seller;

        if (!$notifiable) {
            return response()->json(['error' => 'No authenticated user found'], 401);
        }

        $data = [
            'type' => 'test',
            'title' => 'Test Notification',
            'message' => 'This is a test real-time notification sent at ' . now()->format('H:i:s'),
            'url' => '/notifications',
            'icon' => 'bi bi-bell',
            'extra' => [
                'test' => true,
                'timestamp' => now()->toISOString()
            ]
        ];

        $this->notificationService->sendRealTime($notifiable, $data);

        return response()->json([
            'success' => true,
            'message' => 'Test notification sent successfully',
            'data' => $data
        ]);
    }

    /**
     * Send a test notification to all users of a specific type
     */
    public function sendTestNotificationToAll(Request $request)
    {
        $type = $request->input('type', 'all'); // admin, seller, user, all

        $data = [
            'type' => 'system',
            'title' => 'System Notification',
            'message' => 'This is a system-wide test notification sent at ' . now()->format('H:i:s'),
            'url' => '/notifications',
            'icon' => 'bi bi-gear',
            'extra' => [
                'test' => true,
                'timestamp' => now()->toISOString()
            ]
        ];

        switch ($type) {
            case 'admin':
                $this->notificationService->notifyAdmins($data);
                break;
            case 'seller':
                $this->notificationService->notifySellers($data);
                break;
            case 'user':
                $this->notificationService->notifyUsers($data);
                break;
            case 'all':
            default:
                $this->notificationService->notifyAll($data);
                break;
        }

        return response()->json([
            'success' => true,
            'message' => "Test notification sent to all {$type}s successfully",
            'data' => $data
        ]);
    }

    /**
     * Send a test chat notification
     */
    public function sendTestChatNotification(Request $request)
    {
        $user = Auth::user();
        $admin = Auth::guard('admin')->user();
        $seller = Auth::guard('seller')->user();

        $notifiable = $user ?? $admin ?? $seller;

        if (!$notifiable) {
            return response()->json(['error' => 'No authenticated user found'], 401);
        }

        // Determine the type of user and send appropriate chat notification
        $notificationData = [];
        
        if ($user) {
            // Test notification from seller to user
            $notificationData = [
                'type' => 'chat',
                'title' => 'Test Chat Message from Seller',
                'message' => 'This is a test chat message from a seller regarding order #TEST123',
                'url' => '/user/service-orders',
                'icon' => 'fas fa-comment',
                'extra' => [
                    'order_id' => 1,
                    'order_number' => 'TEST123',
                    'seller_name' => 'Test Seller',
                    'message_preview' => 'This is a test message preview...',
                    'has_attachment' => false,
                    'test' => true
                ]
            ];
        } elseif ($seller) {
            // Test notification from user to seller
            $notificationData = [
                'type' => 'chat',
                'title' => 'Test Chat Message from Customer',
                'message' => 'This is a test chat message from a customer regarding order #TEST123',
                'url' => '/seller/orders',
                'icon' => 'fas fa-comment',
                'extra' => [
                    'order_id' => 1,
                    'order_number' => 'TEST123',
                    'customer_name' => 'Test Customer',
                    'message_preview' => 'This is a test message preview...',
                    'has_attachment' => false,
                    'test' => true
                ]
            ];
        } elseif ($admin) {
            // Test notification for admin
            $notificationData = [
                'type' => 'chat',
                'title' => 'Test Chat Message',
                'message' => 'This is a test chat message notification',
                'url' => '/admin/orders',
                'icon' => 'fas fa-comment',
                'extra' => [
                    'test' => true
                ]
            ];
        }

        // Use NotificationService for real-time delivery
        $this->notificationService->sendRealTime($notifiable, $notificationData);

        return response()->json([
            'success' => true,
            'message' => 'Test chat notification sent successfully',
            'data' => $notificationData
        ]);
    }
} 