<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Company;
use App\Models\Recruiter;
use App\Models\RecruiterRole;
use App\Models\RolePermission;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

use Illuminate\Support\Facades\Validator;

class RecruiterAuthController extends Controller
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
            'oauth_provider' => 'Oauth Provide is required.'

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

        // Find the user by email and role
        $user = User::select('users.*')->where('users.email', $request->email)->first();

        // Check if the user exists and the password is correct
        if ($user && Hash::check($request->password, $user->password)) {

            // Check if the user is active
            if ($user->active == "1") {
                $check_role=RecruiterRole::select('id','role')->where('id',$user->role_id)->where('role',$user->role)->where('active','1')->first();
                if($check_role)
                {
                    $isFirstLogin = is_null($user->last_login);

                $user->oauth_provider = $request->oauth_provider;
                $user->last_login = Carbon::now();
                $user->ip_address=$request->getClientIp();
                $user->save();
                // Return success response with token and user data
                $credentials = $request->only('email', 'password');
                
                      $permissions = $this->getUserPermissions($user);
              
                $customClaims = [
                    'role_id'    => $user->role_id,
                    'company_id' => $user->company_id,
                    'permissions' => $permissions,
                ];

                // Attempt to log the user in and generate the token
                if ($token = JWTAuth::claims($customClaims)->attempt($credentials)) {
$user = $user->toArray();
            $user['first_login'] = $isFirstLogin;

                    return response()->json([
                        "status" => true,
                        "message" => "User Successfully Logged in",
                        "token" => $token,
                        "data" => $user, // You can return the user directly
                       
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

        if ($user->company_id) {
            // => Super Admin
            $roleRecord = RolePermission::select('id', 'menu', 'view', 'add', 'edit', 'delete')->where('role_id', $user->role_id)->where('company_id', $user->company_id)->get();
            return $roleRecord;
        }
        
     
    }
    public function register(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
            'name' => 'required',
            'company' => 'required',
            'mobile' => 'required'
        ], [
            'email.required' => 'Email is required.',
            'email.email' => 'Email must be a valid email address.',
            'password.required' => 'Password is required.',
            'name.required' => 'Name is required.',
            'company.required' => 'Company Name is required',
            'mobile.required' => 'Mobile Number is required'

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $check_user = User::where('email', $request->email)
            ->count();
        $check_company=Company::where('name',$request->company)->count();
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
        } else if($check_company>0)
        {
               return response()->json([
                'status' => false,
                'message' => 'Company Name already Added.',
            ]);
            }else {

            $get_role=RecruiterRole::select('id','role')->where('parent_id','0')->first();
            $oemuser = new User();
            $oemuser->name = $request->name;
            $oemuser->bash_id = Str::uuid();
            // $oemuser->first_name=$request->first_name;
            //$oemuser->last_name=$request->last_name;

            $oemuser->email = $request->email;
            $oemuser->role = $get_role->role;
            $oemuser->role_id = $get_role->id;
            $oemuser->password = bcrypt($request->password);
            $oemuser->mobile = $request->mobile;

            $oemuser->save();

            $company = new Company();
            $company->bash_id = Str::uuid();
            $company->user_id = $oemuser->id; // Assign the new user ID
            $company->name = $request->company; // Assign the company name
            $company->save(); // Save the company record

            $oemuser->company_id = $company->id; // Set the company's ID in the user record
            $oemuser->save(); // Update the user record with the company_id
    
            $permission_data=[
                [
                    "id" => 1,
                    "menu" => "dashboard",
                    "view" => 1,
                    "add" => 1,
                    "edit" => 1,
                    "delete" => 1
                ],
                [
                    "id" => 2,
                    "menu" => "company profile",
                    "view" => 1,
                    "add" => 1,
                    "edit" => 1,
                    "delete" => 1
                ],
                [
                    "id" => 3,
                    "menu" => "job management",
                    "view" => 1,
                    "add" => 1,
                    "edit" => 1,
                    "delete" => 1
                ],
                [
                    "id" => 4,
                    "menu" => "users",
                    "view" => 1,
                    "add" => 1,
                    "edit" => 1,
                    "delete" => 1
                ],
                [
                    "id" => 5,
                    "menu" => "candidate",
                    "view" => 1,
                    "add" => 1,
                    "edit" => 1,
                    "delete" => 1
                ],
                [
                    "id" => 6,
                    "menu" => "interview",
                    "view" => 1,
                    "add" => 1,
                    "edit" => 1,
                    "delete" => 1
                ],
                [
                    "id" => 7,
                    "menu" => "reports",
                    "view" => 1,
                    "add" => 1,
                    "edit" => 1,
                    "delete" => 1
                ],
                  [
                    "id" => 8,
                    "menu" => "smart-search",
                    "view" => 1,
                    "add" => 1,
                    "edit" => 1,
                    "delete" => 1
                ]
            ];
            foreach ($permission_data as $permission) {
                RolePermission::create([
                    'bash_id'=> Str::uuid(),
                    'menu' => $permission['menu'],
                    'view' => $permission['view'],
                    'add' => $permission['add'],
                    'edit' => $permission['edit'],
                    'delete' => $permission['delete'],
                    'company_id' => $company->id, // The company_id you want to associate
                    'role_id' => $oemuser->role_id // The role_id you want to associate
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'success'

            ], 200);
        }
    }
    public function change_password(Request $request)
    {
        $auth = JWTAuth::user();

        // Check if user is null (if token is invalid or expired)
        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized, invalid token.',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required', 
            'old_password'=>'required',
            'new_password'=>'required',
        ], [
            'email.required' => 'Email is required.',  
            'old_password.required'=>'Old Password is required.',
            'new_password.required'=>'New Password is required.'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $user = User::where('email', $request->email)->where('role_id',$auth->role_id)->where('company_id',$auth->company_id)->where('active','1')->first();
        if($request->old_password==$request->new_password)
        {
         return response()->json([
             'status'=>false,
             'message'=>'Password should be Different.'
         ]);
        }else{
        
         if(!empty($user))
         {
           
             $user->password = bcrypt($request->new_password); // Your custom way of hashing or processing
             $user->save();
             return response()->json(['status' => true, 'message' => 'Password updated.'], 200);
          
                                                                                                                                                                                  
         }else{
            return response()->json(['status' => true, 'message' => 'Email not found.'], 200);
          
         }
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
    
    public function update_first_login(Request $request)
    {
         $user = JWTAuth::user();

        // Check if user is null (if token is invalid or expired)
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized, invalid token.',
            ], 401);
        }
             $validator = Validator::make($request->all(), [
            'user_id' => 'required', 
            'first_login'=>'required',
           
        ], [
            'user_id.required' => 'User Id is required.',  
            'first_login.required'=>'First Login is required.',
           
        ]);
     
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
   $data=['user_id'=>$request->user_id,
        'first_login'=>$request->first_login];
        // Return the authenticated user data
        return response()->json([
            'status' => true,
            'message' => 'First Login Status.',
            'data' => $data,
        ], 200);
    }
}
