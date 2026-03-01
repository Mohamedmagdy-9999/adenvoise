<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $lang = $request->header('lang', 'ar'); // default ar

        if (! in_array($lang, ['ar','en'])) {
            $lang = 'ar';
        }

        App::setLocale($lang);

        return $next($request);
    }
}