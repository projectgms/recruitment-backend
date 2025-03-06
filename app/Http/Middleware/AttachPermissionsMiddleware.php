<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class AttachPermissionsMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Try to parse the token and get its payload
        try {
            $payload = JWTAuth::parseToken()->getPayload();
            $permissions = $payload->get('permissions'); // Extract permissions
    
            if ($permissions) {
                $request->attributes->add(['permissions' => $permissions]);
                Log::info('Permissions attached to request: ', $permissions);  // Debugging line
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Token invalid or not provided'], 401);
        }
    
        return $next($request);
    }
}
