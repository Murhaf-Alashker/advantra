<?php

namespace App\Jobs;

use App\Events\GotMessage;
use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SendMessage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public Message $message)
    {
        Log::info('send message');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        GotMessage::dispatch([
            'id' => $this->message->id,
            'user_id' => $this->message->user_id,
            'message' => $this->message->message,
            'media' => $this->message->media,
            'chat_id' => $this ->message->chat_id,
            'created_at' => $this->message->created_at,
            'group_trip_id' => $this->message->chat->group_trip_id,
        ]);
    }

}
