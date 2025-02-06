<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;


use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

use Illuminate\Support\Facades\Validator;

class JobSeekerAuthController extends Controller
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
        $user = User::where('email', $request->email)->where('role', 'job_seeker')->first();
        
        // Check if the user exists and the password is correct
        if ($user && Hash::check($request->password, $user->password)) {
    
            // Check if the user is active
            if ($user->active == "1") {
                // Delete any existing tokens (if necessary)
                
                //$user->tokens()->where('name', 'mytoken')->delete();
                // Generate a new Sanctum token
                $token = $user->createToken("mytoken",['role:job_seeker'])->plainTextToken;
               
                $update_slogin=User::find($user->id);
    
                $update_slogin->last_login=Carbon::now();
                $update_slogin->save();
                // Return success response with token and user data
                return response()->json([
                    "status" => true,
                    "message" => "User Successfully Logged in",
                    "token" => $token,
                    "data" => $user  // You can return the user directly
                ], 200);
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

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'c_password' => 'required|same:password',
            
            'first_name' => 'required',
            'last_name' => 'required',
          
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'password.required' => 'Password is required.',
            'c_password.required' => 'Confirm password is required.',
            'c_password.same' => 'Confirm password must match the password.',
           
            'first_name.required' => 'First name is required.',
            'last_name.required' => 'Last name is required.',
            
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
               
            ], 422);
        }
        $check_user=User::where('email',$request->email)
        ->count();
       

        if( $check_user>0)
        {
            return response()->json([
                'status' => false,
                'message' => 'Already User Email  Added .',
            ]);
        }else if($request->password!=$request->c_password){
            return response()->json([
                'status' => false,
                'message' => 'Password and Confirm Password not match.',
            ]);
        }else{
           
            $oemuser                = new User();
           
            $oemuser->first_name=$request->first_name;
            $oemuser->last_name=$request->last_name;
          
            $oemuser->email=$request->email;
            $oemuser->role='job_seeker';
            $oemuser->password=bcrypt($request->password);
          
            $oemuser->save();
         

            return response()->json([
                'status' => true,
                'message' => 'success'
                
            ], 200);
        }
    }
}
