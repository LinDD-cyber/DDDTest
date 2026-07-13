<?php
 
namespace Shared\Http\Middleware;
 
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
 
class HeaderAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->header('X-User-Id');
        $userRoles = $request->header('X-User-Roles');
        $userVenues = $request->header('X-User-Venues');
 
        if (!$userId) {
            return response()->json([
                'status' => -1,
                'messages' => ['請先登入後再操作。'],
                'error' => [
                    'code' => 'unauthorized',
                    'fields' => []
                ]
            ], 401);
        }
 
        // Merge into request parameters so controllers can access it
        $request->merge([
            'auth_user_id' => (int) $userId,
            'auth_user_roles' => $userRoles ? explode(',', $userRoles) : [],
            'auth_user_venues' => $userVenues ? explode(',', $userVenues) : [],
        ]);
 
        return $next($request);
    }
}
