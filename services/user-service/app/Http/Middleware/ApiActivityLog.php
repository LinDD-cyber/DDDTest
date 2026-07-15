<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Domains\User\Models\User;
use Exception;

class ApiActivityLog
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        try {
            if ($this->shouldLog($request)) {
                $this->logRequest($request, $response);
            }
        } catch (Exception $e) {
            // Prevent logging errors from breaking the API response
            report($e);
        }

        return $response;
    }

    /**
     * Determine if the request should be logged.
     */
    protected function shouldLog(Request $request): bool
    {
        // 1. Check if API logging is globally enabled
        if (!config('api_log.enabled', true)) {
            return false;
        }

        // 2. Check if HTTP method is in exclusion list
        if (in_array(strtoupper($request->method()), config('api_log.exclude_methods', []))) {
            return false;
        }

        // 3. Check if Route Path matches any exclusion pattern
        $path = $request->path();
        foreach (config('api_log.exclude_routes', []) as $excludePath) {
            if ($path === $excludePath || str_starts_with($path, rtrim($excludePath, '/') . '/')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Log the API request and response.
     */
    protected function logRequest(Request $request, Response $response): void
    {
        // Resolve causer (operator)
        $user = $request->user();
        if (!$user) {
            $userId = $request->header('X-User-Id') ?? $request->input('auth_user_id');
            if ($userId) {
                $user = User::find($userId);
            }
        }

        // Prepare request parameters, excluding sensitive data
        $payload = $request->except(['password', 'password_confirmation', 'token', 'access_token']);

        // Determine feature name
        $feature = $this->getFeatureName($request);

        // Perform activity logging using Spatie's helper
        activity('api_access')
            ->causedBy($user)
            ->withProperties([
                'ip' => $request->ip(),
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'path' => $request->getPathInfo(),
                'feature' => $feature,
                'action' => $request->route() ? $request->route()->getActionName() : 'N/A',
                'payload' => $payload,
                'status_code' => $response->getStatusCode(),
            ])
            ->log("API: [{$request->method()}] {$request->getPathInfo()} - {$feature}");
    }

    /**
     * Translate controller actions into readable features.
     */
    protected function getFeatureName(Request $request): string
    {
        $route = $request->route();
        if (!$route) {
            return '未知路由';
        }

        $action = $route->getActionName();
        if (empty($action)) {
            return '未知操作';
        }

        $mappings = [
            'App\Http\Controllers\Api\AuthController@register' => '用戶註冊',
            'App\Http\Controllers\Api\AuthController@login' => '用戶登入',
            'App\Http\Controllers\Api\AuthController@backendLogin' => '管理員後台登入',
            'App\Http\Controllers\Api\AuthController@logout' => '用戶登出',
            'App\Http\Controllers\Api\AuthController@me' => '獲取當前登入者資訊',
            'App\Http\Controllers\Api\AuthController@verify' => '閘道Token驗證',
            'App\Http\Controllers\Api\UserManagementController@getFrontendUsers' => '查詢前台用戶列表',
            'App\Http\Controllers\Api\UserManagementController@getBackendUsers' => '查詢後台用戶列表',
            'App\Http\Controllers\Api\UserManagementController@syncUserVenues' => '同步用戶場館資料權限',
            'App\Http\Controllers\Api\UserManagementController@upgradeToCoach' => '升級用戶為教練',
            'App\Http\Controllers\Api\UserManagementController@createBackendUser' => '新增後台管理員/員工',
            'App\Http\Controllers\Api\UserManagementController@updateBackendUser' => '修改後台管理員/員工',
            'App\Http\Controllers\Api\UserManagementController@deleteBackendUser' => '刪除後台管理員/員工',
            'App\Http\Controllers\Api\VenueController@store' => '新增場館',
            'App\Http\Controllers\UserController@index' => '查詢用戶列表(舊版/模擬)',
            'App\Http\Controllers\UserController@show' => '查詢單一用戶(舊版/模擬)',
            'App\Http\Controllers\UserController@store' => '建立用戶(舊版/模擬)',
        ];

        foreach ($mappings as $key => $name) {
            if (str_contains($action, $key)) {
                return $name;
            }
        }

        return $route->uri();
    }
}
