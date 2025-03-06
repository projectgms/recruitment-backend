<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Models\SuperAdminRole;
use App\Models\RecruiterRole;
use Illuminate\Support\Facades\Log;

class DynamicRoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
   
    public function handle(Request $request, Closure $next, ...$roles)
    {
        try {
            // 1. Parse and validate the JWT token from the Authorization header
            $payload = JWTAuth::parseToken()->getPayload();

            $roleId    = $payload->get('role_id');    // e.g. 1
            $companyId = $payload->get('company_id'); // e.g. null or integer

            // 3. If we want to log these claims for debugging
          

            // 4. Check if role_id is valid
            if ($roleId === null) {
                return response()->json(['status'=>'false','message'=> 'Role ID is missing in the token'], 401);
            }

            // 5. Determine if user is a Super Admin (company_id is null) or Recruiter
            if (is_null($companyId)) {
                // => Super Admin
                $roleRecord = SuperAdminRole::find($roleId);
            } else {
                // => Recruiter
                $roleRecord = RecruiterRole::find($roleId);
            }

            // 5. If the user's role record doesn't exist, deny access
            if (!$roleRecord) {
                return response()->json(['status'=>'false','message' => 'Unauthorized Access - Role not found'], 403);
             
            }
           
            $allowedRoles = $this->getAllowedRoles($companyId,$roleId); // Dynamic fetching of allowed roles

            // 6. Check if the user's role is in the allowed roles
            if (!in_array($roleRecord->role, $allowedRoles)) {
                return response()->json(['status'=>'false','message'=> 'Unauthorized role'], 403);
            }
            // 6. Check if the user's role name is in the allowed roles for this route
           
        } catch (\Exception $e) {
            // Token is invalid, expired, or missing
        
            return response()->json(['status'=>'false','message' => 'Token invalid or not provided'], 401);
        }

        // 7. All good, proceed to the next middleware / controller
        return $next($request);
    }

    protected function getAllowedRoles($companyId,$roleId)
    {
      
        if (is_null($companyId)) {
            // => Super Admin
            $roleRecord = SuperAdminRole::find($roleId);
            return [$roleRecord->role];
        } else {
            // => Recruiter
            $roleRecord = RecruiterRole::find($roleId);
             return [$roleRecord->role];
        }
       
    }
}


