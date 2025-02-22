<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\Admin\AdminAuthController;

//Recruiter
use App\Http\Controllers\Recruiter\RecruiterAuthController;

//JobSeeker
use App\Http\Controllers\JobSeeker\JobSeekerAuthController;
use App\Http\Controllers\JobSeeker\JobSeekerController;
use App\Http\Controllers\JobSeeker\JobSeekerProfileController;
use App\Http\Controllers\Recruiter\RecruiterController;

Route::get('/login', function () {
    return response()->json(['error' => 'Unauthorized.'], 401);
})->name('login');



Route::prefix('v1')->group(function () {

    // //Open Routes 
   
Route::get('welcome',[AuthController::class,'welcome']);
    // Admin API routes

   Route::post('admin/login', [AdminAuthController::class, 'login']);
    Route::middleware(['auth:sanctum'])->group(function () {
       // Route::get("superadmin/profile", [SuperadminAuthController::class, "profile"]);
      
    });



    // Recruiter/Comapny API routes
   Route::post('recruiter/login', [RecruiterAuthController::class, 'login']);
   Route::post('recruiter/register',[RecruiterAuthController::class,'register']);
   Route::post('recruiter/forgot_password',[RecruiterController::class,'forgot_password']);
   Route::post('recruiter/reset_password',[RecruiterController::class,'reset_password']);
    Route::middleware(['auth:api'])->group(function () {
        // Route::get('oem/profile', [OEMAuthController::class, 'profile']);
        
    });



    // Job Seeker API routes
    Route::post('jobseeker/login', [JobSeekerAuthController::class, 'login']);
    Route::post('jobseeker/register',[JobSeekerAuthController::class,'register']);
    Route::post('jobseeker/forgot_password', [JobSeekerController::class, 'forgot_password']);
    Route::post('jobseeker/reset_password',[JobSeekerController::class,'reset_password']);

    Route::middleware(['auth:api'])->group(function () {

       Route::get('jobseeker/profile', [JobSeekerAuthController::class, 'profile']);
       Route::post('jobseeker/personal_info',[JobSeekerProfileController::class,'personal_info']);

    });

  
  
});
