<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\EmailSubscription;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct()
    {
     
    }
  
    public function welcome()
    {
        $uuid = Str::uuid();
        return response()->json(["status"=>$uuid,
                                "message"=>"Welcome to Recruitment APP"]);
    }

    public function logout(Request $request)
    {
        try {
            // Get the current user token
            $token = JWTAuth::getToken();
            
            // Invalidate the token, i.e., log the user out
            JWTAuth::invalidate($token);
            
            return response()->json([
                'status' => true,
                'message' => 'User successfully logged out',
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to logout, please try again',
            ], 500);
        }
    }
    
     public function email_subscription(Request $request)
    {
       
        $validator = Validator::make($request->all(), [
            'email' => 'required'
           ,
        ], [
            'email.required' => 'Email is required.',
         
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $check_email=EmailSubscription::where('email',$request->email)->count();
        if($check_email>0)
        {
            return response()->json([
                'status' => true,
                'message' => 'Thank you for subscription...We will get back to you soon.',
            ], 200);

        }else{
            $subscription=new EmailSubscription();
            $subscription->email=$request->email;
            $subscription->save();
return response()->json([
                'status' => true,
                'message' => 'Thank you for subscription...We will get back to you soon.',
            ], 200);
          
        }
    }
    
    
}