<?php

namespace App\Http\Middleware;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Closure;

class Authenticate extends Middleware
{
   

    public function handle($request, Closure $next, ...$guards)
    {
        if (empty($guards)) {
            $guards = ['api']; // Default to API guard
        }

        foreach ($guards as $guard) {
            // Check if the guard is the API and the user is authenticated
            if ($guard === 'api') {
                // Attempt to authenticate using JWT
                try {
                    // If JWT is valid, user will be authenticated, otherwise an exception will be thrown
                    $user = JWTAuth::parseToken()->authenticate();
                    Log::warning('auth api');
                    if ($user) {
                        // Proceed to the next middleware or controller action
                        return $next($request);
                    } else {
                        // Token is invalid, return unauthorized response
                        return response()->json(['status'=>'false','message' => 'Unauthorized'], 401);
                    }
                } catch (\Exception $e) {
                    // If there is an error (e.g., token missing, expired), return unauthorized response
                    return response()->json(['status'=>'false','message' => 'Unauthorized'], 401);
                }
            }

            // Check other guards here if needed
            if (Auth::guard($guard)->check()) {
                return $next($request);
            }
        }

        // If no user is authenticated for any guard, return unauthorized response
        return response()->json(['status'=>'false','message' => 'Unauthorized'], 401);
    }
}