<?php

namespace App\Http\Controllers;

use App\Http\Requests\PersonalNotificationRequest;
use App\Http\Requests\PublicNotificationRequest;
use App\Notifications\PersonalNotification;
use App\Services\NotificationService;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Auth;
use App\Models\{User, Admin, Guide};
use App\Notifications\NewNotification;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FBNotification;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;
    public function __construct(NotificationService $notificationService){
        $this->notificationService = $notificationService;
    }

      public function getNotifications(Request $request)
      {
          $validated = $request->validate([
              'type' => 'required|in:user,admin,guide',
          ]);
          return $this->notificationService->getNotifications($validated);
      }

    public function markAsRead(DatabaseNotification $notification){
        return $this->notificationService->markAsRead($notification);
    }

    public function updateFcmToken(Request $request) {
       $validated =  $request->validate([
            'token' => 'required|string',
            'type'  => 'required|string|in:user,guide,admin'
        ]);
        return $this->notificationService->updateFcmToken($validated);

    }

    public function storePersonalNotification(PersonalNotificationRequest $request)
    {
        $validated = $request->validated();
        return $this->notificationService->storePersonalNotification($validated);
    }

    public function storePublicNotification(PublicNotificationRequest $request)
    {
        $validated = $request->validated();
        return $this->notificationService->storePublicNotification($validated);
    }

    public function testNotification(){
        $user = Auth::guard('api-user')->user();
        $user->notify(new PersonalNotification('test title' , 'test body' , ['type' => 'event', 'id' => '1']));

    }
}
