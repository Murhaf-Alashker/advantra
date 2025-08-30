<?php

namespace App\Http\Controllers;

use App\Enums\MediaType;
use App\Events\GotMessage;
use App\Http\Resources\MessageResource;
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
        $messages = $chat->messages()->latest()->with('messageable')->paginate(50);
        return MessageResource::collection($messages);
    }

    public function sendMessage(Request $request, Chat $chat){

//        if($chat->status == 'closed' || !$chat->users()->find(Auth::guard('api-user')->id())){
//            return response()->json(['message' => 'unauthorized'], 403);
//        }
        if ($chat->status === 'close') {
            return response()->json(['message' => 'Chat is closed'], 403);
        }
        if (Auth::guard('api-user')->check()) {
            $userId = Auth::guard('api-user')->id();

            if (!$chat->users()->find($userId)) {
                return response()->json(['message' => 'unauthorized'], 403);
            }
        }
        elseif (Auth::guard('api-guide')->check()) {
            $guideId = Auth::guard('api-guide')->id();

            if ($chat->guide_id != $guideId) {
                return response()->json(['message' => 'unauthorized'], 403);
            }
        }
        else {

           return response()->json(['message' => 'unauthorized'], 403);
        }
        $validated = $request->validate([
            'message' => ['nullable','string','min:1','max:500'],
            'media' => ['nullable','file','mimes:' . implode(',', MediaType::values()) ,'max:51200'],
        ]);
        if (empty($validated['message']) && !$request->hasFile('media')) {
            return response()->json(['error' => 'you can`t send empty message'], 422);
        }

        $user = Auth::guard('api-user')->user() ?? Auth::guard('api-guide')->user();

        $type = $user instanceof User ? User::class : Guide::class;

        \Illuminate\Support\Facades\Storage::drive('public')->put('a1.json','controller');
        $message = $chat->messages()->create([
            'message' => $validated['message'] ?? null,
            'messageable_type' => $type,
            'messageable_id' => $user->id,
            'created_at' => now()->format('Y-m-d H:i:s'),
            'updated_at' => now()->format('Y-m-d H:i:s'),
        ]);
        Log::info('📤 Broadcasting...');

        if($request->hasFile('media')){
            $media = $request->file('media')->storeAs(self::FILE_PATH.$chat->id.'/', uuid_create().'.'.$request->file('media')->getClientOriginalExtension(),'public');
            $url =Storage::disk('public')->url($media);
            $message->media = $url;
            $message->save();
        }
        ;
        broadcast($message->refresh()->toArray())->toOthers();
        //SendMessage::dispatch($message);/////

        return response()->json( new MessageResource($message->refresh()),201);
    }
}
