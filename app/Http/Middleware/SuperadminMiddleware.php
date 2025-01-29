<?php
namespace App\Http\Middleware;


use Illuminate\Support\Facades\Auth;
use Closure;
use Illuminate\Http\Request;

class SuperadminMiddleware
{
    public function handle($request, Closure $next)
    {   // Check if the authenticated user has the 'superadmin' role
        $user = Auth::guard('superadmin')->user();
       
        if ($user && $user->tokenCan('role:super_admin')) {
            return $next($request);
        }

        // If not, return an unauthorized response
        return response()->json(['error' => 'Unauthorized access, superadmin only.'], 403);

    }
}