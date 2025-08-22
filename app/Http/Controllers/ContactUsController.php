<?php

namespace App\Http\Controllers;

use App\Mail\ContactUsMail;
use App\Models\ContactUs;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class ContactUsController extends Controller
{
    public function store(Request $request){
        $validated = $request->validate([
            'body' => 'required|string|max:255',
        ]);

        $id = Auth::guard('api-user')->id();
       $user =  User::findOrFail($id);

       // $user = auth()->user();
        if($user){
       $contact =  ContactUs::create([
           'body' => $validated['body'],
           'user_id' => $user->id,
       ]);
        Mail::to('adventracompany@gmail.com')->queue(new ContactUsMail($validated['body'],$user->email,$user->name));

        return response()->json([
            'message' => 'Your message has been sent'
        ]);
    }else{
            return response()->json(['error' => 'Unauthorized'], 401);
        }
}
}
