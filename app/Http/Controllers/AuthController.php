<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckResetPasswordCodeRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\resetPasswordRequest;
use App\Http\Requests\UnverifiedUserRequest;
use App\Mail\VerificationCodeMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Mockery\Exception;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class AuthController extends Controller
{
    public  function sendVerificationCode(UnverifiedUserRequest $request)
    {
        return $this->createVerificationCode($request,__FUNCTION__);
    }

    public function resendVerificationCode(UnverifiedUserRequest $request)
    {
        return $this->createVerificationCode($request,__FUNCTION__);
    }


    public function register(RegisterRequest $request)
    {
        $info=$request->validated();

        $unVerifiedUser=DB::table('unverified_users')
            ->where('email', $info['email'])
            ->first();

        if(!$unVerifiedUser)
        {
            return response()->json(['message' => __('message.invalid_email')], ResponseAlias::HTTP_BAD_REQUEST);
        }

        if($unVerifiedUser->verify_code!=$info['code'])
        {
            return response()->json(['message' => __('message.invalid_code')], ResponseAlias::HTTP_BAD_REQUEST);
        }

        $expiredDate=$unVerifiedUser->expired_at;

        if(Carbon::parse($expiredDate)->lessThan(Carbon::now()))
        {
            return response()->json(['message' => __('message.expired_code')],ResponseAlias::HTTP_GONE);
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

            return response()->json(['message' => __('message.register_successfully'), 'user'=>$user, 'token'=>$token]);
        }

        catch (\Exception $e) {
            throw new \Exception(__('message.something_wrong'),ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email',$request->email)->first() ?? User::onlyTrashed()->where('email',$request->email)->first();
        if(!$user || !Hash::check( $request->password , $user->password )){
            return response()->json(['message' => __('message.wrong_email_or_password')]);
        }

        if($user->trashed()){
            $user->restore();
            $user->points = 0.00;
            $user->save();
        }

        $token = $user->createToken('user_token',['api-user'])->plainTextToken;

        return response()->json(['message' => __('message.login_successfully'), 'token' => $token]);

    }

    public function logout()
    {
        auth()->guard('api-user')->user()->currentAccessToken()->delete();

        return response()->json(['message' => __('message.logout_successfully')], ResponseAlias::HTTP_OK);
    }

    public function requestResetPasswordCode(Request $request)
    {
        if($request->email)
        {
            $validated = $request->validate([
                'email' => ['required','string','max:30','min:15','email','exists:users,email'],
            ]);
            $email = $validated['email'];
            $user = User::where('email' , $email)->first();
        }
        else
        {
            $user=Auth::guard('api-user')->user();
            $email = $user->email;
        }
        $requestAble = $this->requestAble($email);

        if(!$requestAble)
        {
            return response()->json(['message' => __('message.rate_limit_exceeded')], ResponseAlias::HTTP_TOO_MANY_REQUESTS);
        }
        try{
            $exist = DB::table('password_reset_tokens')->where('email' , $email)->first();
            $code = (string) rand(100000, 999999);

            DB::transaction(function () use ($email,$code,$exist){
                if($exist){
                    DB::table('password_reset_tokens')
                        ->where('email' , $email)
                        ->update([
                            'code' => $code,
                            'expired_at' => Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'),
                        ]);
                }
                else{
                DB::table('password_reset_tokens')->insert([
                    'email' => $email,
                    'code' => $code,
                    'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    'expired_at' => Carbon::now()->addMinutes(5)->format('Y-m-d H:i:s'),
                ]);}
            });

            $this->sendVerificationCodeMail($code, $user->name, $user->email);

            return response()->json(['message' => __('message.send_verify_code')], ResponseAlias::HTTP_OK);
        }
        catch (\Exception $e) {
            throw new \Exception(__('message.something_wrong'),ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }

    }

    public function checkResetPasswordCode(CheckResetPasswordCodeRequest $request)
    {
        $data = $request->validated();
        $info = DB::table('password_reset_tokens')->where('email',$data['email'])->first();
        if($info && $info->code == $data['code']){
            if(Carbon::parse($info->expired_at)->lessThan(Carbon::now())){
                return response()->json(['message' => __('message.expired_code'), 'data' => false],ResponseAlias::HTTP_GONE);
            }
            return response()->json(true, ResponseAlias::HTTP_OK);
        }
        return response()->json(false, ResponseAlias::HTTP_BAD_REQUEST);
    }

    public function resetPasswordUsingCode(resetPasswordRequest $request)
    {
        $data = $request->validated();
        $info = DB::table('password_reset_tokens')->where('email',$data['email'])->first();
        if(!$info){
            return response()->json(['message' => __('message.invalid_email')], ResponseAlias::HTTP_BAD_REQUEST);
        }
        if($info->code != $data['code']){
            return response()->json(['message' => __('message.invalid_code')], ResponseAlias::HTTP_BAD_REQUEST);
        }
        if (Carbon::parse($info->expired_at)->lessThan(Carbon::now())) {
            return response()->json(['message' => __('message.expired_code')], ResponseAlias::HTTP_GONE);
        }
        $user = User::where('email',$data['email'])->first();
        $user->password = Hash::make($data['password']);
        $user->save();
        DB::table('password_reset_tokens')->where('email',$data['email'])->delete();
        return response()->json(['message' => __('message.reset_password_successfully')], ResponseAlias::HTTP_OK);
    }

    public function redirect()
    {
        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback(Request $request)
    {
        $googleToken = $request->input('token');

        if($googleToken)
        {
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($googleToken);
        }

        else
        {
            $googleUser = Socialite::driver('google')->stateless()->user();
        }

        $user = User::where('email', $googleUser->email)->first();
        try {
            if (!$user)
            {
                DB::transaction(function () use($googleUser,&$user)
                {
                    $user = User::create([
                        'name' => $googleUser->name,
                        'email' => $googleUser->email,
                        'password' => $this->createRandomPassword(8),
                        'google_id' => $googleUser->id,
                        'email_verified_at' => Carbon::now()->format('Y-m-d H:i:s'),
                    ]);
                });
            }

            else if (!$user->google_id)
            {
                $user->google_id = $googleUser->id;
                $user->save();
            }

            else if ($user->google_id != $googleUser->id)
            {
                return response()->json(['message' => __('message.something_wrong')], ResponseAlias::HTTP_BAD_REQUEST);
            }

            $token = $user->createToken('user_token', ['api-user'])->plainTextToken;

            return response()->json(['message' => __('message.login_successfully'), 'token' => $token],ResponseAlias::HTTP_OK);

        }
        catch (\Exception $e) {
            throw new \Exception(__('message.something_wrong'),ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function createVerificationCode($request,string $function_name)
    {
        try {
            $requestAble = $this->requestAble($request->email);

            if(!$requestAble)
            {
                return response()->json(['message' => __('message.rate_limit_exceeded')], ResponseAlias::HTTP_TOO_MANY_REQUESTS);
            }

            $action = 'send';
            $method = 'create';

            if(Str::before($function_name,'Verification') === 'resend'){
                $action = 'resend';
                $method = 'update';
            }

            $user=$this->createOrUpdateUnverifiedUser($request->validated(),$method);

            if(!$user['status'])
            {
                return response()->json(['message' => __('message.something_wrong')],ResponseAlias::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->sendVerificationCodeMail($user['verify_code'], $user['name'], $user['email']);

            return response()->json(['message' => __("message.{$action}_verify_code")],ResponseAlias::HTTP_OK);
        }

        catch (\Exception $e) {
            throw new \Exception(__('message.something_wrong'),ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    private function sendVerificationCodeMail(string $verifyCode, string $name, string $email):void
    {
        try {
            Mail::to($email)->send(new VerificationCodeMail($verifyCode, $name));
        }

        catch (\Exception $e) {
            throw new \Exception(__('message.something_wrong'),ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
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

            if ($requestCount == 0) {
                Cache::put($key, 0, now()->addDays(1));
            }

            Cache::increment($key);

            return true;
        }
        catch (\Exception $e) {
            throw new \Exception(__('message.something_wrong'),ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function createRandomPassword(int $length = 8):string
    {
        $uppercase = range('A', 'Z');
        $lowercase = range('a', 'z');
        $numbers = range(0, 9);
        $all=array_merge($uppercase, $lowercase, $numbers);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            if($i == 0)
            {
                $randomString .= array_rand($uppercase);
            }

            else if($i == 1)
            {
                $randomString .= array_rand($lowercase);
            }

            else if($i == 2)
            {
                $randomString .= array_rand($numbers);
            }

            else
            {
                $randomString .= array_rand($all);
            }
        }

        return $randomString;
    }
}
