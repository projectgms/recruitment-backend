<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
//Recruiter
use App\Http\Controllers\Recruiter\JobPostController;

use App\Http\Controllers\Recruiter\RecruiterAuthController;
use App\Http\Controllers\Recruiter\RecruiterController;
use App\Http\Controllers\Recruiter\RecruiterCompanyController;
use App\Http\Controllers\Admin\AdminAuthController;

Route::get('/login', function () {
    return response()->json(['error' => 'Unauthorized.'], 401);
})->name('login');




// Include route files
Route::prefix('v1')->group(function () {
    
    // Include routes for jobseeker
    require base_path('routes/api/jobseeker.php');


    Route::post('admin/login', [AdminAuthController::class, 'login']);
    Route::post('recruiter/login', [RecruiterAuthController::class, 'login']);
   Route::post('recruiter/register',[RecruiterAuthController::class,'register']);
   Route::post('recruiter/forgot_password',[RecruiterController::class,'forgot_password']);
   Route::post('recruiter/reset_password',[RecruiterController::class,'reset_password']);
    Route::middleware(['auth:api',\App\Http\Middleware\AttachPermissionsMiddleware::class,\App\Http\Middleware\DynamicRoleMiddleware::class])->group(function () {
        Route::get('recruiter/profile', [RecruiterAuthController::class, 'profile']);
        Route::post('company_profile',[RecruiterCompanyController::class,'company_profile']);
        Route::post('recruiter/update_company_profile',[RecruiterCompanyController::class,'update_company_profile']);

        Route::post('recruiter/add_job_post',[JobPostController::class,'add_job_post']);
        Route::get('recruiter/view_job_post',[JobPostController::class,'view_job_post']);
        Route::post('recruiter/update_job_post',[JobPostController::class,'update_job_post']);
        Route::post('recruiter/delete_job_post',[JobPostController::class,'delete_job_post']);

    });
});

Route::prefix('v1')->group(function () {

Route::get('welcome',[AuthController::class,'welcome']);
  
});
