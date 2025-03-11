<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use App\Models\SuperAdminRole;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Password;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Notifications\Admin\ResetPasswordNotification;

use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{
    //
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
           
        ], [
            'email.required' => 'Email is required.',
            'password.required' => 'Password is required.',
            
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' =>$validator->errors(),
                
            ], 422);
        }

        // Find the user by email and role
        $user = User::where('email', $request->email)->first();
        
        // Check if the user exists and the password is correct
        if ($user && Hash::check($request->password, $user->password)) {
    
            // Check if the user is active
            if ($user->active == "1") {
              $check_role=SuperAdminRole::select('id','role')->where('id',$user->role_id)->where('role',$user->role)->first();
              if($check_role)
              {
                $update_slogin=User::find($user->id);
    
                $update_slogin->last_login=Carbon::now();
                $update_slogin->save();
                $credentials = $request->only('email', 'password');
                $permissions = $this->getUserPermissions($user);
              
                $customClaims = [
                    'role_id'    => $user->role_id,
                    'company_id' => $user->company_id,
                    'permissions' => $permissions,
                ];
                // Attempt to log the user in and generate the token
                if ($token = JWTAuth::claims($customClaims)->attempt($credentials)) 
                {
                    // Return success response with token and user data
                    return response()->json([
                        "status" => true,
                        "message" => "User Successfully Logged in",
                        "token" => $token,
                        "data" => $user,  // You can return the user directly
                        "permissions" => $permissions,
                    ], 200);
               }
            }else{
                return response()->json([
                    "status" => false,
                    "message" => "Invalid LOgin.",
                    "data" => []
                ]);  // HTTP 403 Forbidden
            }
            } else {
                // If the user is not active, return an error
                return response()->json([
                    "status" => false,
                    "message" => "You're currently disabled, contact to admin.",
                    "data" => []
                ], 403);  // HTTP 403 Forbidden
            }
        } else {
            // If the credentials are invalid, return an error
            return response()->json([
                "status" => false,
                "message" => "Invalid credentials, please try again.",
                "data" => []
            ], 401);  // HTTP 401 Unauthorized
        }
    }

    protected function getUserPermissions(User $user)
    {
        // Check if the user has a role

        if (empty($user->company_id)) {
            // => Super Admin
            $roleRecord = RolePermission::select('id', 'menu', 'view', 'add', 'edit', 'delete')->where('role_id', $user->role_id)->whereNull('company_id')->get();
            return $roleRecord;
        }
        
     
    }
    public function forgot_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',    
        ], [
            'email.required' => 'Email is required.',    
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
                
            ], 422);
        }

        $user_s = User::where('email', $request->email)->where('active', '1')->first();
        if ($user_s) 
        {
            $check_role=SuperAdminRole::select('id','role')->where('id',$user_s->role_id)->where('role',$user_s->role)->first();
            if($check_role)
            {
            $token = Password::getRepository()->create($user_s);
          
            // Send the custom notification
            $user_s->notify(new ResetPasswordNotification($token));
           
            $data=array(
                'reset_pass_token'=>$token,
                'email'=>$request->email
                );
            return response()->json(['status' => true, 'message' => 'Password reset link sent.','data'=>$data], 200);
            }else{
                return response()->json([
                    "status" => false,
                    "message" => "Invalid LOgin.",
                    "data" => []
                ]);  // HTTP 403 Forbidden
            }
        }else{
            return response()->json(['status' => false, 'message' => 'Details Wrong.']);

        }
    }

    public function reset_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'token'=>'required',
            'email' => 'required|email',
            'password' => 'required',
           
       ], [
           'email.required' => 'Email is required.',
           'password.required' => 'Password is required',
          
       ]);
       if ($validator->fails()) {
           return response()->json([
               'status' => false,
               'message' =>  $validator->errors(),
            
           ], 422);
       }
         // Find the user by email
         $user = User::select('id','password')->where('email', $request->email)->first();

         // Check if the user exists
         if ($user) {
            $check_role=SuperAdminRole::select('id','role')->where('id',$user->role_id)->where('role',$user->role)->first();
            if($check_role)
            {
            // throw ValidationException::withMessages(['email' => 'This email address or Unique Id does not exist.']);
            $user->password = bcrypt($request->password); // Your custom way of hashing or processing
            $user->save();
            return response()->json(['status' => 'Password has been reset successfully.'], 200);
            }else{
                return response()->json([
                    "status" => false,
                    "message" => "Invalid LOgin.",
                    "data" => []
                ]);  // HTTP 403 Forbidden
            }
         }else{
            return response()->json(['status' => false, 'message' => 'This email address does not exist.']);
         }
      
    }
    public function profile()
    {
        $user = JWTAuth::user();

        // Check if user is null (if token is invalid or expired)
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized, invalid token.',
            ], 401);
        }

        // Return the authenticated user data
        return response()->json([
            'status' => true,
            'message' => 'Profile fetched successfully.',
            'data' => $user,
        ], 200);
    }
}
