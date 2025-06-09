<?php

namespace Database\Seeders;

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

        // Create Interview Rounds
        $rounds = [
            'MCQ Test',
            'Mock Interview',
            'Technical Test',
            'Technical Interview',
            'HR Round',
        ];

        foreach ($rounds as $name) {
            InterviewRound::firstOrCreate(
                ['round_name' => $name],
                ['bash_id' => Str::uuid()]
            );
        }

        // Create SuperAdmin Role
        $superAdminRole = SuperAdminRole::firstOrCreate(
            ['role' => 'super_admin'],
            [
                'bash_id' => Str::uuid(),
                'parent_id' => 0,
                'status' => 'Active',
                'active' => 1,
            ]
        );

        // Create Recruiter Role
        $recruiterRole = RecruiterRole::firstOrCreate(
            ['role' => 'recruiter'],
            [
                'bash_id' => Str::uuid(),
                'parent_id' => 0,
                'status' => 'Active',
                'active' => 1,
            ]
        );

        // Create Default SuperAdmin User
        $password = bcrypt('123456');
        $superAdminUser = User::firstOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'SuperAdmin User',
                'password' => $password,
                'role_id' => $superAdminRole->id,
                'role' => $superAdminRole->role,
                'mobile' => 9657899983,
                'oauth_provider' => 'email_password',
                'status' => 'active',
                'bash_id' => Str::uuid(),
                'company_id' => null,
                'active' => 1,
                'last_login' => Carbon::now(),
            ]
        );

        // Create Sub Roles under Recruiter
        $subRoles = ['HR-Admin', 'Manager', 'Employee'];
        foreach ($subRoles as $role) {
            RecruiterRole::firstOrCreate(
                ['role' => $role],
                [
                    'bash_id' => Str::uuid(),
                    'parent_id' => $recruiterRole->id,
                    'status' => 'Active',
                    'active' => 1,
                ]
            );
        }

        // Insert Default Permissions for SuperAdmin
        $menus = [
            'admin-dashboard',
            'jobseeker-management',
            'recruiter-management',
            'subscription-payment',
            'reports-analytics',
            'user-management',
            'role-management',
            'role-permission',
            'profile',
            'setting',
            'earning',
           
        ];

        foreach ($menus as $menu) {
            RolePermission::firstOrCreate(
                [
                    'role_id' => $superAdminRole->id,
                    'menu' => $menu,
                ],
                [
                    'bash_id' => Str::uuid(),
                    'company_id' => null,
                    'view' => 1,
                    'add' => 1,
                    'edit' => 1,
                    'delete' => 1,
                ]
            );
        }

        // Add any other default data here if needed

        // Output a message that data was seeded
        $this->command->info('SuperAdmin and Recruiter roles, users, and permissions have been seeded!');
    }
}
