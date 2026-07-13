<?php

namespace App\Domains\User\Services;

use App\Domains\User\Models\User;
use App\Domains\User\Models\UserLoginLog;
use Shared\Support\ServiceResult;
use App\Support\ApiMessageBuilder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AuthService
{
    protected UserService $userService;
    protected ApiMessageBuilder $messageBuilder;

    public function __construct(UserService $userService, ApiMessageBuilder $messageBuilder)
    {
        $this->userService = $userService;
        $this->messageBuilder = $messageBuilder;
    }

    /**
     * Handle user registration (defaults to student)
     */
    public function register(array $data): ServiceResult
    {
        try {
            $account = $data['account'];
            $isEmail = filter_var($account, FILTER_VALIDATE_EMAIL);

            $user = $this->userService->createStudent([
                'account' => $account,
                'password' => $data['password'],
                'email' => $isEmail ? $account : null,
                'phone' => !$isEmail ? $account : null,
            ]);

            return ServiceResult::success(
                [$this->messageBuilder->build('create', 'user', 'success')],
                ['user' => $user]
            );
        } catch (\Exception $e) {
            return ServiceResult::fail(
                [$this->messageBuilder->build('create', 'user', 'fail'), $e->getMessage()],
                ['reason' => 'server_error']
            );
        }
    }

    /**
     * Handle user login
     */
    public function login(array $credentials, Request $request): ServiceResult
    {
        $account = $credentials['account'];
        $isEmail = filter_var($account, FILTER_VALIDATE_EMAIL);
        $isPhone = preg_match('/^09\d{8}$/', $account);

        $user = User::where(function($query) use ($account, $isEmail, $isPhone) {
            $query->where('account', $account);
            if ($isEmail) {
                $query->orWhere('email', $account);
            }
            if ($isPhone) {
                $query->orWhere('phone', $account);
            }
        })->first();

        if (!$user) {
            return ServiceResult::fail(
                [$this->messageBuilder->direct('invalid_credentials')],
                ['reason' => 'invalid_credentials']
            );
        }

        if ($user->status !== 1) {
            return ServiceResult::fail(
                [$this->messageBuilder->direct('account_disabled')],
                ['reason' => 'account_disabled']
            );
        }

        // Authentication checks (verification code vs password)
        if (isset($credentials['code'])) {
            $cachedOtp = \Illuminate\Support\Facades\Cache::get('otp_' . $account);

            if (!$cachedOtp || $cachedOtp !== $credentials['code']) {
                return ServiceResult::fail(
                    ['驗證碼錯誤或已過期。'],
                    ['reason' => 'invalid_otp']
                );
            }

            \Illuminate\Support\Facades\Cache::forget('otp_' . $account);
        } else {
            if (!Hash::check($credentials['password'], $user->password)) {
                return ServiceResult::fail(
                    [$this->messageBuilder->direct('invalid_credentials')],
                    ['reason' => 'invalid_credentials']
                );
            }
        }

        return $this->processSuccessfulLogin($user, $request);
    }

    /**
     * Send One-Time Password (OTP) to user email or phone
     */
    public function sendOtp(string $account): ServiceResult
    {
        try {
            $isEmail = filter_var($account, FILTER_VALIDATE_EMAIL);
            $isPhone = preg_match('/^09\d{8}$/', $account);

            if (!$isEmail && !$isPhone) {
                return ServiceResult::fail(
                    ['帳號格式錯誤。'],
                    ['reason' => 'invalid_account_format']
                );
            }

            // Generate 6-digit random code
            $otp = (string) mt_rand(100000, 999999);

            // Cache OTP for 5 minutes
            \Illuminate\Support\Facades\Cache::put('otp_' . $account, $otp, now()->addMinutes(5));

            // In production, integrate actual Mail / SMS gateway here
            if ($isEmail) {
                // \Illuminate\Support\Facades\Mail::to($account)->send(new \App\Mail\OtpMail($otp));
                \Illuminate\Support\Facades\Log::info("Email OTP for {$account}: {$otp}");
            } else {
                // $this->smsService->send($account, "您的登入驗證碼為：{$otp}");
                \Illuminate\Support\Facades\Log::info("SMS OTP for {$account}: {$otp}");
            }

            return ServiceResult::success(
                ['驗證碼已成功送出。'],
                ['otp_preview' => config('app.debug') ? $otp : null]
            );
        } catch (\Exception $e) {
            return ServiceResult::fail(
                ['驗證碼傳送失敗。', $e->getMessage()],
                ['reason' => 'server_error']
            );
        }
    }

    /**
     * Handle backend user login (must have roles)
     */
    public function backendLogin(array $credentials, Request $request): ServiceResult
    {
        $user = User::with('roles')->where('account', $credentials['account'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return ServiceResult::fail(
                [$this->messageBuilder->direct('invalid_credentials')],
                ['reason' => 'invalid_credentials']
            );
        }

        if ($user->status !== 1) {
            return ServiceResult::fail(
                [$this->messageBuilder->direct('account_disabled')],
                ['reason' => 'account_disabled']
            );
        }

        // Check if user has any backend roles
        if ($user->roles->isEmpty()) {
            return ServiceResult::fail(
                [$this->messageBuilder->direct('insufficient_permissions')],
                ['reason' => 'insufficient_permissions']
            );
        }

        return $this->processSuccessfulLogin($user, $request);
    }

    /**
     * Common logic to issue token and record login log
     */
    protected function processSuccessfulLogin(User $user, Request $request): ServiceResult
    {
        // Issue token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Update last login
        $user->update(['last_login_at' => Carbon::now()]);

        // Record login log
        UserLoginLog::create([
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'device' => $request->userAgent(),
            'platform' => null,
            'browser' => null,
            'login_at' => Carbon::now(),
        ]);

        return ServiceResult::success(
            [$this->messageBuilder->direct('login_success')],
            [
                'access_token' => $token,
                'user' => $user->only(['id', 'uuid', 'account', 'email', 'status', 'last_login_at'])
            ]
        );
    }

    /**
     * Handle user logout
     */
    public function logout(User $user): ServiceResult
    {
        // Revoke current token
        $user->currentAccessToken()->delete();

        // Update the most recent login log with logout time
        $latestLog = UserLoginLog::where('user_id', $user->id)
            ->whereNull('logout_at')
            ->orderBy('login_at', 'desc')
            ->first();

        if ($latestLog) {
            $latestLog->update(['logout_at' => Carbon::now()]);
        }

        return ServiceResult::success(
            [$this->messageBuilder->direct('logout_success')],
            []
        );
    }

    /**
     * Get authenticated user details with related layers
     */
    public function getMe(User $user): ServiceResult
    {
        $user->load([
            'profile',
            'coachProfile',
            'frontIdentities',
            'venues',
            'roles', // Spatie backend roles
            'permissions' // Spatie direct permissions
        ]);

        return ServiceResult::success(
            [$this->messageBuilder->build('query', 'user', 'success')],
            ['user' => $user]
        );
    }
}
