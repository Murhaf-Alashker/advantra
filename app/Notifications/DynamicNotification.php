<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class DynamicNotification extends Notification
    implements ShouldQueue
{
    use Queueable;

    protected $title;
    protected $body;
    protected $data;
    protected $token;

    public function __construct($title, $body, array $data = [], $token = null)
    {
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
        $this->token = $token;
    }

    public function via($notifiable)
    {   Log::info('DynamicNotification via() called for user ' . $notifiable->id);
        return ['database', FcmChannel::class];
    }

    public function toDatabase($notifiable)
    {
        return array_merge([
            'title' => $this->title,
            'body'  => $this->body,
        ], $this->data);
    }

    public function toFcm($notifiable)
    {
        return [
            'token' => $this->token ?? $notifiable->fcm_token,
            'title' => $this->title,
            'body'  => $this->body,
            'data'  => $this->data,
        ];
    }
}
