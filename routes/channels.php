<?php

use App\Models\Chat;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel("channel.{chat_id}",function ($user,$chat_id){
    Log::info('private message');
    return $user->chats()->where('id',$chat_id)->where('status','open')->exists();
});
