<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Traits\ApiResponse;

class User extends Authenticatable implements JWTSubject
{
    use Notifiable, ApiResponse;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'Forbidden',
        'authenticated',
        'verification_code',
        'verification_expires_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'verification_code',
    ];

    protected $casts = [
        'email_verified_at'       => 'datetime',
        'verification_expires_at' => 'datetime',
        'Forbidden'               => 'boolean',
        'authenticated'           => 'boolean',
    ];

    /**
     * Register User
     */
    public static function registerUser($request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return (new static)->validationError($validator->errors());
        }

        $user = self::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'role'          => 1,
            'Forbidden'     => false,
            'authenticated' => false,
        ]);

        $user->sendEmailVerificationCode();

        return (new static)->success([], 'تم إنشاء الحساب بنجاح، يرجى التحقق من بريدك الإلكتروني');
    }

    /**
     * Login User
     */
    public static function loginUser($request)
    {
        $credentials = $request->only('email', 'password');

        if (! $token = Auth::guard('api')->attempt($credentials)) {
            return (new static)->error('بيانات تسجيل الدخول غير صحيحة', 401);
        }

        $user = Auth::guard('api')->user();

        if ($user->Forbidden) {
            return (new static)->error('هذا الحساب محظور من قبل الإدارة', 403);
        }

        if (! $user->authenticated) {
            $user->sendEmailVerificationCode();
        }

        return (new static)->success([
            'access_token' => $token,
            'token_type'   => 'bearer',
            'expires_in'   => Auth::guard('api')->factory()->getTTL() * 60,
            'user'         => $user,
        ], 'تم تسجيل الدخول بنجاح');
    }


    /**
     * Logout User
     */
    public static function logoutUser()
    {
        Auth::guard('api')->logout();
        return (new static)->success([], 'تم تسجيل الخروج بنجاح');
    }

    /**
     * Verify Email
     */
    public static function verifyUserEmail($request)
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        $user = Auth::guard('api')->user();

        if ($user->authenticated) {
            return (new static)->error('تم تفعيل البريد مسبقًا', 400);
        }

        if (
            $user->verification_code !== $request->code ||
            $user->verification_expires_at < Carbon::now()
        ) {
            return (new static)->error('رمز التحقق غير صالح أو منتهي الصلاحية', 400);
        }

        $user->authenticated       = true;
        $user->email_verified_at   = now();
        $user->verification_code   = null;
        $user->verification_expires_at = null;
        $user->save();

        return (new static)->success([], 'تم تفعيل البريد الإلكتروني بنجاح');
    }

    /**
     * Send password reset code
     */
    public static function forgotPasswordUser($request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        $user = self::where('email', $request->email)->first();

        $user->verification_code = Str::random(6);
        $user->verification_expires_at = Carbon::now()->addMinutes(10);
        $user->save();

        $user->notify(new \App\Notifications\ResetPasswordNotification($user->verification_code));

        return (new static)->success([], 'تم إرسال رمز إعادة تعيين كلمة المرور إلى بريدك الإلكتروني');
    }

    /**
     * Verify reset code
     */
    public static function verifyResetCodeUser($request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'code'  => 'required|string',
        ]);

        $user = self::where('email', $request->email)->first();

        if (
            $user->verification_code !== $request->code ||
            $user->verification_expires_at < Carbon::now()
        ) {
            return (new static)->error('رمز إعادة التعيين غير صالح أو منتهي الصلاحية', 400);
        }

        return (new static)->success([], 'رمز إعادة التعيين صالح، يمكنك الآن تغيير كلمة المرور');
    }

    /**
     * Reset password
     */
    public static function resetPasswordUser($request)
    {
        $request->validate([
            'email'    => 'required|email|exists:users,email',
            'code'     => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = self::where('email', $request->email)->first();

        if (
            $user->verification_code !== $request->code ||
            $user->verification_expires_at < Carbon::now()
        ) {
            return (new static)->error('رمز إعادة التعيين غير صالح أو منتهي الصلاحية', 400);
        }

        $user->password = Hash::make($request->password);
        $user->verification_code = null;
        $user->verification_expires_at = null;
        $user->save();

        return (new static)->success([], 'تم تغيير كلمة المرور بنجاح');
    }


    /**
     * Send email verification code
     */
    public function sendEmailVerificationCode()
    {
        $this->verification_code = \Illuminate\Support\Str::random(6);
        $this->verification_expires_at = \Carbon\Carbon::now()->addMinutes(10);
        $this->save();

        // إرسال الكود عبر البريد
        $this->notify(new \App\Notifications\VerifyEmailNotification($this->verification_code));
    }

    /**
     * JWT Methods
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }
}
