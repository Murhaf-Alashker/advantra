<?php

namespace App\Notifications;

use App\Channels\FcmChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PublicNotification extends Notification
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

    public function via(object $notifiable): array
    {
        Log::info('PublicNotification via() called for user ' . $notifiable->id);
        return [FcmChannel::class];
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
