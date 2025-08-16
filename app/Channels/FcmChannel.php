<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Illuminate\Support\Facades\Log;



class FcmChannel
{
    protected $messaging;

    public function __construct()
    {
        Log::info('FcmChannel construct ' );
        $firebase = (new Factory)
            ->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')));
          //  ->withServiceAccount(storage_path('app/json/adventra-f15ec-firebase-adminsdk-fbsvc-ffb1c18a52.json'));

        $this->messaging = $firebase->createMessaging();
    }

    public function send($notifiable, Notification $notification)
    {
        Log::info('FcmChannel send() called for user ' . $notifiable->id);
       try {
           $fcmData = $notification->toFcm($notifiable);

           if (!$fcmData || empty($fcmData['token'])) {
               Log::warning('Empty FCM token for user ' . $notifiable->id);
               return null;
           }

           $message = CloudMessage::withTarget('token', $fcmData['token'])
               ->withNotification(FcmNotification::create($fcmData['title'], $fcmData['body']))
               ->withData($fcmData['data'] ?? []);

           $result = $this->messaging->send($message);
           Log::info('FCM message sent successfully for user ' . $notifiable->id);
           return $result;
       } catch (\Throwable $e) {
           //Log::error('FCM send failed: ' . $e->getMessage());
           Log::error('FCM send failed for user ' . $notifiable->id . ': ' . $e->getMessage());
           return null;
       }
    }
}
