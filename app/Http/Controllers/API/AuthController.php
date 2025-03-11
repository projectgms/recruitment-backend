<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\MenuList;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;

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
    
}