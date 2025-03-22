<?php

use App\Http\Controllers\JobSeeker\JobController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//JobSeeker
use App\Http\Controllers\JobSeeker\JobSeekerAuthController;
use App\Http\Controllers\JobSeeker\JobSeekerController;
use App\Http\Controllers\JobSeeker\JobSeekerProfileController;
use App\Http\Controllers\Recruiter\RecruiterController;
// Job Seeker API routes
    Route::post('jobseeker/login', [JobSeekerAuthController::class, 'login']);
    Route::post('jobseeker/register',[JobSeekerAuthController::class,'register']);
    Route::post('jobseeker/forgot_password', [JobSeekerController::class, 'forgot_password']);
    Route::post('jobseeker/reset_password',[JobSeekerController::class,'reset_password']);

    Route::middleware(['auth:api'])->group(function () {

       Route::get('jobseeker/profile', [JobSeekerAuthController::class, 'profile']);
       Route::post('jobseeker/personal_info',[JobSeekerProfileController::class,'personal_info']);
       Route::get('jobseeker/get_personal_info',[JobSeekerProfileController::class,'get_personal_info']);
       Route::post('jobseeker/change_password',[JobSeekerAuthController::class,'change_password']);

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

       Route::post('jobseeker/add_certification',[JobSeekerProfileController::class,'add_certification']);
       Route::get('jobseeker/get_certification',[JobSeekerProfileController::class,'get_certification']);
       Route::post('jobseeker/update_certification',[JobSeekerProfileController::class,'update_certification']);
       Route::post('jobseeker/delete_certification',[JobSeekerProfileController::class,'delete_certification']);

       Route::post('jobseeker/add_education',[JobSeekerProfileController::class,'add_education']);
       Route::get('jobseeker/get_education',[JobSeekerProfileController::class,'get_education']);
       Route::post('jobseeker/update_education',[JobSeekerProfileController::class,'update_education']);
       Route::post('jobseeker/delete_education',[JobSeekerProfileController::class,'delete_education']);

       Route::post('jobseeker/profile_other_details',[JobSeekerProfileController::class,'profile_other_details']);
       Route::get('jobseeker/get_profile_other_details',[JobSeekerProfileController::class,'get_profile_other_details']);
       Route::post('jobseeker/open_to_work',[JobSeekerProfileController::class,'open_to_work']);

       Route::get('jobseeker/check_profile_complete',[JobSeekerProfileController::class,'check_profile_complete']);

       Route::get('jobseeker/master_resume_json',[JobSeekerProfileController::class,'master_resume_json']);
       Route::post('jobseeker/generate_resume',[JobSeekerProfileController::class,'generate_resume']);
       Route::get('jobseeker/view_generate_resume',[JobSeekerProfileController::class,'view_generate_resume']);
     
       //Job
       Route::post('jobseeker/job_list',[JobController::class,'job_list']);
       Route::post('jobseeker/job_list_filter',[JobController::class,'job_list_filter']);
       Route::post('jobseeker/get_job_details',[JobController::class,'get_job_details']);
       Route::post('jobseeker/apply_job',[JobController::class,'apply_job']);



    });
