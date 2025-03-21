<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
//Recruiter
use App\Http\Controllers\Recruiter\JobPostController;

use App\Http\Controllers\Recruiter\RecruiterAuthController;
use App\Http\Controllers\Recruiter\RecruiterController;
use App\Http\Controllers\Recruiter\RecruiterCompanyController;

//Admin
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Recruiter\CandidateController;
use App\Http\Controllers\Recruiter\RolePermissionController;
use App\Models\RolePermission;

Route::get('/login', function () {
    return response()->json(['error' => 'Unauthorized.'], 401);
})->name('login');




// Include route files
Route::prefix('v1')->group(function () {

    // Include routes for jobseeker
    require base_path('routes/api/jobseeker.php');


    Route::post('admin/login', [AdminAuthController::class, 'login']);
    Route::post('admin/forgot_password', [AdminAuthController::class, 'forgot_password']);
    Route::post('admin/reset_password', [AdminAuthController::class, 'reset_password']);

    Route::post('recruiter/login', [RecruiterAuthController::class, 'login']);
    Route::post('recruiter/register', [RecruiterAuthController::class, 'register']);
    Route::post('recruiter/forgot_password', [RecruiterController::class, 'forgot_password']);
    Route::post('recruiter/reset_password', [RecruiterController::class, 'reset_password']);
    Route::post('decrypt_email', [RecruiterController::class, 'decrypt_email']);
    Route::middleware(['auth:api', \App\Http\Middleware\AttachPermissionsMiddleware::class, \App\Http\Middleware\DynamicRoleMiddleware::class])->group(function () {
        Route::get('recruiter/profile', [RecruiterAuthController::class, 'profile']);
        Route::post('company_profile', [RecruiterCompanyController::class, 'company_profile']);
        Route::post('update_company_profile', [RecruiterCompanyController::class, 'update_company_profile']);

        Route::get('recruiter/get_interview_round', [JobPostController::class, 'get_interview_round']);
        Route::post('recruiter/add_job_post', [JobPostController::class, 'add_job_post']);
        Route::post('recruiter/view_job_post', [JobPostController::class, 'view_job_post']);
        Route::post('recruiter/update_job_post', [JobPostController::class, 'update_job_post']);
        Route::post('recruiter/delete_job_post', [JobPostController::class, 'delete_job_post']);

        Route::post('recruiter/add_role_permission', [RolePermissionController::class, 'add_role_permission']);
        Route::get('recruiter/view_role_permission', [RolePermissionController::class, 'view_role_permission']);
        Route::post('recruiter/update_role_permission', [RolePermissionController::class, 'update_role_permission']);
        Route::post('recruiter/delete_role_permission', [RolePermissionController::class, 'delete_role_permission']);

        Route::post('get_roles', [RolePermissionController::class, 'get_roles']);
        Route::post('add_user', [RolePermissionController::class, 'add_user']);
        Route::post('view_user', [RolePermissionController::class, 'view_user']);
        Route::post('update_user', [RolePermissionController::class, 'update_user']);
        Route::post('delete_user', [RolePermissionController::class, 'delete_user']);

        Route::post('job_applicant',[CandidateController::class,'job_applicant']);

        Route::post('logout', [AuthController::class, 'logout']);

        //Admin Routes
        Route::get('admin/profile', [AdminAuthController::class, 'profile']);
    });
});

Route::prefix('v1')->group(function () {

    Route::get('welcome', [AuthController::class, 'welcome']);
});
