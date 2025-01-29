<?php

namespace App\Http\Middleware;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Closure;

class Authenticate extends Middleware
{
   

    public function handle($request, Closure $next, ...$guards)
    {
        // Get the bearer token from the request
       //Check if the user is authenticated for any of the guards
       if (empty($guards)) {
        $guards = ['api']; // Default to API guard
    }

    foreach ($guards as $guard) {
        if (Auth::guard($guard)->check()) {
            // Log the active guard and user information
            // Log::info('Active Guard:', [
            //     'guard' => $guard,
            //     'user' => Auth::guard($guard)->user(),
            // ]);
            return $next($request);
        }
    }

    // If no user is authenticated for any guard, return unauthorized response
    return response()->json(['error' => 'Unauthorized'], 401);

    }
}