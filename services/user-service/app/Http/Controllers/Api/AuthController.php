<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Domains\User\Services\AuthService;
use Shared\Traits\MapsServiceResult;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    use MapsServiceResult;

    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Register a new user (Student)
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        return $this->respond(
            $this->authService->register($request->validated())
        );
    }

    /**
     * Login and get token
     */
    public function login(LoginRequest $request): JsonResponse
    {
        return $this->respond(
            $this->authService->login($request->validated(), $request)
        );
    }

    /**
     * Send One-Time Password (OTP)
     */
    public function sendOtp(Request $request): JsonResponse
    {
        $request->validate([
            'account' => 'required|string',
        ]);

        return $this->respond(
            $this->authService->sendOtp($request->input('account'))
        );
    }

    /**
     * Backend Login (Must have roles)
     */
    public function backendLogin(LoginRequest $request): JsonResponse
    {
        return $this->respond(
            $this->authService->backendLogin($request->validated(), $request)
        );
    }

    /**
     * Get the authenticated user's details
     */
    public function me(Request $request): JsonResponse
    {
        return $this->respond(
            $this->authService->getMe($request->user())
        );
    }

    /**
     * Logout user and revoke token
     */
    public function logout(Request $request): JsonResponse
    {
        return $this->respond(
            $this->authService->logout($request->user())
        );
    }

    /**
     * Internal endpoint for gateway to verify token and extract user details
     */
    public function verify(Request $request)
    {
        $user = $request->user();
        
        // Load roles and venues
        $roles = $user->roles->pluck('name')->toArray();
        $venues = $user->venues->pluck('id')->toArray();

        return response('', 200)
            ->header('X-User-Id', $user->id)
            ->header('X-User-Roles', implode(',', $roles))
            ->header('X-User-Venues', implode(',', $venues));
    }
}
