<?php namespace Database\Seeders;

use App\Models\InterviewRound;
use Illuminate\Database\Seeder;
use App\Models\SuperAdminRole;
use App\Models\RecruiterRole;
use App\Models\User;
use App\Models\RolePermission;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AllDataSeeder extends Seeder
{
    public function run()
    {

           // Create SuperAdmin Role
         $rounds=[  [
          
            'bash_id'=>Str::uuid(),
            'round_name' => 'MCQ Test',
           
        ],
         [
            'bash_id'=>Str::uuid(),
            'round_name' => 'Mock Interview',
           
        ],
        [
            'bash_id'=>Str::uuid(),
            'round_name' => 'Technical Test',
           
        ],
        [
            'bash_id'=>Str::uuid(),
            'round_name' => 'Technical Interview',
           
        ],
        [
            'bash_id'=>Str::uuid(),
            'round_name' => 'HR Round',
           
        ],
    ];
    foreach ($rounds as $rnd) {
        InterviewRound::firstOrCreate($rnd);
    }
        // Create SuperAdmin Role
        $superAdminRole = SuperAdminRole::firstOrCreate([
            'role' => 'super_admin',
            'bash_id'=>Str::uuid(),
            'parent_id' => 0,  // Root level for 
            'status'=>'Active',
            'active' => 1,
        ]);

        // Create Recruiter Role
        $recruiterRole = RecruiterRole::firstOrCreate([
            'role' => 'recruiter',
            'bash_id'=>Str::uuid(),
            'parent_id' => 0,  // Root level for Recruiter
            'status'=>'Active',
            'active' => 1,
        ]);

        // Create Default SuperAdmin User
        $password=bcrypt('12345');
        $superAdminUser = User::firstOrCreate([
            'email' => 'superadmin@example.com',
        ], [
            'name' => 'SuperAdmin User',
            'password' =>$password,
            'role_id' => $superAdminRole->id,
            'role' => $superAdminRole->role,
            'mobile'=>9657899983,
            'oauth_provider'=>'email_password',
            'status'=>'active',
            'bash_id'=>Str::uuid(),
            'company_id' => null,  // Assuming SuperAdmin doesn't need a company
            'active' => 1,
            'last_login' => Carbon::now(),
        ]);

      

        $hrAdminRole = RecruiterRole::firstOrCreate([
            'role' => 'HR-Admin',
            'bash_id'=>Str::uuid(),
            'parent_id' => $recruiterRole->id, // Set recruiter role as parent
            'status'=>'Active',
            'active' => 1,
        ]);

        $managerRole = RecruiterRole::firstOrCreate([
            'role' => 'Manager',
            'bash_id'=>Str::uuid(),
            'parent_id' => $recruiterRole->id, // Set recruiter role as parent
            'status'=>'Active',
            'active' => 1,
        ]);

        // Insert Default Permissions for SuperAdmin
        $superAdminPermissions = [
         
            [
                'role_id' => $superAdminRole->id,
                'bash_id'=>Str::uuid(),
                'company_id' => null,
                'menu' => 'admin-dashboard',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],
            [
                'role_id' => $superAdminRole->id,
                'bash_id'=>Str::uuid(),
                'company_id' => null,
                'menu' => 'jobseeker-management',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],
            [
                'role_id' => $superAdminRole->id,
                'bash_id'=>Str::uuid(),
                'company_id' => null,
                'menu' => 'recruiter-management',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],
            [
                'role_id' => $superAdminRole->id,
                'bash_id'=>Str::uuid(),
                'company_id' => null,
                'menu' => 'subscription-payment',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],
            [
                'role_id' => $superAdminRole->id,
                'bash_id'=>Str::uuid(),
                'company_id' => null,
                'menu' => 'reports-analytics',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],
            [
                'role_id' => $superAdminRole->id,
                'bash_id'=>Str::uuid(),
                'company_id' => null,
                'menu' => 'user-management',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],
            [
                'role_id' => $superAdminRole->id,
                'bash_id'=>Str::uuid(),
                'company_id' => null,
                'menu' => 'role-management',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],
            [
                'role_id' => $superAdminRole->id,
                'bash_id'=>Str::uuid(),
                'company_id' => null,
                'menu' => 'role-permission',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],
            [
                'role_id' => $superAdminRole->id,
                'bash_id'=>Str::uuid(),
                'company_id' => null,
                'menu' => 'profile',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],
            [
                'role_id' => $superAdminRole->id,
                'bash_id'=>Str::uuid(),
                'company_id' => null,
                'menu' => 'setting',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ],
            [
                'role_id' => $superAdminRole->id,
                'bash_id'=>Str::uuid(),
                'company_id' => null,
                'menu' => 'earning',
                'view' => 1,
                'add' => 1,
                'edit' => 1,
                'delete' => 1,
            ]

        ];

        // Insert permissions into the RolePermission table
        foreach ($superAdminPermissions as $permission) {
            RolePermission::firstOrCreate($permission);
        }

        // Add any other default data here if needed

        // Output a message that data was seeded
        $this->command->info('SuperAdmin and Recruiter roles, users, and permissions have been seeded!');
    }
}
