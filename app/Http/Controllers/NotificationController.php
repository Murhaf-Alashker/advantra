<?php

namespace App\Http\Controllers;

use App\Models\{User, Admin, Guide};
use App\Notifications\NewNotification;
use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FBNotification;

class NotificationController extends Controller
{
    protected $messaging;

    public function __construct()
    {
        $this->messaging = (new Factory)
            ->withServiceAccount(storage_path('app/firebase_credentials.json'))
            ->createMessaging();
    }

    public function saveToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
            'guard_type' => 'required|in:user,admin,guide'
        ]);

        $user = match($request->guard_type) {
            'admin' => auth('api-admin')->user(),
            'guide' => auth('api-guide')->user(),
            default => auth('api-user')->user()
        };

        $user->update(['fcm_token' => $request->fcm_token]);

        return response()->json(['success' => true]);
    }

    public function sendNotification(Request $request)
    {
        $request->validate([
            'title' => 'required|string',
            'body' => 'required|string',
            'target' => 'required|in:user,admin,guide,all'
        ]);

        $recipients = match($request->target) {
            'user' => User::whereNotNull('fcm_token')->get(),
            'admin' => Admin::whereNotNull('fcm_token')->get(),
            'guide' => Guide::whereNotNull('fcm_token')->get(),
            'all' => collect()
                ->merge(User::whereNotNull('fcm_token')->get())
                ->merge(Admin::whereNotNull('fcm_token')->get())
                ->merge(Guide::whereNotNull('fcm_token')->get())
        };

        foreach ($recipients as $recipient) {
            $this->sendToRecipient($recipient, $request->title, $request->body);
        }

        return response()->json([
            'sent_to' => $request->target,
            'recipients_count' => $recipients->count()
        ]);
    }

    protected function sendToRecipient($recipient, $title, $body)
    {
        // Store in database
        $recipient->notify(new NewNotification($title, $body));

        // Send to Firebase
        $message = CloudMessage::withTarget('token', $recipient->fcm_token)
            ->withNotification(FBNotification::create($title, $body));

        $this->messaging->send($message);
    }
}
