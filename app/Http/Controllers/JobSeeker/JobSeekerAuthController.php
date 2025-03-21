<?php

namespace App\Http\Controllers\JobSeeker;

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

class JobSeekerAuthController extends Controller
{
    //

    public function login(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'oauth_provider' => 'required',

        ], [
            'email.required' => 'Email is required.',
            'password.required' => 'Password is required.',
            'oauth_provider.required' => 'OAuth Provider is required.'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

        // Find the user by email and role
        $user = User::where('email', $request->email)->where('role', 'job_seeker')->first();

        // Check if the user exists and the password is correct
        if ($user && Hash::check($request->password, $user->password)) {

            // Check if the user is active
            if ($user->active == "1") {
                // Delete any existing tokens (if necessary)
                $user->oauth_provider = $request->oauth_provider;
                $user->last_login = Carbon::now();
                $user->save();
                $credentials = $request->only('email', 'password');

                // Attempt to log the user in and generate the token
                if ($token = JWTAuth::attempt($credentials)) {

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

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'mobile' => 'required',
            'name' => 'required',

            //'first_name' => 'required',
            // 'last_name' => 'required',

        ], [
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'mobile.required' => 'Mobile Number is required.',
            'password.required' => 'Password is required.'

            //  'first_name.required' => 'First name is required.',
            //'last_name.required' => 'Last name is required.',

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $check_user = User::where('email', $request->email)
            ->count();

        $check_user_mobile = User::where('mobile', $request->mobile)
            ->count();
        if ($check_user > 0) {
            return response()->json([
                'status' => false,
                'message' => 'User email already Signup.',
            ]);
        } else if ($check_user_mobile > 0) {
            return response()->json([
                'status' => false,
                'message' => 'User mobile already Signup.',
            ]);
        } else {

            $oemuser = new User();
            $oemuser->name = $request->name;
            $oemuser->bash_id = Str::uuid();
            $oemuser->mobile = $request->mobile;
            // $oemuser->first_name=$request->first_name;
            // $oemuser->last_name=$request->last_name;

            $oemuser->email = $request->email;
            $oemuser->role = 'job_seeker';
            $oemuser->role_id = '1';
            $oemuser->password = bcrypt($request->password);

            $oemuser->save();


            return response()->json([
                'status' => true,
                'message' => 'success'

            ], 200);
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
