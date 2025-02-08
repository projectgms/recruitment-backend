<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;


use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
        $user = User::where('email', $request->email)->where('role', 'admin')->first();
        
        // Check if the user exists and the password is correct
        if ($user && Hash::check($request->password, $user->password)) {
    
            // Check if the user is active
            if ($user->active == "1") {
                // Delete any existing tokens (if necessary)
                
                //$user->tokens()->where('name', 'mytoken')->delete();
                // Generate a new Sanctum token
               
                $update_slogin=User::find($user->id);
    
                $update_slogin->last_login=Carbon::now();
                $update_slogin->save();
                $credentials = $request->only('email', 'password');

                // Attempt to log the user in and generate the token
                if ($token = JWTAuth::attempt($credentials)) 
                {
                    // Return success response with token and user data
                    return response()->json([
                        "status" => true,
                        "message" => "User Successfully Logged in",
                        "token" => $token,
                        "data" => $user  // You can return the user directly
                    ], 200);
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
}
