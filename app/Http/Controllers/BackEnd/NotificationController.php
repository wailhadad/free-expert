<?php

namespace App\Http\Controllers\BackEnd;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $admin = Auth::guard('admin')->user();
        $notifications = $admin->notifications()->latest()->paginate(15);
        return view('backend.notifications.index', compact('notifications'));
    }

    public function markAllAsRead()
    {
        $admin = Auth::guard('admin')->user();
        $admin->unreadNotifications->markAsRead();
        $unreadCount = $admin->unreadNotifications()->count();
        return response()->json(['success' => true, 'unreadCount' => $unreadCount]);
    }

    public function clearAll()
    {
        $admin = Auth::guard('admin')->user();
        $admin->notifications()->delete();
        return back();
    }

    public function markAsRead($id)
    {
        $admin = Auth::guard('admin')->user();
        $notification = $admin->notifications()->where('id', $id)->first();
        if ($notification && $notification->read_at === null) {
            $notification->markAsRead();
        }
        $unreadCount = $admin->unreadNotifications()->count();
        return response()->json(['success' => true, 'unreadCount' => $unreadCount]);
    }

    public function destroy($id)
    {
        $admin = Auth::guard('admin')->user();
        $notification = $admin->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->delete();
        }
        return back();
    }

    public function dropdown()
    {
        $admin = Auth::guard('admin')->user();
        $notifications = $admin->unreadNotifications()->take(5)->get();
        $unreadCount = $admin->unreadNotifications()->count();
        $guard = 'admin';
        return view('components.notification-bell-dropdown', compact('notifications', 'unreadCount', 'guard'))->render();
    }

    public function unreadCount()
    {
        $admin = Auth::guard('admin')->user();
        $count = $admin->unreadNotifications()->count();
        return response()->json(['count' => $count]);
    }

    public function list()
    {
        $admin = Auth::guard('admin')->user();
        $notifications = $admin->notifications()->latest()->paginate(15);
        return view('backend.notifications.partials.list', compact('notifications'))->render();
    }
} 