<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next)
    {

        if (! Auth::guard('api_admins')->check()) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بالدخول',
            ], 401);
        }

        return $next($request);
    }
}