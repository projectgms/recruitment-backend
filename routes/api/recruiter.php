
<?php

use App\Http\Controllers\Recruiter\JobPostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//Recruiter
use App\Http\Controllers\Recruiter\RecruiterAuthController;
use App\Http\Controllers\Recruiter\RecruiterController;
use App\Http\Controllers\Recruiter\RecruiterCompanyController;


// Recruiter/Comapny API routes
   Route::post('recruiter/login', [RecruiterAuthController::class, 'login']);
   Route::post('recruiter/register',[RecruiterAuthController::class,'register']);
   Route::post('recruiter/forgot_password',[RecruiterController::class,'forgot_password']);
   Route::post('recruiter/reset_password',[RecruiterController::class,'reset_password']);
    Route::middleware(['auth:api'])->group(function () {
        Route::get('recruiter/profile', [RecruiterAuthController::class, 'profile']);
        Route::get('recruiter/company_profile',[RecruiterCompanyController::class,'company_profile']);
        Route::post('recruiter/update_company_profile',[RecruiterCompanyController::class,'update_company_profile']);

        Route::post('recruiter/add_job_post',[JobPostController::class,'add_job_post']);
        Route::get('recruiter/view_job_post',[JobPostController::class,'view_job_post']);
        Route::post('recruiter/update_job_post',[JobPostController::class,'update_job_post']);
        Route::post('recruiter/delete_job_post',[JobPostController::class,'delete_job_post']);

    });



 
  