<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

class NotificationController extends Controller
{
    /**
     * Get the authenticated notifiable (user, seller, or admin)
     */
    protected function getNotifiable()
    {
        if (Auth::guard('web')->check()) {
            return Auth::guard('web')->user();
        } elseif (Auth::guard('seller')->check()) {
            return Auth::guard('seller')->user();
        } elseif (Auth::guard('admin')->check()) {
            return Auth::guard('admin')->user();
        }
        abort(401, 'Unauthorized');
    }

    // Web: List notifications
    public function index(Request $request)
    {
        $routeGuard = $request->route('guard');
        if ($routeGuard && Auth::guard($routeGuard)->check()) {
            $guard = $routeGuard;
            $notifiable = Auth::guard($guard)->user();
        } else {
            if (Auth::guard('web')->check()) {
                $guard = 'web';
                $notifiable = Auth::guard('web')->user();
            } elseif (Auth::guard('seller')->check()) {
                $guard = 'seller';
                $notifiable = Auth::guard('seller')->user();
            } elseif (Auth::guard('admin')->check()) {
                $guard = 'admin';
                $notifiable = Auth::guard('admin')->user();
            } else {
                abort(401, 'Unauthorized');
            }
        }
        $notifications = $notifiable ? $notifiable->notifications()->latest()->paginate(20) : collect();
        $unreadCount = $notifiable ? $notifiable->unreadNotifications()->count() : 0;
        return view('notifications.index', compact('notifications', 'guard', 'unreadCount'));
    }

    // Web: Unread count
    public function unreadCount()
    {
        $notifiable = $this->getNotifiable();
        $count = $notifiable->unreadNotifications()->count();
        return response()->json(['count' => $count]);
    }

    // Web: Mark as read
    public function markAsRead($id)
    {
        $notifiable = $this->getNotifiable();
        $notification = $notifiable->notifications()->findOrFail($id);
        $notification->markAsRead();
        return response()->json(['success' => true]);
    }

    // Web: Mark all as read
    public function markAllAsRead()
    {
        $notifiable = $this->getNotifiable();
        $notifiable->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }

    // Web: Delete notification
    public function destroy($id)
    {
        $notifiable = $this->getNotifiable();
        $notification = $notifiable->notifications()->findOrFail($id);
        $notification->delete();
        return response()->json(['success' => true]);
    }

    // Web: Clear all notifications
    public function clearAll()
    {
        $notifiable = $this->getNotifiable();
        $notifiable->notifications()->delete();
        return response()->json(['success' => true]);
    }

    // API: List notifications
    public function apiIndex(Request $request)
    {
        $notifiable = $this->getNotifiable();
        $notifications = $notifiable->notifications()->latest()->paginate(20);
        return response()->json($notifications);
    }

    // API: Unread count
    public function apiUnreadCount()
    {
        $notifiable = $this->getNotifiable();
        $count = $notifiable->unreadNotifications()->count();
        return response()->json(['count' => $count]);
    }

    // API: Mark as read
    public function apiMarkAsRead($id)
    {
        $notifiable = $this->getNotifiable();
        $notification = $notifiable->notifications()->findOrFail($id);
        $notification->markAsRead();
        return response()->json(['success' => true]);
    }

    // API: Mark all as read
    public function apiMarkAllAsRead()
    {
        $notifiable = $this->getNotifiable();
        $notifiable->unreadNotifications->markAsRead();
        return response()->json(['success' => true]);
    }

    // API: Delete notification
    public function apiDestroy($id)
    {
        $notifiable = $this->getNotifiable();
        $notification = $notifiable->notifications()->findOrFail($id);
        $notification->delete();
        return response()->json(['success' => true]);
    }

    // API: Clear all notifications
    public function apiClearAll()
    {
        $notifiable = $this->getNotifiable();
        $notifiable->notifications()->delete();
        return response()->json(['success' => true]);
    }
} 