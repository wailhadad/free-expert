<?php

namespace App\Http\Controllers\Seller;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index()
    {
        $seller = Auth::guard('seller')->user();
        $notifications = $seller->notifications()->latest()->paginate(15);
        return view('seller.notifications.index', compact('notifications'));
    }

    public function markAllAsRead()
    {
        $seller = Auth::guard('seller')->user();
        $seller->unreadNotifications->markAsRead();
        $unreadCount = $seller->unreadNotifications()->count();
        return response()->json(['success' => true, 'unreadCount' => $unreadCount]);
    }

    public function clearAll()
    {
        $seller = Auth::guard('seller')->user();
        $seller->notifications()->delete();
        return back();
    }

    public function markAsRead($id)
    {
        $seller = Auth::guard('seller')->user();
        $notification = $seller->notifications()->find($id);
        if ($notification && $notification->read_at === null) {
            $notification->markAsRead();
        }
        $unreadCount = $seller->unreadNotifications()->count();
        return response()->json(['success' => true, 'unreadCount' => $unreadCount]);
    }

    public function destroy($id)
    {
        $seller = Auth::guard('seller')->user();
        $notification = $seller->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->delete();
        }
        return back();
    }

    public function dropdown()
    {
        $seller = Auth::guard('seller')->user();
        \Log::info('Dropdown seller', ['id' => $seller->id]);
        $notifications = $seller->unreadNotifications()->take(5)->get();
        \Log::info('Dropdown notifications count', ['count' => $notifications->count(), 'ids' => $notifications->pluck('id')]);
        $unreadCount = $seller->unreadNotifications()->count();
        \Log::info('Dropdown unreadCount', ['count' => $unreadCount]);
        $guard = 'seller';
        return view('components.notification-bell-dropdown', compact('notifications', 'unreadCount', 'guard'))->render();
    }

    public function list()
    {
        $seller = Auth::guard('seller')->user();
        $notifications = $seller->notifications()->latest()->paginate(20);
        $guard = 'seller';
        return view('components.notification-list', compact('notifications', 'guard'))->render();
    }

    public function unreadCount()
    {
        $seller = Auth::guard('seller')->user();
        \Log::info('Unread count seller', ['id' => $seller->id]);
        $count = $seller->unreadNotifications()->count();
        \Log::info('Unread count', ['count' => $count]);
        return response()->json(['count' => $count]);
    }
} 