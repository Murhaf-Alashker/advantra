<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UnverifiedUserRequest;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Mockery\Exception;

class AuthController extends Controller
{
    public  function generateUnverifiedUser(UnverifiedUserRequest $request)
    {
        try {

            $user=$this->createOrUpdateUnverifiedUser($request->validated());

            $this->sendMail($user['verify_code'], $user['name'], $user['email']);

            return response()->json('Verification code has been sent to your email.');

        }
        catch (\Exception $e) {

            throw new \Exception($e->getMessage()?: 'something went wrong');

        }
    }

    public function resendVerificationCode(UnverifiedUserRequest $request)
    {
        try {

            $user=$this->createOrUpdateUnverifiedUser($request->validated());

            if(!$user['name']) {
                return response()->json('something went wrong. Please try again.');
            }

                $this->sendMail($user['verify_code'], $user['name'], $user['email']);

            return response()->json('Verification code has been resent to your email.');

        }
        catch (\Exception $e) {
            throw new \Exception($e->getMessage()?: 'something went wrong');
        }
    }


    public function register(RegisterRequest $request)
    {
        $info=$request->validated();
        $unVerifiedUser=DB::table('unverified_users')
            ->where('email',$info['email'])
            ->first();
        if(!$unVerifiedUser){
            return response()->json('This email is not valid.');
        }

        if($unVerifiedUser->verify_code!=$info['code'])
        {
            return response()->json('This code is not valid.');
        }

        $expiredDate=$unVerifiedUser->expired_at;

        if(Carbon::parse($expiredDate)->lessThan(Carbon::now()))
        {
            return response()->json('This code has expired.');
        }

        try {

                $user=User::create([

                    'name'=>$unVerifiedUser->name,
                    'email'=>$unVerifiedUser->email,
                    'password'=>Hash::make($unVerifiedUser->password),
                    'email_verified_at'=>Carbon::now()->format('Y-m-d H:i:s'),
                ]);

                $token = $user->createToken('user_token',['api-user'])->plainTextToken;

                DB::table('unverified_users')
                    ->where('email',$unVerifiedUser->email)
                    ->delete();

            return response()->json(['message' => 'Registration successful.','user'=>$user,'token'=>$token]);


        }
        catch (\Exception $e) {
            throw new \Exception($e->getMessage()?: 'something went wrong');
        }

    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email',$request->email)->first();

        if(!$user || Hash::check( $request->password , $user->password )){
            return response()->json('Wrong email or password .');
        }

        $token = $user->createToken('user_token',['api-user'])->plainTextToken;

        return response()->json(['message' => 'Login successful.','token' => $token]);

    }

    public function logout(){
        auth()->guard('api-user')->user()->currentAccessToken()->delete();
        return response()->json('Logged out successfully.');
    }


    private function sendMail(string $verifyCode,string $name ,string $email):void
    {
        Mail::to($email)->send(new VerificationCodeMail($verifyCode, $name));
    }


    private function createOrUpdateUnverifiedUser(array $info):array
    {
        $user = $this->userData($info);

        DB::transaction(function () use ($user)
        {
            if(array_key_exists('name', $user)){
            DB::table('unverified_users')
                ->Insert([
                    'email' => $user['email'],
                    'name' => $user['name'],
                    'password'=>Hash::make($user['password']),
                    'verify_code'=>$user['verify_code'],
                    'expired_at'=>$user['expired_at'],

                ],
                );
            }

            else{
                DB::table('unverified_users')
                    ->where('email',$user['email'])
                    ->update([
                        'verify_code' => $user['verify_code'],
                        'expired_at' => $user['expired_at'],
                    ]);
            }
        });

        if(empty($user['name']))
        {
            $user['name'] = DB::table('unverified_users')
                ->where('email', $user['email'])
                ->first()->name??null;
        }

        return $user;
    }


    private function userData(array $info): array
    {
        $user=[
            'email' => $info['email'],
            'verify_code' => (string) rand(100000, 999999),
            'expired_at' => now()->addMinutes(5)->format('Y-m-d H:i:s'),
        ];

        if(!empty($info['name']) && !empty($info['password']))
        {
            $user['password'] = Hash::make($info['password']);
            $user['name'] = $info['name'];
        }

        return $user;
    }
}
