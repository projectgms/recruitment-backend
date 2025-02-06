<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\Admin\AdminAuthController;

//Recruiter
use App\Http\Controllers\Recruiter\RecruiterAuthController;

//JobSeeker
use App\Http\Controllers\JobSeeker\JobSeekerAuthController;

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
    Route::middleware(['auth:sanctum'])->group(function () {
        // Route::get('oem/profile', [OEMAuthController::class, 'profile']);
        
    });



    // Job Seeker API routes
    Route::post('jobseeker/login', [JobSeekerAuthController::class, 'login']);
    Route::post('jobseeker/register',[JobSeekerAuthController::class,'register']);
    Route::middleware(['auth:sanctum'])->group(function () {

     //   Route::get('aggregater/profile', [AggregaterAuthController::class, 'profile']);
      

    });

  
  
});
