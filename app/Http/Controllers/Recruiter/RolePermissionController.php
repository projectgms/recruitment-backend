<?php

namespace App\Http\Controllers\Recruiter;

use App\Models\User;

use App\Http\Controllers\Controller;
use App\Models\RolePermission;
use App\Models\RecruiterRole;
use App\Models\SuperAdminRole;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Notifications\RegisterNotification;


class RolePermissionController extends Controller
{
    //
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
            'company_id' => 'required',
            'permission' => 'required|array'

        ], [
            'role_id.required' => 'Role Id is required.',
            'company_id.required' => 'Company Id is required',


        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $check_role = RolePermission::where('role_id', $request->role_id)->where('company_id', $request->company_id)->first();
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
                    'company_id' => $request->company_id,
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
        $permissions = RolePermission::join('recruiter_roles', 'role_permissions.role_id', '=', 'recruiter_roles.id')
            ->select(
                'recruiter_roles.role',  // Role name
                'role_permissions.role_id',
                'role_permissions.company_id',
                'role_permissions.id',
                'role_permissions.menu',
                'role_permissions.view',
                'role_permissions.add',
                'role_permissions.edit',
                'role_permissions.delete'
            )
            ->where('role_permissions.company_id', $auth->company_id) // filter by company_id
            ->get()
            ->groupBy('role');  // Group the results by the role name

        // Structure the response with each role and its permissions
        $response = [];
        foreach ($permissions as $role => $permissionsList) {
            // Get role_id and company_id from the first permission in the list
            $role_id = $permissionsList->first()->role_id;
            $company_id = $permissionsList->first()->company_id;

            $response[] = [
                'role' => $role,  // Role name
                'role_id' => $role_id,  // Role ID
                'company_id' => $company_id,  // Company ID
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
            'company_id' => 'required',
            'permission' => 'required|array'

        ], [
            'role_id.required' => 'Role Id is required.',
            'company_id.required' => 'Company Id is required',


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
                    ->where('company_id', $request->company_id)
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
            'company_id' => 'required',

        ], [
            'role_id.required' => 'Role Id is required.',
            'company_id.required' => 'Company Id is required',


        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $delete_permission = RolePermission::where('role_id', $request->role_id)->where('company_id', $request->company_id)->first();
        if ($delete_permission) {
            $delete_permission->delete();
            return response()->json(['status' => true, 'message' => 'Role Permission deleted.'], 200);
        }
    }
    public function get_roles(Request $request)
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
        if ($auth->company_id) {
            $roles = RecruiterRole::select('id', 'role')->where('parent_id', '!=', '0')->get();
        } else {
            $roles = SuperAdminRole::select('id', 'role')->where('parent_id', '!=', '0')->get();
        }
        return response()->json([
            'status' => true,
            'message' => 'Get Roles.',
            'data' => $roles
        ]);
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
            'company_id' => 'required',
            'email' => 'required',
            'mobile' => 'required',
            'password' => 'required',
            'role_id' => 'required'

        ], [
            'name.required' => 'Name is required.',
            'company_id.required' => 'Company Id is required',
            'email.required' => 'Email is required.',
            'mobile.required' => 'Mobile Number is required.',
            'password.required' => 'password is required.',
            'role_id.required' => 'Role Id is required.'


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

            if ($request->company_id) {
                $roles = RecruiterRole::select('id', 'role')->where('id', '=', $request->role_id)->first();
            } else {
                $roles = SuperAdminRole::select('id', 'role')->where('id', '=', $request->role_id)->first();
            }
            $oemuser = new User();
            $oemuser->name = $request->name;
            $oemuser->bash_id = Str::uuid();

            $oemuser->email = $request->email;
            $oemuser->role = $roles->role;
            $oemuser->role_id = $roles->id;
            $oemuser->password = bcrypt($request->password);
            $oemuser->mobile = $request->mobile;
            $oemuser->company_id = $request->company_id;
            $oemuser->save();

            $oemuser->notify(new RegisterNotification($request->email, $request->password));


            return response()->json([
                'status' => true,
                'message' => 'success'

            ], 200);
        }
    }

    public function view_user(Request $request)
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
        if ($auth->company_id) {

            $roles = RecruiterRole::select('id', 'role')
                ->where('parent_id', '!=', 0)
                ->get();

            $roleIds = $roles->pluck('id')->toArray();

            $users = User::whereIn('role_id', $roleIds)
                ->where('company_id', $auth->company_id)
                ->get();
        } else {

            $roles = SuperAdminRole::select('id', 'role')
                ->where('parent_id', '!=', 0)
                ->get();

            $roleIds = $roles->pluck('id')->toArray();

            $users = User::whereIn('role_id', $roleIds)
                ->whereNull('company_id') // If you only want those with no company

                ->get();
        }

        return response()->json([
            'status' => true,
            'message' => 'Users fetched successfully.',

            'users' => $users,
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
            'company_id' => 'required',
            'email' => 'required',
            'mobile' => 'required',
            'password' => 'required',
            'role_id' => 'required'

        ], [
            'id.required' => 'Id is required',
            'name.required' => 'Name is required.',
            'company_id.required' => 'Company Id is required',
            'email.required' => 'Email is required.',
            'mobile.required' => 'Mobile Number is required.',
            'password.required' => 'password is required.',
            'role_id.required' => 'Role Id is required.'


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

            if ($request->company_id) {
                $roles = RecruiterRole::select('id', 'role')->where('id', '=', $request->role_id)->first();
            } else {
                $roles = SuperAdminRole::select('id', 'role')->where('id', '=', $request->role_id)->first();
            }
            $oemuser = User::find($request->id);
            $oemuser->name = $request->name;

            $oemuser->email = $request->email;
            $oemuser->role = $roles->role;
            $oemuser->role_id = $roles->id;
            $oemuser->password = bcrypt($request->password);
            $oemuser->mobile = $request->mobile;
            $oemuser->company_id = $request->company_id;
            $oemuser->save();

            return response()->json([
                'status' => true,
                'message' => 'success'

            ], 200);
        }
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
