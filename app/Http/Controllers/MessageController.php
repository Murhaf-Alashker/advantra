<?php

namespace App\Http\Controllers;

use App\Enums\MediaType;
use App\Events\GotMessage;
use App\Jobs\SendMessage;
use App\Libraries\FileManager;
use App\Models\Chat;
use App\Models\Guide;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public const FILE_PATH =  'uploads/chats/';

    public function chats()
    {
        $user = Auth::guard('api-user')->user() ?? Auth::guard('api-guide')->user();

        return $user ? $user->chats()->get() : response()->json(['message' => 'unauthorized'], 403);
    }

    public function messages(Chat $chat){
        $messages = $chat->messages()->with('messageable')->paginate(50);
        return response()->json($messages);
    }

    public function sendMessage(Request $request, Chat $chat){
        $validated = $request->validate([
            'message' => ['nullable','string','min:1','max:500'],
            'media' => ['nullable','file','mimes:' . implode(',', MediaType::values()) ,'max:51200'],
        ]);
        if (empty($validated['message']) && !$request->hasFile('media')) {
            return response()->json(['error' => 'you can`t send empty message'], 422);
        }

        $user = Auth::guard('api-user')->user() ?? Auth::guard('api-guide')->user();

        $type = $user instanceof User ? User::class : Guide::class;

        abort_unless($user->chats()->whereKey($chat->id)->exists(), 403);

       //  Log::info('controller');
        \Illuminate\Support\Facades\Storage::drive('public')->put('a1.json','controller');
        $message = $chat->messages()->create([
            'message' => $validated['message'] ?? null,
            'messageable_type' => $type,
            'messageable_id' => $user->id,
        ]);
        if($request->hasFile('media')){
            $media = $request->file('media')->storeAs(self::FILE_PATH.$chat->id.'/', $message->id.'.'.$request->file('media')->getClientOriginalExtension(),'public');
            $url =Storage::disk('public')->url($media);
            $message->media = $url;
            $message->save();
        }

        SendMessage::dispatch($message);

        return response()->json($message,201);
    }
}
