<?php

namespace App\Http\Controllers\Admin;
use App\Models\RolePermission;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use App\Models\SuperAdminRole;
use App\Models\RecruiterRole;
use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Notifications\Admin\RegisterNotification;


class AdminUserController extends Controller
{
    //

    public function get_roles()
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }
        // if($auth->role=='super_admin')
        // {
          //  $roles = SuperAdminRole::select('id', 'role','status')->where('parent_id', '!=', '0')->where('active','1')->get();
        // }else{
        //     $roles = SuperAdminRole::select('id', 'role','status')->where('parent_id', '!=', '0')->where('active','1')->where('added_by','=',$auth->id)->get(); 
        // }
    
       
       
        $roles =  SuperAdminRole::select('id', 'bash_id','role','role_desc','active','status','created_at','updated_at')
        ->where('parent_id', '!=', 0)
        ->where('active', 1)
        ->get();
      
        return response()->json([
            'status' => true,
            'message' => 'Get Roles.',
            'data' => $roles
        ]);
    }

    public function add_roles(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }

        $validator = Validator::make($request->all(), [
            'role' => 'required',
          //  'role_desc'=>'required'
        ], [
            'role.required' => 'Role is required.',
            //'role_desc.required'=>'Role Description is required.'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' =>$validator->errors(),
                
            ], 422);
        }
        $check_role=SuperAdminRole::select('id','role','status')->where('role',$request->role)->where('active','1')->first();
        if($check_role)
        {
            return response()->json([
                "status" => false,
                "message" => "Role Already Exist",
               
            ]); 
        }else{
            $get_role=SuperAdminRole::select('id')->where('role',$auth->role)->first();
           $roles=new SuperAdminRole();
           $roles->bash_id=Str::uuid();
           $roles->role=$request->role;
           $roles->parent_id=$get_role->id;
           $roles->status="Active";
           $roles->added_by=$auth->id;
           $roles->save();
           return response()->json([
            "status" => true,
            "message" => "Role Added",
           
        ]);
        }
    }
    public function update_role(Request $request)
    {
        $auth=JWTAuth::user();
        if(!$auth)
        {
            return response()->json(['status'=>false,'message'=>'Unauthorized'],401);
        }
        
        $validator=Validator::make($request->all(),[
            'id'=>'required',
            'role'=>'required',
           // 'role_desc'=>'required'
        ],[
            'id.required'=>'Id is required.',
            'role.required'=>'Role is required.',
            //'role_desc.required'=>'Role Description is required.'
        ]);

           if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' =>$validator->errors(),
                
            ], 422);
        }
        $check_role=SuperAdminRole::select('id','role','status')->where('role',$request->role)->where('id','!=',$request->id)->where('active','1')->first();
        if($check_role)
        {
            return response()->json([
                "status" => false,
                "message" => "Role Already Exist",
               
            ]); 
        }else{
            $get_role=SuperAdminRole::select('id')->where('role',$auth->role)->first();
           $roles=SuperAdminRole::find($request->id);
           
           $roles->role=$request->role;
           $roles->role_desc=$request->role_desc;
           $roles->parent_id=$get_role->id;
           $roles->status="Active";
           $roles->added_by=$auth->id;
           $roles->save();
           return response()->json([
            "status" => true,
            "message" => "Role Updated.",
           
        ]);
        }
        
    }
    public function bulk_action(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }

        $validator = Validator::make($request->all(), [
           'action' => 'required',
           'ids'=>'array|required'
        ], [
            'action.required' => 'Action is required.',
            'ids.required'=>'Id is required.'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' =>$validator->errors(),
                
            ], 422);
        }
        if($request->action=='enable')
        {
         SuperAdminRole::whereIn('id', $request->ids)
        ->update(['status' => 'Active']);
        }
        if($request->action=='disable')
        {
         SuperAdminRole::whereIn('id', $request->ids)
        ->update(['status' => 'Inactive']);
        }
         if($request->action=='delete')
        {
         SuperAdminRole::whereIn('id', $request->ids)
        ->update(['active' => '0']);
        }
           return response()->json([
            "status" => true,
            "message" => "Action Updated",
           
        ]);
    }
    public function delete_role(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }

        $validator = Validator::make($request->all(), [
           
           'id'=>'required'
        ], [
           
            'id.required'=>'Id is required.'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' =>$validator->errors(),
                
            ], 422);
        }

        $roles=SuperAdminRole::find($request->id);
         
            $roles->active="0";
        
        
           $roles->save();
           return response()->json([
            "status" => true,
            "message" => "Role Deleted",
           
        ]);
    }

    public function add_role_permission(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }
        $validator = Validator::make($request->all(), [
            'role_id' => 'required',
            'permission' => 'required|array'

        ], [
            'role_id.required' => 'Role Id is required.',
          
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $check_role = RolePermission::where('role_id', $request->role_id)->whereNull('company_id')->first();
        if ($check_role) {
            return response()->json([
                "status" => false,
                "message" => "Permission already added to role.",

            ]);
        } else {
            foreach ($request->permission as $key => $permission) {
                $perm = RolePermission::create([
                    'role_id' => $request->role_id,
                    'bash_id' => Str::uuid(),
                 
                    'menu' => $permission['menu'],
                    'view' => $permission['view'],
                    'add' => $permission['add'],
                    'edit' => $permission['edit'],
                    'delete' => $permission['delete'],
                ]);
            }
            return response()->json(['status' => true, 'message' => 'Role Permission Added.'], 200);
        }
    }
    public function view_permission(Request $request)
{
    $auth = JWTAuth::user();

    if (!$auth) {
        return response()->json(
            [
                'status' => false,
                'message' => 'Unauthorized',
            ],
            401
        );
    }
   
    $validator = Validator::make($request->all(), [
        'role_id' => 'required',
     
    ], [
        'role_id.required' => 'Role Id is required.',
       
    ]);
    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validator->errors(),

        ], 422);
    }
     $permissions = RolePermission::join('superadmin_roles', 'role_permissions.role_id', '=', 'superadmin_roles.id')
            ->select(
                'superadmin_roles.role',
                'role_permissions.role_id',
                'role_permissions.company_id',
                'role_permissions.id',
                'role_permissions.menu',
                'role_permissions.view',
                'role_permissions.add',
                'role_permissions.edit',
                'role_permissions.delete'
            )
            ->whereNull('role_permissions.company_id')
            ->where('superadmin_roles.id', '=', $request->role_id)
            ->get()
            ->groupBy('role');

        $response = [];
        foreach ($permissions as $role => $permissionsList) {
            $role_id = $permissionsList->first()->role_id;
            $response[] = [
                'role' => $role,
                'role_id' => $role_id,
                'permissions' => $permissionsList->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'menu' => $permission->menu,
                        'view' => $permission->view,
                        'add' => $permission->add,
                        'edit' => $permission->edit,
                        'delete' => $permission->delete,
                    ];
                })
            ];
        }

       

    return response()->json([
        'status' => true,
        'data' => $response
    ]);
}

    public function view_role_permission()
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }
       
        $permissions = RolePermission::join('superadmin_roles', 'role_permissions.role_id', '=', 'superadmin_roles.id')
            ->select(
                'superadmin_roles.role',
                'role_permissions.role_id',
                'role_permissions.company_id',
                'role_permissions.id',
                'role_permissions.menu',
                'role_permissions.view',
                'role_permissions.add',
                'role_permissions.edit',
                'role_permissions.delete'
            )
            ->whereNull('role_permissions.company_id')
            ->where('superadmin_roles.role', '!=', $auth->role)
            ->get()
            ->groupBy('role');

        $response = [];

        foreach ($permissions as $role => $permissionsList) {
            $role_id = $permissionsList->first()->role_id;

            $response[] = [
                'role' => $role,
                'role_id' => $role_id,
                'permissions' => $permissionsList->map(function ($permission) {
                    return [
                        'id' => $permission->id,
                        'menu' => $permission->menu,
                        'view' => $permission->view,
                        'add' => $permission->add,
                        'edit' => $permission->edit,
                        'delete' => $permission->delete
                    ];
                })
            ];
        }

        return response()->json([
            'status' => true,
            'data' => $response
        ]);
    }

    public function update_role_permission(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }
        $validator = Validator::make($request->all(), [
            'role_id' => 'required',
         
            'permission' => 'required|array'

        ], [
            'role_id.required' => 'Role Id is required.',
           
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        if ($request->permission) {
            foreach ($request->permission as $key => $permission) {
                $check_role = RolePermission::where('id', $permission['id'])  // Assuming 'menu' is the id
                    ->where('role_id', $request->role_id)
                    ->whereNull('company_id')
                    ->first();

                // Check if the role permission record exists
                if ($check_role) {

                    // Update the role permission record
                    $check_role->update([
                        'menu' => $permission['menu'],
                        'view' => $permission['view'],
                        'add' => $permission['add'],
                        'edit' => $permission['edit'],
                        'delete' => $permission['delete'],
                    ]);
                }
            }



            return response()->json(['status' => true, 'message' => 'Role Permission updated.'], 200);
        }
    }

    public function delete_role_permission(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }
        $validator = Validator::make($request->all(), [
            'role_id' => 'required',
           
        ], [
            'role_id.required' => 'Role Id is required.',
          
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $deleted = RolePermission::where('role_id', $request->role_id)
    ->whereNull('company_id')
    ->delete();

if ($deleted) {
    return response()->json(['status' => true, 'message' => 'Role Permissions deleted.'], 200);
} else {
    return response()->json(['status' => false, 'message' => 'No matching Role Permissions found.'], 404);
}
    
    }

    public function add_user(Request $request)
    {

        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }
        $validator = Validator::make($request->all(), [

            'name' => 'required',
          
            'email' => 'required',
            'mobile' => 'required',
            'password' => 'required',
            'role' => 'required'

        ], [
            'name.required' => 'Name is required.',
           
            'email.required' => 'Email is required.',
            'mobile.required' => 'Mobile Number is required.',
            'password.required' => 'password is required.',
            'role.required' => 'Role Id is required.'


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

           
                $roles = SuperAdminRole::select('id', 'role')->where('role', '=', $request->role)->where('active','1')->first();
           if($roles)
           {
            $oemuser = new User();
            $oemuser->name = $request->name;
            $oemuser->bash_id = Str::uuid();

            $oemuser->email = $request->email;
            $oemuser->role = $roles->role;
            $oemuser->role_id = $roles->id;
            $oemuser->password = bcrypt($request->password);
            $oemuser->mobile = $request->mobile;
            $oemuser->added_by = $auth->id;
           
            $oemuser->save();

            $oemuser->notify(new RegisterNotification($request->email, $request->password));


            return response()->json([
                'status' => true,
                'message' => 'success'

            ], 200);
           }else{
                 return response()->json([
                'status' => false,
                'message' => 'role not found'

            ]);
           }
        }
    }

    public function view_user(Request $request)
    {
       $auth = JWTAuth::user();

            if (!$auth) {
                return response()->json([
                    'status' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }
                $roles = SuperAdminRole::select('id', 'role')
                    ->where('parent_id', '!=', 0)
                    ->get();
        
                $roleIds = $roles->pluck('id')->toArray();
        
               if ($auth->role === 'super_admin') {
                $users =User::whereIn('role_id', $roleIds)
                    ->whereNull('company_id')
                    ->where('active', '=', '1')
                    ->get();
            } else {
                $users=User::whereIn('role_id', $roleIds)
                    ->whereNull('company_id')
                    ->where('added_by', '=', $auth->id)
                    ->where('active', '=', '1')
                    ->get();
            }
        
            return response()->json([
                'status' => true,
                'message' => 'Users fetched successfully.',
                'data' => $users,
            ]);
    }

    public function update_user(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'name' => 'required',
            'email' => 'required',
            'mobile' => 'required',
            //'password' => 'required',
            'role' => 'required'

        ], [
            'id.required' => 'Id is required',
            'name.required' => 'Name is required.',
            'email.required' => 'Email is required.',
            'mobile.required' => 'Mobile Number is required.',
           // 'password.required' => 'password is required.',
            'role.required' => 'Role Id is required.'


        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

        $check_user = User::where('email', $request->email)->where('id', '!=', $request->id)
            ->count();

        $check_user_mobile = User::where('mobile', $request->mobile)->where('id', '!=', $request->id)
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

           $roles = SuperAdminRole::select('id', 'role')->where('role', '=', $request->role)->where('active','1')->first();
           if($roles)
           {
            $oemuser = User::find($request->id);
            $oemuser->name = $request->name;

            $oemuser->email = $request->email;
            $oemuser->role = $roles->role;
            $oemuser->role_id = $roles->id;
            //$oemuser->password = bcrypt($request->password);
            $oemuser->mobile = $request->mobile;
         
            $oemuser->save();

            return response()->json([
                'status' => true,
                'message' => 'success'

            ], 200);
        }else{
            return response()->json([
                'status' => false,
                'message' => 'role not found'

            ]); 
        }
        }
    }
 public function bulk_action_user(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }

        $validator = Validator::make($request->all(), [
           'action' => 'required',
           'ids'=>'array|required'
        ], [
            'action.required' => 'Action is required.',
            'ids.required'=>'Id is required.'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' =>$validator->errors(),
                
            ], 422);
        }
        if($request->action=='enable')
        {
         User::whereIn('id', $request->ids)
        ->update(['status' => 'Active']);
        }
        if($request->action=='disable')
        {
         User::whereIn('id', $request->ids)
        ->update(['status' => 'Inactive']);
        }
         if($request->action=='delete')
        {
         User::whereIn('id', $request->ids)
        ->update(['active' => '0']);
        }
           return response()->json([
            "status" => true,
            "message" => "Action Updated",
           
        ]);
    }
    public function delete_user(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }
        $validator = Validator::make($request->all(), [
            'id' => 'required',


        ], [
            'id.required' => 'Id is required',


        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $check_user = User::where('id', $request->id)  // Assuming 'menu' is the id

            ->first();

        // Check if the role permission record exists
        if ($check_user) {


            $check_user->active = '0';
            $check_user->save();
        }
        return response()->json([
            'status' => true,
            'message' => 'User deleted.'

        ], 200);
    }
}
