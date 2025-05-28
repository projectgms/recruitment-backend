<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
//Recruiter
use App\Http\Controllers\Recruiter\JobPostController;

use App\Http\Controllers\Recruiter\RecruiterAuthController;
use App\Http\Controllers\Recruiter\RecruiterController;
use App\Http\Controllers\Recruiter\RecruiterCompanyController;
use App\Http\Controllers\Recruiter\CandidateController;
use App\Http\Controllers\Recruiter\RolePermissionController;
use App\Http\Controllers\Recruiter\RecruiterSubscriptionController;

use App\Http\Controllers\Recruiter\InterviewController;
//Admin
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminUserController;

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
    Route::post('email_subscription',[AuthController::class,'email_subscription']);

    Route::post('recruiter/login', [RecruiterAuthController::class, 'login']);
    Route::post('recruiter/register', [RecruiterAuthController::class, 'register']);
    Route::post('recruiter/forgot_password', [RecruiterController::class, 'forgot_password']);
    Route::post('recruiter/reset_password', [RecruiterController::class, 'reset_password']);
    Route::post('decrypt_email', [RecruiterController::class, 'decrypt_email']);
    Route::middleware(['auth:api', \App\Http\Middleware\AttachPermissionsMiddleware::class, \App\Http\Middleware\DynamicRoleMiddleware::class])->group(function () {
        
        //Recruiter Routes
        Route::get('recruiter/profile', [RecruiterAuthController::class, 'profile']);
        Route::post('recruiter/change_password',[RecruiterAuthController::class,'change_password']);
        Route::post('recruiter/update_first_login',[RecruiterAuthController::class,'update_first_login']);


        Route::post('company_profile', [RecruiterCompanyController::class, 'company_profile']);
        Route::post('update_company_profile', [RecruiterCompanyController::class, 'update_company_profile']);
        Route::post('recruiter/add_company_event',[RecruiterCompanyController::class,'add_company_event']);
        Route::post('recruiter/view_company_event',[RecruiterCompanyController::class,'view_company_event']);
        Route::post('recruiter/delete_company_event',[RecruiterCompanyController::class,'delete_company_event']);

        Route::get('recruiter/get_interview_round', [JobPostController::class, 'get_interview_round']);
        Route::post('recruiter/check_interview_questions',[JobPostController::class,'check_interview_questions']);
        Route::post('recruiter/add_job_post', [JobPostController::class, 'add_job_post']);
        Route::post('recruiter/view_job_post', [JobPostController::class, 'view_job_post']);
        Route::post('recruiter/update_job_post', [JobPostController::class, 'update_job_post']);
        Route::post('recruiter/delete_job_post', [JobPostController::class, 'delete_job_post']);
        Route::post('recruiter/get_job_post_count',[JobPostController::class,'get_job_post_count']);
        Route::post('recruiter/salary_insights',[JobPostController::class,'salary_insights']);
        Route::get('recruiter/recent_job_post',[JobPostController::class,'recent_job_post']);
        Route::post('recruiter/pin_job',[JobPostController::class,'pin_job']);
        Route::post('recruiter/get_pin_job',[JobPostController::class,'get_pin_job']);
        Route::post('recruiter/auto_apply_job_application',[JobPostController::class,'auto_apply_job_application']);
        Route::post('recruiter/ai_generate_question',[JobPostController::class,'ai_generate_question']);

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
        Route::post('recruiter/total_job_application',[CandidateController::class,'total_job_application']);
        Route::post('recruiter/hired_application',[CandidateController::class,'hired_application']);
        Route::get('recruiter/recent_application',[CandidateController::class,'recent_application']);

        Route::post('recruiter/candidate_analysis_by_jd',[CandidateController::class,'candidate_analysis_by_jd']);
        Route::post('recruiter/job_questions',[CandidateController::class,'job_questions']);

        
        Route::post('recruiter/update_filter_job_applicant',[CandidateController::class,'update_filter_job_applicant']);
        Route::post('recruiter/update_filter_job_applicant_test',[CandidateController::class,'update_filter_job_applicant_test']);
        Route::post('open_to_work',[CandidateController::class,'open_to_work']);
        Route::post('recruiter/smart_search_candidate',[CandidateController::class,'smart_search_candidate']);

         //Job Application Notification
        Route::post('recruiter/view_job_application_notification',[CandidateController::class,'view_job_application_notification']);
        Route::post('recruiter/update_job_application_notification',[CandidateController::class,'update_job_application_notification']);

        Route::post('recruiter/get_candidate_interview_status',[InterviewController::class,'get_candidate_interview_status']);
        Route::post('recruiter/get_job_interview_status',[InterviewController::class,'get_job_interview_status']);
        Route::post('recruiter/today_interview',[InterviewController::class,'today_interview']);

        Route::post('recruiter/update_candidate_interview_status',[InterviewController::class,'update_candidate_interview_status']);

        Route::get('recruiter/get_plans',[RecruiterSubscriptionController::class,'get_plans']);
        Route::post('recruiter/create_subscription_order',[RecruiterSubscriptionController::class,'create_subscription_order']);
        Route::post('recruiter/verify_payment',[RecruiterSubscriptionController::class,'verify_payment']);

        Route::post('logout', [AuthController::class, 'logout']);

        //Admin Routes
        Route::get('admin/profile', [AdminAuthController::class, 'profile']);
        Route::get('admin/get_roles',[AdminUserController::class,'get_roles']);
        Route::post('admin/add_roles',[AdminUserController::class,'add_roles']);
        Route::post('admin/update_role',[AdminUserController::class,'update_role']);
        Route::post('admin/bulk_action',[AdminUserController::class,'bulk_action']);
        Route::post('admin/delete_role',[AdminUserController::class,'delete_role']);

        Route::post('admin/add_role_permission',[AdminUserController::class,'add_role_permission']);
        Route::get('admin/view_role_permission',[AdminUserController::class,'view_role_permission']);
        Route::post('admin/view_permission',[AdminUserController::class,'view_permission']);
        Route::post('admin/update_role_permission',[AdminUserController::class,'update_role_permission']);
        Route::post('admin/delete_role_permission',[AdminUserController::class,'delete_role_permission']);

        Route::post('admin/add_user', [AdminUserController::class, 'add_user']);
        Route::get('admin/view_user', [AdminUserController::class, 'view_user']);
        Route::post('admin/update_user', [AdminUserController::class, 'update_user']);
        Route::post('admin/delete_user', [AdminUserController::class, 'delete_user']);

        //Admin and Recruiter Common routes
        
    });
});

Route::prefix('v1')->group(function () {

    Route::get('welcome', [AuthController::class, 'welcome']);
});
