<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Shared\Traits\ApiResponseTool;

class BackendAccess
{
    use ApiResponseTool;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->roles()->count() === 0) {
            return $this->generateApiResponse(403, config('apiResponse.status.fail'), [config('apiMessage.direct.insufficient_permissions')]);
        }

        return $next($request);
    }
}
