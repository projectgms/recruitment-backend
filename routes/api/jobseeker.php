<?php
use App\Http\Controllers\JobSeeker\AppliedJobController;

use App\Http\Controllers\JobSeeker\CandidateSkillController;
use App\Http\Controllers\JobSeeker\JobController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\JobSeeker\HomeController;
//JobSeeker
use App\Http\Controllers\JobSeeker\JobSeekerAuthController;
use App\Http\Controllers\JobSeeker\JobSeekerController;
use App\Http\Controllers\JobSeeker\JobSeekerProfileController;
use App\Http\Controllers\JobSeeker\ResumeController;
use App\Http\Controllers\Recruiter\RecruiterController;
use App\Models\CandidateSkillTest;

    //Home Routes
    Route::get('jobseeker/top_job_post',[HomeController::class,'top_job_post']);
    Route::get('jobseeker/best_companies',[HomeController::class,'best_companies']);
    Route::get('jobseeker/platform_review',[HomeController::class,'platform_review']);
    // Job Seeker API routes
    Route::post('jobseeker/login', [JobSeekerAuthController::class, 'login']);
    Route::post('jobseeker/register',[JobSeekerAuthController::class,'register']);
    Route::post('jobseeker/forgot_password', [JobSeekerController::class, 'forgot_password']);
    Route::post('jobseeker/reset_password',[JobSeekerController::class,'reset_password']);

    Route::middleware(['auth:api'])->group(function () {
      Route::post('jobseeker/logout', [AuthController::class, 'logout']);

       Route::get('jobseeker/profile', [JobSeekerAuthController::class, 'profile']);
       Route::post('jobseeker/personal_info',[JobSeekerProfileController::class,'personal_info']);
       Route::get('jobseeker/get_personal_info',[JobSeekerProfileController::class,'get_personal_info']);
       Route::post('jobseeker/submit_candidate_review',[JobSeekerProfileController::class,'submit_candidate_review']);
       Route::post('jobseeker/change_password',[JobSeekerAuthController::class,'change_password']);
       Route::post('jobseeker/update_first_login',[JobSeekerAuthController::class,'update_first_login']);


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
       Route::get('jobseeker/get_open_to_work',[JobSeekerProfileController::class,'get_open_to_work']);

       Route::get('jobseeker/check_profile_complete',[JobSeekerProfileController::class,'check_profile_complete']);

       Route::get('jobseeker/master_resume_json',[JobSeekerProfileController::class,'master_resume_json']);
       Route::post('jobseeker/generate_resume',[JobSeekerProfileController::class,'generate_resume']);
       Route::get('jobseeker/view_generate_resume',[JobSeekerProfileController::class,'view_generate_resume']);
       Route::post('jobseeker/get_resume_by_id',[JobSeekerProfileController::class,'get_resume_by_id']);
       Route::post('jobseeker/delete_generate_resume',[JobSeekerProfileController::class,'delete_generate_resume']);
       Route::post('jobseeker/generate_resume_by_jd',[JobSeekerProfileController::class,'generate_resume_by_jd']);

       //Job
       Route::post('jobseeker/job_list',[JobController::class,'job_list']);
       Route::post('jobseeker/job_list_filter',[JobController::class,'job_list_filter']);
       Route::post('jobseeker/submit_saved_job',[JobController::class,'submit_saved_job']);
       Route::get('jobseeker/my_saved_job',[JobController::class,'my_saved_job']);
       Route::post('jobseeker/get_job_details',[JobController::class,'get_job_details']);
       Route::post('jobseeker/apply_job',[JobController::class,'apply_job']);
       Route::post('jobseeker/get_job_round',[JobController::class,'get_job_round']);
       Route::post('jobseeker/check_job_post_notification',[JobController::class,'check_job_post_notification']);
       Route::post('jobseeker/update_job_post_notification',[JobController::class,'update_job_post_notification']);
       Route::post('jobseeker/prepare_for_job',[JobController::class,'prepare_for_job']);
       Route::post('jobseeker/auto_apply_job',[JobController::class,'auto_apply_job']);
       Route::get('jobseeker/get_auto_apply_job',[JobController::class,'get_auto_apply_job']);

       ///Skill Test
       Route::get('jobseeker/get_candidate_skills',[CandidateSkillController::class,'get_candidate_skills']);
       Route::post('jobseeker/candidate_skill_test',[CandidateSkillController::class,'candidate_skill_test']);
       Route::post('jobseeker/candidate_skill_test_que',[CandidateSkillController::class,'candidate_skill_test_que']);
       Route::post('jobseeker/submit_candidate_skill_test',[CandidateSkillController::class,'submit_candidate_skill_test']);
       Route::get('jobseeker/get_skill_test_score',[CandidateSkillController::class,'get_skill_test_score']);

       //Applied Job 
       Route::get('jobseeker/my_applied_jobs',[AppliedJobController::class,'my_applied_jobs']);
       Route::post('jobseeker/mcq_interview_instruction',[AppliedJobController::class,'mcq_interview_instruction']);
       Route::post('jobseeker/mcq_interview_questions',[AppliedJobController::class,'mcq_interview_questions']);
       Route::post('jobseeker/submit_mcq_interview_questions',[AppliedJobController::class,'submit_mcq_interview_questions']);
       Route::post('jobseeker/talk_interview',[AppliedJobController::class,'talk_interview']);
      Route::post('jobseeker/submit_mock_interview',[AppliedJobController::class,'submit_mock_interview']);

       Route::post('jobseeker/submit_ai_resume_analysis',[ResumeController::class,'submit_ai_resume_analysis']);

       //Company Profile
       Route::get('jobseeker/company_list',[HomeController::class,'company_list']);
       Route::post('jobseeker/company_details',[HomeController::class,'company_details']);

    });
