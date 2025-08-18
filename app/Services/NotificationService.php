<?php

namespace App\Services;

use App\Http\Resources\NotificationResource;
use App\Models\Guide;
use App\Models\User;
use App\Notifications\PersonalNotification;
use App\Notifications\PublicNotification;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    public function getNotifications(array $data)
    {
        $guard = $data['type'];
        $currentUser = Auth::guard('api-' . $guard)->user();

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

    public function markAsRead(DatabaseNotification $notification)
    {
        if ($notification) {
            $notification->markAsRead();
        }
        return response()->json([
            'message' => 'Notification marked as read'
        ]);
    }

    public function updateFcmToken(array $data)
    {
        $guard = $data['type'];
        $currentUser = Auth::guard('api-' . $guard)->user();

        if (!$currentUser) {
            return response()->json(['message' => 'User not authenticated.'], 401);
        }

        $currentUser->fcm_token = $data['token'];
        $currentUser->save();


        return response()->json(['message' => 'FCM token updated successfully.']);
    }

    protected $receivers;
    protected $failed;
    public function storePersonalNotification(array $data)
    {
        try {
            if ($data['type'] === 'user') {
                $this->receivers = User::whereIn('id', $data['ids'])
                                       ->get();
            } else if ($data['type'] === 'guide') {
                $this->receivers = Guide::whereIn('id', $data['ids'])
                                         ->get();
            }

              foreach ($this->receivers as $receiver) {
                  if(empty($receiver->fcm_token)) {
                      $failed[] = $receiver->id;
                  }else{
                      $receiver->notify(new PersonalNotification($data['title'], $data['body'], $data['data']??[]));
                  }
              }
              if(!empty($failed)) {
                  return response()->json(['message' => 'Notification sent but some users dont have fcm token.',
                      'failed users'=>$failed]);
              }else{
                  return response()->json(['message' => 'Notification sent successfully.']);
              }
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to send notifications', 'error' => $e->getMessage()], 500);

        }
    }
        public function storePublicNotification(array $data){
        try{
            if($data['type'] === 'user') {
                $this->receivers = User::whereNotNull('fcm_token')->get();
            }elseif ($data['type'] === 'guide') {
                $this->receivers = Guide::whereNotNull('fcm_token')->get();
            }
            Notification::send($this->receivers, new PublicNotification($data['title'], $data['body'], $data['data']??[]));
            return response()->json(['message' => 'Notification sent successfully.']);
        }catch (\Throwable $e) {
            return response()->json(['message' => 'Failed to send notifications', 'error' => $e->getMessage()], 500);

        }
                }

}
