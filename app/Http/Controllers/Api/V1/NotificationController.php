<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        return response()->json([
            'status' => true,
            'message' => 'Notifications fetched successfully',
            'data' => [
                'unread' => $user->unreadNotifications,
                'read' => $user->readNotifications,
            ]
        ]);
    }

    public function show($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        
        return response()->json([
            'status' => true,
            'notification' => $notification
        ]);
    }

    public function viewNotification($id)
    {
        $user = auth()->user();

        $notification = $user->notifications()->where('id', $id)->firstOrFail();

        // Mark as read if not already
        if (is_null($notification->read_at)) {
            $notification->markAsRead();
        }

        return response()->json([
            'status' => true,
            'message' => 'Notification retrieved successfully',
            'data' => $notification
        ]);
    }


}
