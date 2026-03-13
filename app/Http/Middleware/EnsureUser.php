<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureUser
{
    public function handle(Request $request, Closure $next)
    {

        if (! Auth::guard('api_users')->check()) {
            return response()->json([
                'status' => false,
                'message' => 'غير مصرح لك بالدخول',
            ], 401);
        }

        return $next($request);
    }
}