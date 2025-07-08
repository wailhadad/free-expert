<?php

namespace App\Http\Controllers\FrontEnd;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index()
    {
        $user = Auth::guard('web')->user();
        $notifications = $user->notifications()->latest()->paginate(15);
        return view('frontend.user.notifications', compact('notifications'));
    }

    public function unreadCount()
    {
        $user = Auth::guard('web')->user();
        return response()->json(['count' => $user->unreadNotifications()->count()]);
    }

    public function markAsRead($id)
    {
        $user = Auth::guard('web')->user();
        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification && $notification->read_at === null) {
            $notification->markAsRead();
        }
        $unreadCount = $user->unreadNotifications()->count();
        return response()->json(['success' => true, 'unreadCount' => $unreadCount]);
    }

    public function markAllAsRead()
    {
        $user = Auth::guard('web')->user();
        $user->unreadNotifications->markAsRead();
        $unreadCount = $user->unreadNotifications()->count();
        return response()->json(['success' => true, 'unreadCount' => $unreadCount]);
    }

    public function destroy($id)
    {
        $user = Auth::guard('web')->user();
        $notification = $user->notifications()->where('id', $id)->first();
        if ($notification) {
            $notification->delete();
        }
        return back();
    }

    public function clearAll()
    {
        $user = Auth::guard('web')->user();
        $user->notifications()->delete();
        return back();
    }

    public function dropdown()
    {
        $user = Auth::guard('web')->user();
        $notifications = $user->unreadNotifications()->take(5)->get();
        $unreadCount = $user->unreadNotifications()->count();
        $guard = 'web';
        return view('components.notification-bell-dropdown', compact('notifications', 'unreadCount', 'guard'))->render();
    }

    public function list()
    {
        $user = Auth::guard('web')->user();
        $notifications = $user->notifications()->latest()->paginate(20);
        $guard = 'web';
        return view('components.notification-list', compact('notifications', 'guard'))->render();
    }
} 