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
       Route::get('jobseeker/get_personal_info',[JobSeekerProfileController::class,'get_personal_info']);

       Route::post('jobseeker/contact_details',[JobSeekerProfileController::class,'contact_details']);
       Route::get('jobseeker/get_contact_details',[JobSeekerProfileController::class,'get_contact_details']);

       Route::post('jobseeker/add_document',[JobSeekerProfileController::class,'add_document']);
       Route::post('jobseeker/delete_document',[JobSeekerProfileController::class,'delete_document']);
       Route::get('jobseeker/get_document',[JobSeekerProfileController::class,'get_document']);

       Route::post('jobseeker/add_professional_exp',[JobSeekerProfileController::class,'add_professional_exp']);
       Route::get('jobseeker/get_professional_exp',[JobSeekerProfileController::class,'get_professional_exp']);
       Route::post('jobseeker/update_professional_exp',[JobSeekerProfileController::class,'update_professional_exp']);
       Route::post('jobseeker/delete_professional_exp',[JobSeekerProfileController::class,'delete_professional_exp']);

       Route::post('jobseeker/add_internship',[JobSeekerProfileController::class,'add_internship']);
       Route::get('jobseeker/get_internship',[JobSeekerProfileController::class,'get_internship']);
       Route::post('jobseeker/update_internship',[JobSeekerProfileController::class,'update_internship']);
       Route::post('jobseeker/delete_internship',[JobSeekerProfileController::class,'delete_internship']);

       Route::post('jobseeker/add_project',[JobSeekerProfileController::class,'add_project']);
       Route::get('jobseeker/get_project',[JobSeekerProfileController::class,'get_project']);
       Route::post('jobseeker/update_project',[JobSeekerProfileController::class,'update_project']);
       Route::post('jobseeker/delete_project',[JobSeekerProfileController::class,'delete_project']);

       Route::post('jobseeker/add_research_paper',[JobSeekerProfileController::class,'add_research_paper']);
       Route::get('jobseeker/get_research_paper',[JobSeekerProfileController::class,'get_research_paper']);
       Route::post('jobseeker/update_research_paper',[JobSeekerProfileController::class,'update_research_paper']);
       Route::post('jobseeker/delete_research_paper',[JobSeekerProfileController::class,'delete_research_paper']);

       Route::post('jobseeker/add_training',[JobSeekerProfileController::class,'add_training']);
       Route::get('jobseeker/get_training',[JobSeekerProfileController::class,'get_training']);
       Route::post('jobseeker/update_training',[JobSeekerProfileController::class,'update_training']);
       Route::post('jobseeker/delete_training',[JobSeekerProfileController::class,'delete_training']);

    });

  
  
});
