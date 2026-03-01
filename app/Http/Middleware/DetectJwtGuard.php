<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class DetectJwtGuard
{
    public function handle($request, Closure $next)
    {
        $guards = [
            'api_admins',
            'api_users',
            'api_citizens',
        ];

        foreach ($guards as $guard) {
            try {
                if (Auth::guard($guard)->check()) {
                    // خزّن اسم الجارد
                    $request->attributes->set('auth_guard', $guard);
                    return $next($request);
                }
            } catch (\Exception $e) {
                // تجاهل الخطأ وجرب اللي بعده
            }
        }

        return response()->json([
            'status' => false,
            'message' => 'Unauthorized',
        ], 401);
    }
}