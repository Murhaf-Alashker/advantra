<?php

namespace App\Services;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function getNotifications(array $data)
    {
        $guard = $data['type'];
         $currentUser = Auth::guard('api-'.$guard)->user();

        $unread = $currentUser->notifications()
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $read = $currentUser->notifications()
            ->whereNotNull('read_at')
            ->orderBy('created_at', 'desc')
            ->get();

        $notifications = $unread->concat($read);

         return NotificationResource::collection($notifications);
    }

    public function markAsRead(DatabaseNotification $notification){
        if($notification){
            $notification->markAsRead();
        }
        return response()->json([
            'message' => 'Notification marked as read'
        ]);
    }

    public function updateFcmToken(array $data){
        $guard = $data['type'];
        $currentUser = Auth::guard('api-'.$guard)->user();

        if (!$currentUser) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        $currentUser->fcm_token = $data['token'];
        $currentUser->save();


        return response()->json(['message' => 'FCM token updated successfully.']);
    }
}
