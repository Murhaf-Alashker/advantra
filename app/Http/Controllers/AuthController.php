<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\UnverifiedUserRequest;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
            $requestAble = $this->requestAble($request->email);

            if(!$requestAble)
            {
                return response()->json(['message' => 'Rate limit exceeded. Try again later.'], 429);
            }

            $user=$this->createOrUpdateUnverifiedUser($request->validated(),'create');

            if(!$user['status'])
            {
                return response()->json('Sorry , something went wrong. Please try again later');
            }

            $this->sendVerificationCodeMail($user['verify_code'], $user['name'], $user['email']);

            return response()->json('Verification code has been sent to your email.');
        }

        catch (\Exception $e) {
            throw new \Exception($e->getMessage()?: 'something went wrong');
        }
    }

    public function resendVerificationCode(UnverifiedUserRequest $request)
    {
        try {
            $requestAble = $this->requestAble($request->email);

            if(!$requestAble)
            {
                return response()->json(['message' => 'Rate limit exceeded. Try again later.'], 429);
            }

            $user=$this->createOrUpdateUnverifiedUser($request->validated(),'update');

            if(!$user['status'])
            {
                return response()->json(['message' => 'Sorry , something went wrong. Please try again later.']);
            }

            $this->sendVerificationCodeMail($user['verify_code'], $user['name'], $user['email']);

            return response()->json(['message' => 'Verification code has been resent to your email.']);
        }

        catch (\Exception $e) {
            throw new \Exception($e->getMessage() ?: 'something went wrong.');
        }
    }


    public function register(RegisterRequest $request)
    {
        $info=$request->validated();

        $unVerifiedUser=DB::table('unverified_users')
            ->where('email', $info['email'])
            ->first();

        if(!$unVerifiedUser)
        {
            return response()->json(['message' => 'This email is not valid.']);
        }

        if($unVerifiedUser->verify_code!=$info['code'])
        {
            return response()->json('This code is not valid.');
        }

        $expiredDate=$unVerifiedUser->expired_at;

        if(Carbon::parse($expiredDate)->lessThan(Carbon::now()))
        {
            return response()->json(['message' => 'This code has expired.']);
        }

        try {
            $user=User::create([

                'name' => $unVerifiedUser->name,
                'email' => $unVerifiedUser->email,
                'password' => $unVerifiedUser->password,
                'email_verified_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            $token = $user->createToken('user_token', ['api-user'])->plainTextToken;

            DB::table('unverified_users')
                ->where('email', $unVerifiedUser->email)
                ->delete();

            return response()->json(['message' => 'Registration successful.', 'user'=>$user, 'token'=>$token]);
        }

        catch (\Exception $e) {
            throw new \Exception($e->getMessage() ?: 'something went wrong.');
        }

    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email',$request->email)->first();
        if(!$user || !Hash::check( $request->password , $user->password )){
            return response()->json(['message' => 'Wrong email or password .']);
        }

        $token = $user->createToken('user_token',['api-user'])->plainTextToken;

        return response()->json(['message' => 'Login successful.', 'token' => $token]);

    }

    public function logout()
    {
        auth()->guard('api-user')->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }


    private function sendVerificationCodeMail(string $verifyCode, string $name, string $email):void
    {
        try {
            Mail::to($email)->send(new VerificationCodeMail($verifyCode, $name));
        }

        catch (\Exception $e) {
            throw new \Exception($e->getMessage() ?: 'something went wrong.');
        }

    }


    private function createOrUpdateUnverifiedUser(array $info, string $method = 'create'):array
    {
        $user = $this->userData($info);

        $status = false;

        DB::transaction(function () use ($user,$method,&$status)
        {
            if($method === 'create' && $user['name']) {
                $status = $this->createUnverifiedUser($user);
            }

            else if($method === 'update')
            {
                $status = $this->updateUnverifiedUser($user);
            }
        });

        if(empty($user['name']))
        {
            $user['name'] = DB::table('unverified_users')
                ->where('email', $user['email'])
                ->first()->name ?? null;
        }

        $user['status'] = $status;

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
            $user['password'] = $info['password'];
            $user['name'] = $info['name'];
        }

        return $user;
    }

    private function createUnverifiedUser(array $user):bool
    {
        $exist = DB::table('unverified_users')
            ->where('email', $user['email'])
            ->exists();
        if($exist)
        {
            return $this->updateUnverifiedUser($user);
        }

        return DB::table('unverified_users')
            ->Insert([
                'email' => $user['email'],
                'name' => $user['name'],
                'password' => Hash::make($user['password']),
                'verify_code' => $user['verify_code'],
                'expired_at' => $user['expired_at'],
            ]);
    }

    private function updateUnverifiedUser(array $user):bool
    {
        return DB::table('unverified_users')
            ->where('email', $user['email'])
            ->update([
                'verify_code' => $user['verify_code'],
                'expired_at' => $user['expired_at'],
            ]) > 0;
    }

    private function requestAble(string $email):bool
    {
        try {
            $key = "verify_code_to:{$email}";

            $requestCount = Cache::get($key) ?? 0;

            if ($requestCount >= 5) {
                return false;
            }

            Cache::increment($key);

            if ($requestCount == 0) {
                Cache::put($key, 1, now()->addDays(1));
            }

            return true;
        }
        catch (\Exception $e) {
            throw new \Exception($e->getMessage() ?: 'something went wrong.');
        }
    }
}
