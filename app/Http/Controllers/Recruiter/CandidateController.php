<?php

namespace App\Http\Controllers\Recruiter;
use Illuminate\Support\Facades\Notification;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\CandidateApplicationAnalysis;

use Illuminate\Http\Request;
use App\Models\JobSeekerProfessionalDetails;
use Illuminate\Support\Facades\Storage;
use App\Notifications\Recruiter\UpdateJobApplication;
use App\Notifications\Recruiter\CandidateInvitation;
use App\Models\Company;

use App\Models\CandidateSkillTest;
use App\Models\RecruiterPrepareJob;
use App\Models\JobApplicationNotification;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Jobs;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Interview;
use App\Models\InterviewRound;
use Twilio\Rest\Client;
class CandidateController extends Controller
{
    //
     public function job_applicant(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }
        $validator = Validator::make($request->all(), [
            'job_id' => 'required',
            'bash_id' => 'required',

        ], [
            'job_id.required' => 'Job Id is required.',
            'bash_id.required' => 'Bash Id is required.',


        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
      
       
            $candidates = JobApplication::select(
                'users.open_to_work',
                'users.name',
                'users.first_name',
                'users.middle_name',
                'users.last_name',
                'users.email',
                'users.mobile',
                'users.location',
                'users.gender',
                'users.dob',
                'users.profile_picture',
                'users.marital_status',
                'users.medical_history',
                'users.disability',
                'users.language_known',
                'job_applications.status as application_status',
                'job_applications.id',
                'job_applications.bash_id',
                'job_applications.resume',
                'job_applications.job_seeker_id',
                'job_seeker_contact_details.country',
                'job_seeker_contact_details.state',
                'job_seeker_contact_details.city',
                'job_seeker_contact_details.zipcode',
                'job_seeker_contact_details.course',
                'job_seeker_contact_details.primary_specialization',
                'job_seeker_contact_details.dream_company',
                'job_seeker_contact_details.total_year_exp',
                'job_seeker_contact_details.total_month_exp',
                'job_seeker_contact_details.secondary_mobile',
                'job_seeker_contact_details.secondary_email',
                'job_seeker_contact_details.linkedin_url',
                'job_seeker_contact_details.github_url',
                'jobseeker_education_details.certifications',
                'jobseeker_education_details.publications',
                'jobseeker_education_details.trainings',
                'jobseeker_education_details.educations',
                'jobseeker_professional_details.experience',
                'jobseeker_professional_details.summary',
                'jobseeker_professional_details.skills',
                'jobseeker_professional_details.achievement',
                'jobseeker_professional_details.extra_curricular',
                'jobseeker_professional_details.projects',
                'jobseeker_professional_details.internship'
            )
                ->leftJoin('jobs', 'jobs.id', '=', 'job_applications.job_id')
                ->leftJoin('users', 'users.id', '=', 'job_applications.job_seeker_id')
                ->leftJoin('job_seeker_contact_details', 'users.id', '=', 'job_seeker_contact_details.user_id')
                ->leftJoin('jobseeker_education_details', 'users.id', '=', 'jobseeker_education_details.user_id')
                ->leftJoin('jobseeker_professional_details', 'users.id', '=', 'jobseeker_professional_details.user_id')
                ->where('jobs.id', $request->job_id)
                ->where('jobs.bash_id', $request->bash_id)
                ->where('job_applications.status','Applied')
                ->get();

            $disk = env('FILESYSTEM_DISK', 'local');
            $urlPrefix = $disk === 's3' ? Storage::disk('s3') : Storage::disk('public');

            $candidates->transform(function ($candidate) use ($disk, $urlPrefix) {
                foreach (['certifications', 'publications', 'trainings', 'educations', 'experience', 'skills', 'projects', 'internship'] as $field) {
                    $candidate->{$field} = json_decode($candidate->{$field}, true);
                }

                $candidate->open_to_work = $candidate->open_to_work == 1;

                // Resume
                if ($candidate->resume) {
                    $candidate->resume = $disk === 's3'
                        ? Storage::disk('s3')->url($candidate->resume)
                        : env('APP_URL') . Storage::url('app/public/' . $candidate->resume);
                }

                // Profile Picture
                if ($candidate->profile_picture) {
                    $candidate->profile_picture = $disk === 's3'
                        ? Storage::disk('s3')->url($candidate->profile_picture)
                        : env('APP_URL') . Storage::url('app/public/' . $candidate->profile_picture);
                }

                // Skill Test
                $candidate->skill_test = CandidateSkillTest::select('skill', 'score', 'total')
                    ->where('jobseeker_id', $candidate->job_seeker_id)
                    ->get();

                return $candidate;
            });

          

        return response()->json([
            'status' => true,
            'message' => 'Candidate Information.',
            'data' => $candidates
        ]);
    }
      public function hired_application(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }
        $validator = Validator::make($request->all(), [
            'job_id' => 'required',
            'bash_id' => 'required',

        ], [
            'job_id.required' => 'Job Id is required.',
            'bash_id.required' => 'Bash Id is required.',


        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
      
       
            $candidates = JobApplication::select(
                'users.open_to_work',
                'users.name',
                'users.first_name',
                'users.middle_name',
                'users.last_name',
                'users.email',
                'users.mobile',
                'users.location',
                'users.gender',
                'users.dob',
                'users.profile_picture',
                'users.marital_status',
                'users.medical_history',
                'users.disability',
                'users.language_known',
                'job_applications.status as application_status',
                'job_applications.id',
                'job_applications.bash_id',
                'job_applications.resume',
                'job_applications.job_seeker_id',
                'job_seeker_contact_details.country',
                'job_seeker_contact_details.state',
                'job_seeker_contact_details.city',
                'job_seeker_contact_details.zipcode',
                'job_seeker_contact_details.course',
                'job_seeker_contact_details.primary_specialization',
                'job_seeker_contact_details.dream_company',
                'job_seeker_contact_details.total_year_exp',
                'job_seeker_contact_details.total_month_exp',
                'job_seeker_contact_details.secondary_mobile',
                'job_seeker_contact_details.secondary_email',
                'job_seeker_contact_details.linkedin_url',
                'job_seeker_contact_details.github_url',
                'jobseeker_education_details.certifications',
                'jobseeker_education_details.publications',
                'jobseeker_education_details.trainings',
                'jobseeker_education_details.educations',
                'jobseeker_professional_details.experience',
                'jobseeker_professional_details.summary',
                'jobseeker_professional_details.skills',
                'jobseeker_professional_details.achievement',
                'jobseeker_professional_details.extra_curricular',
                'jobseeker_professional_details.projects',
                'jobseeker_professional_details.internship'
            )
                ->leftJoin('jobs', 'jobs.id', '=', 'job_applications.job_id')
                ->leftJoin('users', 'users.id', '=', 'job_applications.job_seeker_id')
                ->leftJoin('job_seeker_contact_details', 'users.id', '=', 'job_seeker_contact_details.user_id')
                ->leftJoin('jobseeker_education_details', 'users.id', '=', 'jobseeker_education_details.user_id')
                ->leftJoin('jobseeker_professional_details', 'users.id', '=', 'jobseeker_professional_details.user_id')
                ->where('jobs.id', $request->job_id)
                ->where('jobs.bash_id', $request->bash_id)
                ->where('job_applications.status','Hired')
                ->get();

            $disk = env('FILESYSTEM_DISK', 'local');
            $urlPrefix = $disk === 's3' ? Storage::disk('s3') : Storage::disk('public');

            $candidates->transform(function ($candidate) use ($disk, $urlPrefix) {
                foreach (['certifications', 'publications', 'trainings', 'educations', 'experience', 'skills', 'projects', 'internship'] as $field) {
                    $candidate->{$field} = json_decode($candidate->{$field}, true);
                }

                $candidate->open_to_work = $candidate->open_to_work == 1;

                // Resume
                if ($candidate->resume) {
                    $candidate->resume = $disk === 's3'
                        ? Storage::disk('s3')->url($candidate->resume)
                        : env('APP_URL') . Storage::url('app/public/' . $candidate->resume);
                }

                // Profile Picture
                if ($candidate->profile_picture) {
                    $candidate->profile_picture = $disk === 's3'
                        ? Storage::disk('s3')->url($candidate->profile_picture)
                        : env('APP_URL') . Storage::url('app/public/' . $candidate->profile_picture);
                }

                // Skill Test
                $candidate->skill_test = CandidateSkillTest::select('skill', 'score', 'total')
                    ->where('jobseeker_id', $candidate->job_seeker_id)
                    ->get();

                return $candidate;
            });

          

        return response()->json([
            'status' => true,
            'message' => 'Candidate Information.',
            'data' => $candidates
        ]);
    }
      public function total_job_application(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }
        $validator = Validator::make($request->all(), [
            'job_id' => 'required',
            'bash_id' => 'required',

        ], [
            'job_id.required' => 'Job Id is required.',
            'bash_id.required' => 'Bash Id is required.',


        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
      
       
            $candidates = JobApplication::select(
                'users.open_to_work',
                'users.name',
                'users.first_name',
                'users.middle_name',
                'users.last_name',
                'users.email',
                'users.mobile',
                'users.location',
                'users.gender',
                'users.dob',
                'users.profile_picture',
                'users.marital_status',
                'users.medical_history',
                'users.disability',
                'users.language_known',
                'job_applications.status as application_status',
                'job_applications.id',
                'job_applications.bash_id',
                'job_applications.resume',
                'job_applications.job_seeker_id',
                'job_seeker_contact_details.country',
                'job_seeker_contact_details.state',
                'job_seeker_contact_details.city',
                'job_seeker_contact_details.zipcode',
                'job_seeker_contact_details.course',
                'job_seeker_contact_details.primary_specialization',
                'job_seeker_contact_details.dream_company',
                'job_seeker_contact_details.total_year_exp',
                'job_seeker_contact_details.total_month_exp',
                'job_seeker_contact_details.secondary_mobile',
                'job_seeker_contact_details.secondary_email',
                'job_seeker_contact_details.linkedin_url',
                'job_seeker_contact_details.github_url',
                'jobseeker_education_details.certifications',
                'jobseeker_education_details.publications',
                'jobseeker_education_details.trainings',
                'jobseeker_education_details.educations',
                'jobseeker_professional_details.experience',
                'jobseeker_professional_details.summary',
                'jobseeker_professional_details.skills',
                'jobseeker_professional_details.achievement',
                'jobseeker_professional_details.extra_curricular',
                'jobseeker_professional_details.projects',
                'jobseeker_professional_details.internship'
            )
                ->leftJoin('jobs', 'jobs.id', '=', 'job_applications.job_id')
                ->leftJoin('users', 'users.id', '=', 'job_applications.job_seeker_id')
                ->leftJoin('job_seeker_contact_details', 'users.id', '=', 'job_seeker_contact_details.user_id')
                ->leftJoin('jobseeker_education_details', 'users.id', '=', 'jobseeker_education_details.user_id')
                ->leftJoin('jobseeker_professional_details', 'users.id', '=', 'jobseeker_professional_details.user_id')
                ->where('jobs.id', $request->job_id)
                ->where('jobs.bash_id', $request->bash_id)
               
                ->get();

            $disk = env('FILESYSTEM_DISK', 'local');
            $urlPrefix = $disk === 's3' ? Storage::disk('s3') : Storage::disk('public');

            $candidates->transform(function ($candidate) use ($disk, $urlPrefix) {
                foreach (['certifications', 'publications', 'trainings', 'educations', 'experience', 'skills', 'projects', 'internship'] as $field) {
                    $candidate->{$field} = json_decode($candidate->{$field}, true);
                }

                $candidate->open_to_work = $candidate->open_to_work == 1;

                // Resume
                if ($candidate->resume) {
                    $candidate->resume = $disk === 's3'
                        ? Storage::disk('s3')->url($candidate->resume)
                        : env('APP_URL') . Storage::url('app/public/' . $candidate->resume);
                }

                // Profile Picture
                if ($candidate->profile_picture) {
                    $candidate->profile_picture = $disk === 's3'
                        ? Storage::disk('s3')->url($candidate->profile_picture)
                        : env('APP_URL') . Storage::url('app/public/' . $candidate->profile_picture);
                }

                // Skill Test
                $candidate->skill_test = CandidateSkillTest::select('skill', 'score', 'total')
                    ->where('jobseeker_id', $candidate->job_seeker_id)
                    ->get();

                return $candidate;
            });

          

        return response()->json([
            'status' => true,
            'message' => 'Candidate Information.',
            'data' => $candidates
        ]);
    }
     public function smart_search_candidate(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }
        $validator = Validator::make($request->all(), [
            'skills' => 'array|required',
            
        ], [
            'skills.required' => 'Job Title is required.',
           
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

     
    // Convert the skills to an array (either JSON or comma-separated)
    $jobSeekerSkills = $request->skills;
    if (!is_array($jobSeekerSkills)) {
        $jobSeekerSkills = array_map('trim', explode(',', $request->skills));
    }
        $query = User::select(
            'users.open_to_work', 'users.id', 'users.name', 'users.first_name', 'users.middle_name',
            'users.last_name', 'users.email', 'users.mobile', 'users.location', 'users.gender',
            'users.dob', 'users.marital_status', 'users.medical_history', 'users.disability', 'users.language_known',
            'job_seeker_contact_details.country', 'job_seeker_contact_details.state', 'job_seeker_contact_details.city',
            'job_seeker_contact_details.zipcode', 'job_seeker_contact_details.course',
            'job_seeker_contact_details.primary_specialization', 'job_seeker_contact_details.dream_company',
            'job_seeker_contact_details.total_year_exp', 'job_seeker_contact_details.total_month_exp',
            'job_seeker_contact_details.secondary_mobile', 'job_seeker_contact_details.secondary_email',
            'job_seeker_contact_details.linkedin_url', 'job_seeker_contact_details.github_url',
            'jobseeker_education_details.certifications', 'jobseeker_education_details.publications',
            'jobseeker_education_details.trainings', 'jobseeker_education_details.educations',
            'jobseeker_professional_details.experience', 'jobseeker_professional_details.summary',
            'jobseeker_professional_details.skills', 'jobseeker_professional_details.achievement',
            'jobseeker_professional_details.extra_curricular', 'jobseeker_professional_details.projects',
            'jobseeker_professional_details.internship'
        )
        ->leftJoin('job_seeker_contact_details', 'users.id', '=', 'job_seeker_contact_details.user_id')
        ->leftJoin('jobseeker_education_details', 'users.id', '=', 'jobseeker_education_details.user_id')
        ->leftJoin('jobseeker_professional_details', 'users.id', '=', 'jobseeker_professional_details.user_id')
      
        ->where('users.active', 1);
       

        $query->where(function ($sub) use ($jobSeekerSkills) {
            foreach ($jobSeekerSkills as $skill) {
                $stopWords = ['and', 'or', 'the', 'with', 'a', 'an', 'to', 'of', 'for'];
                $words = preg_split('/[\s,]+/', strtolower($skill));
                foreach ($words as $word) {
                    if (!empty($word) && !in_array($word, $stopWords)) {
                        $sub->orWhereRaw("LOWER(jobseeker_professional_details.skills) LIKE ?", ["%{$word}%"]);
                    }
                }
            }
        });
        // if ($request->filled('search')) {
        //     $jobTitle = strtolower($request->search);
        //     $query->orWhereRaw("LOWER(jobs.job_title) LIKE ?", ['%' . $jobTitle . '%']);
        // }
      if ($request->filled('location') && is_array($request->location)) {
            $locations = array_map('strtolower', $request->location);
        
            $query->where(function ($subQuery) use ($locations) {
                foreach ($locations as $loc) {
                    $subQuery->orWhereRaw("LOWER(users.location) LIKE ?", ["%{$loc}%"]);
                }
            });
        }
        if ($request->filled('min_experience') && $request->filled('max_experience')) {
        $minExp = (int) $request->min_experience;
        $maxExp = (int) $request->max_experience;
    
        $query->WhereBetween('job_seeker_contact_details.total_year_exp', [$minExp, $maxExp]);
    } elseif ($request->filled('min_experience')) {
        $minExp = (int) $request->min_experience;
        $query->Where('job_seeker_contact_details.total_year_exp', '>=', $minExp);
    } elseif ($request->filled('max_experience')) {
        $maxExp = (int) $request->max_experience;
        $query->Where('job_seeker_contact_details.total_year_exp', '<=', $maxExp);
    }
         

         $candidates=$query->get()->transform(function ($candidate) {
            foreach (['certifications', 'publications', 'trainings', 'educations', 'experience', 'skills', 'projects', 'internship'] as $field) {
                $candidate->{$field} = json_decode($candidate->{$field}, true);
            }
            $candidate->open_to_work = (bool) $candidate->open_to_work;
            return $candidate;
        });
   

    return response()->json([
        'status' => true,
        'message' => ' Candidate Information.',
        'data' => $candidates
    ]);
        
    }

   public function open_to_work(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }
        $validator = Validator::make($request->all(), [
            'job_id' => 'required',
            'bash_id' => 'required',

        ], [
            'job_id.required' => 'Job Id is required.',
            'bash_id.required' => 'Bash Id is required.',


        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        
            $get_job_skills = Jobs::select('skills_required')
                ->where('id', $request->job_id)
                ->where('bash_id', $request->bash_id)
                ->first();
    
            $jobSkills = json_decode($get_job_skills->skills_required, true);
            if (!is_array($jobSkills)) {
                $jobSkills = array_map('trim', explode(',', $get_job_skills->skills_required));
            }
    
            $query = User::select(
                'users.open_to_work', 'users.id', 'users.name', 'users.first_name', 'users.middle_name',
                'users.last_name', 'users.email', 'users.mobile', 'users.location', 'users.gender',
                'users.dob', 'users.marital_status', 'users.medical_history', 'users.disability', 'users.language_known',
                'job_seeker_contact_details.country', 'job_seeker_contact_details.state', 'job_seeker_contact_details.city',
                'job_seeker_contact_details.zipcode', 'job_seeker_contact_details.course',
                'job_seeker_contact_details.primary_specialization', 'job_seeker_contact_details.dream_company',
                'job_seeker_contact_details.total_year_exp', 'job_seeker_contact_details.total_month_exp',
                'job_seeker_contact_details.secondary_mobile', 'job_seeker_contact_details.secondary_email',
                'job_seeker_contact_details.linkedin_url', 'job_seeker_contact_details.github_url',
                'jobseeker_education_details.certifications', 'jobseeker_education_details.publications',
                'jobseeker_education_details.trainings', 'jobseeker_education_details.educations',
                'jobseeker_professional_details.experience', 'jobseeker_professional_details.summary',
                'jobseeker_professional_details.skills', 'jobseeker_professional_details.achievement',
                'jobseeker_professional_details.extra_curricular', 'jobseeker_professional_details.projects',
                'jobseeker_professional_details.internship'
            )
            ->leftJoin('job_seeker_contact_details', 'users.id', '=', 'job_seeker_contact_details.user_id')
            ->leftJoin('jobseeker_education_details', 'users.id', '=', 'jobseeker_education_details.user_id')
            ->leftJoin('jobseeker_professional_details', 'users.id', '=', 'jobseeker_professional_details.user_id')
            ->where('users.open_to_work', 1)
            ->where('users.active', 1)
            ->whereNotIn('users.id', function ($query) use ($request) {
                $query->select('job_seeker_id')
                      ->from('job_applications')
                      ->where('job_id', $request->job_id);
            });
    
            $query->where(function ($sub) use ($jobSkills) {
                foreach ($jobSkills as $skill) {
                    $stopWords = ['and', 'or', 'the', 'with', 'a', 'an', 'to', 'of', 'for'];
                    $words = preg_split('/[\s,]+/', strtolower($skill));
                    foreach ($words as $word) {
                        if (!empty($word) && !in_array($word, $stopWords)) {
                            $sub->orWhereRaw("LOWER(jobseeker_professional_details.skills) LIKE ?", ["%{$word}%"]);
                        }
                    }
                }
            });
    
             $candidates=$query->get()->transform(function ($candidate) {
                foreach (['certifications', 'publications', 'trainings', 'educations', 'experience', 'skills', 'projects', 'internship'] as $field) {
                    $candidate->{$field} = json_decode($candidate->{$field}, true);
                }
                $candidate->open_to_work = (bool) $candidate->open_to_work;
                return $candidate;
            });
       
    
        return response()->json([
            'status' => true,
            'message' => 'Open to Work Candidate Information.',
            'data' => $candidates
        ]);
    }

   
    
       public function update_filter_job_applicant(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }
        $validator = Validator::make($request->all(), [
            'job_application_id' => 'array|required',
            'job_application_status' => 'required',
            'job_id' => 'required'

        ], [
            'job_id.required' => 'Job Id is required.',
            'job_application_id.required' => 'Job Application Id is required.',
            'job_application_status' => 'Job Application Status required.'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

        $job_application = JobApplication::select('users.name','users.mobile','companies.name as company_name','jobs.round','companies.website','job_applications.job_seeker_id','jobs.company_id','users.id','jobs.job_title','users.email','job_applications.id as job_application_id', 'job_applications.status')
        ->leftJoin('jobs', 'jobs.id', '=', 'job_applications.job_id')
        ->leftJoin('users', 'users.id', '=', 'job_applications.job_seeker_id')
       ->leftJoin('companies', 'companies.id', '=', 'jobs.company_id')
       
        ->where('jobs.id', $request->job_id)
        ->whereIn('job_applications.id', $request->job_application_id)
        ->where('jobs.status','Active')
        ->get();
    if ($job_application) {
        foreach ($job_application as $job_application) {
            
            $update_status = JobApplication::where('id', $job_application->job_application_id)
                ->where('job_id', $request->job_id)
                ->first();
            
            if ($update_status) {
              
                if($request->job_application_status!=$job_application->status && $job_application->status=='Applied')
                {
                     
                        $rounds = json_decode($job_application->round, true); // true => decode as array
                        
                        // Get the first round ID safely
                        $first_round_id = $rounds[0] ?? null; // null if not set
                        
                        $get_round_name=InterviewRound::select('round_name')->where('id',$first_round_id)->first();
                    if($request->job_application_status=='Shortlisted')
                    {
                      
                        if ($first_round_id) {
                            $check_round_already_present = Interview::select('round_id')
                                ->where('job_application_id', $job_application->job_application_id)
                                 ->where('jobseeker_id', $job_application->job_seeker_id)
                                  ->where('company_id', $job_application->company_id)
                                ->where('round_id', $first_round_id)
                                ->first();
                        
                            if (!$check_round_already_present) {
                                // If not present, create a new interview or whatever you want
                                if($request->interview_date && $request->interview_mode )
                                {
                                       $update_status->status = $request->job_application_status;
                                        $update_status->save();
                        
                                  $test = new Interview();
                                $test->bash_id = Str::uuid();
                                $test->job_application_id = $job_application->job_application_id;
                                $test->jobseeker_id =$job_application->job_seeker_id;
                                $test->company_id =$job_application->company_id;
                                $test->round_id = $first_round_id;
                                
                                $test->score = 0;
                                $test->total =10;
                                $test->interview_date =  $request->interview_date;
                                $test->interview_mode = $request->interview_mode;
                                 $test->interview_link = $request->interview_link;
                                $test->status = $request->job_application_status;
                              
                    
                                $test->save();
                                }else{
                                      return response()->json([
                                        'status' => false,
                                        'message' => 'Interview Date and Mode is required.',
                                        
                                    ]); 
                                }
                                
                                
                            }
                        }
                    }
                    
                    Notification::route('mail', $job_application->email)->notify(new UpdateJobApplication($job_application->name,$job_application->job_title,$job_application->company_name,$job_application->website,$request->job_application_status,$get_round_name->round_name,$request->interview_date?$request->interview_date:'',$request->interview_mode?$request->interview_mode:'',$request->interview_link?$request->interview_link:'',$job_application->mobile));

                }
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'Job Application Status .',
            
        ]);
    }else{
          return response()->json([
            'status' => false,
            'message' => 'Job Status not active.',
            
        ]); 
    }
    }
    
   public function candidate_analysis_by_jd(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }
        $validator = Validator::make($request->all(), [
            'job_application_id' => 'required',
            'job_id' => 'required'

        ], [
            'job_id.required' => 'Job Id is required.',
            'job_application_id.required' => 'Job Application Id is required.',
           
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $check_analysis=CandidateApplicationAnalysis::select('id','ai_analysis')->where('job_application_id',$request->job_application_id)->first();
        if($check_analysis)
        {
          return response()->json([
                  'status' => true,
                  'message' =>'Already analysis',
                  'data'=>json_decode($check_analysis->ai_analysis),
              ]);
           
        }else{
            $job_application = Jobs::select('job_applications.job_seeker_id','jobs.job_title','jobs.location','jobs.job_description','jobs.responsibilities','jobs.skills_required','jobs.status','jobs.salary_range','jobs.industry','jobs.job_type','jobs.contact_email','jobs.experience_required','jobs.is_hot_job','jobs.expiration_date','jobs.expiration_time','job_applications.id as job_application_id','job_applications.resume_json', 'job_applications.status')
            ->Join('job_applications', 'job_applications.job_id', '=', 'jobs.id')
           
            ->where('jobs.id', $request->job_id)
            ->where('job_applications.id', $request->job_application_id)
            ->first();
           
           $jd=array("title"=>$job_application->job_title,
         
           "locations"=>$job_application->location,
           "description"=>$job_application->job_description,
           "responsibilities"=>$job_application->responsibilities,
           "skills"=>$job_application->skills_required,
           "status"=>$job_application->status,
           "salary"=>$job_application->salary_range,
           "industries"=>$job_application->industry,
           "employmentType"=>$job_application->job_type,
           "email"=>$job_application->contact_email,
           "experience"=>$job_application->	experience_required,
           "hotJob"=>$job_application->is_hot_job,
           "expirationDate"=>$job_application->expiration_date,
           
           "expirationTime"=>$job_application->expiration_time
           );
           
            $ch = curl_init();
                
             $resumeArray = is_string($job_application->resume_json)
                ? json_decode($job_application->resume_json, true)
                : $job_application->resume_json;
            
            $jsonData = [
                'jd' => $jd,
                'resume' => $resumeArray
            ];
    
           
                curl_setopt_array($ch, [
                    CURLOPT_URL => 'https://job-recruiter.onrender.com/CANDIDATE_ANALYSIS',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($jsonData), 
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Content-Type: application/json', // This is CRITICAL
                    ],
                ]);
                
                $response = curl_exec($ch);
                
               
           
                if (curl_errno($ch)) {
                     return response()->json([
                    'status' => false,
                    'message' =>curl_error($ch),
                    
                ]);
                  
                }
                $resume=new CandidateApplicationAnalysis();
                $resume->bash_id=Str::uuid();
                $resume->jobseeker_id=$job_application->job_seeker_id;
                $resume->job_application_id=$request->job_application_id;
                $resume->ai_analysis=$response;
                $resume->save();
                 curl_close($ch);
              
            
                return response()->json([
                    'status' => true,
                    'message' =>'Candidate Analysis',
                     'data'=>json_decode($response)
                ]);
        }
            
    }
    
     public function job_questions(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }
        $validator = Validator::make($request->all(), [
            'job_application_id' => 'required',
            'job_id' => 'required'

        ], [
            'job_id.required' => 'Job Id is required.',
            'job_application_id.required' => 'Job Application Id is required.',
           
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

        $job_application = JobApplication::select('job_applications.job_seeker_id','jobs.job_title','jobs.location','jobs.job_description','jobs.responsibilities','jobs.skills_required','jobs.status','jobs.salary_range','jobs.industry','jobs.job_type','jobs.contact_email','jobs.experience_required','jobs.is_hot_job','jobs.expiration_date','jobs.expiration_time','job_applications.id as job_application_id','job_applications.resume_json', 'job_applications.status')
        ->Join('jobs', 'jobs.id', '=', 'job_applications.job_id')
       
        ->where('job_applications.job_id', $request->job_id)
        ->where('job_applications.id', $request->job_application_id)
        ->first();

            if (!$job_application) {
                return response()->json([
                    'status' => false,
                    'message' => 'Resume not found',
                ], 404);
            }
            
        $jd=array("title"=>$job_application->job_title,

        "locations"=>$job_application->location,
        "description"=>$job_application->job_description,
        "responsibilities"=>$job_application->responsibilities,
        "skills"=>$job_application->skills_required,
        "status"=>$job_application->status,
        "salary"=>$job_application->salary_range,
        "industries"=>$job_application->industry,
        "employmentType"=>$job_application->job_type,
        "email"=>$job_application->contact_email,
        "experience"=>$job_application->	experience_required,
        "hotJob"=>$job_application->is_hot_job,
        "expirationDate"=>$job_application->expiration_date,
        
        "expirationTime"=>$job_application->expiration_time
        );

 $check_prepare_job = RecruiterPrepareJob::select('qa_output')->where('job_application_id', $request->job_application_id)
                ->where('company_id', $auth->company_id)
                ->where('job_id',$request->job_id)
                ->first();
        
            if ($check_prepare_job) {
                return response()->json([
                    'status' => true,
                    'message' => 'Already Prepare',
                    'data' =>json_decode($check_prepare_job->qa_output), 
                ]);
            }

            $ch = curl_init();
                
            $resumeArray = is_string($job_application->resume_json)
                ? json_decode($job_application->resume_json, true)
                : $job_application->resume_json;
            
            $jsonData = [
                'jd' => $jd,
                'resume' => $resumeArray
            ];


        curl_setopt_array($ch, [
            CURLOPT_URL => 'https://job-recruiter.onrender.com/GENERATE_QUESTIONS',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($jsonData), 
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/json', // This is CRITICAL
            ],
        ]);

        $response = curl_exec($ch);



        if (curl_errno($ch)) {
                return response()->json([
                'status' => false,
                'message' =>curl_error($ch),
                
            ]);
  
         }

            curl_close($ch);
            $decoded = json_decode($response, true);
                                    
            if (isset($decoded['questions'])) {
                $decoded = $this->transformQaOutput($decoded['questions']);
                unset($decoded['questions']); // Optional: remove the raw string
            }
     $ai = new RecruiterPrepareJob();
                $ai->bash_id = Str::uuid();
                $ai->company_id = $auth->company_id;
                $ai->job_id=$request->job_id;
                $ai->job_application_id = $request->job_application_id;
                $ai->qa_output = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $ai->save();

            return response()->json([
                'status' => true,
                'message' =>'Candidate Analysis',
                'data'=>$decoded
            ]);
    }

    private function transformQaOutput($qaText)
    {
        $lines = preg_split("/\r\n|\n|\r/", $qaText);
        $qa = [];
        $summary = '';
        $currentQ = '';
        $currentA = '';
        $category = '';
        $inSummary = true;
    
        foreach ($lines as $line) {
            $line = trim($line);
    
            // Skip empty lines
            if ($line === '') continue;
    
            // Section headings
            if (preg_match('/^\*\*(.*?)\*\*$/', $line, $matches)) {
                $category = $matches[1];
                continue;
            }
    
            // Summary before questions
            if ($inSummary && preg_match('/^\d+\.\s*Q:/', $line)) {
                $inSummary = false; // questions are starting
            }
    
            if ($inSummary) {
                $summary .= ' ' . $line;
                continue;
            }
    
            if (preg_match('/^\d+\.\s*Q:\s*(.*)$/i', $line, $matches)) {
                if ($currentQ && $currentA) {
                    $qa[] = [
                        'question' => trim($currentQ),
                        'answer' => trim($currentA),
                        'category' => $category
                    ];
                    $currentA = '';
                }
                $currentQ = $matches[1];
            } elseif (preg_match('/^A:\s*(.*)$/i', $line, $matches)) {
                $currentA = $matches[1];
            } elseif (!empty($line)) {
                $currentA .= ' ' . $line;
            }
        }
    
        // Push last Q&A
        if ($currentQ && $currentA) {
            $qa[] = [
                'question' => trim($currentQ),
                'answer' => trim($currentA),
                'category' => $category
            ];
        }
    
        return [
            'summary' => trim($summary),
            'qa' => $qa
        ];
    }
    
   public function recent_application()
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }

      
        $job_candidate= JobApplication::select(
            'users.name',
            'users.first_name',
            'users.middle_name',
            'users.last_name',
            'users.email',
            'users.mobile',
            'job_applications.status as application_status',
            'job_applications.id as job_application_id',
            'job_applications.bash_id as job_application_bash_id',
            'jobs.id as job_id',
            'jobs.job_title'
        )
            ->leftJoin('jobs', 'jobs.id', '=', 'job_applications.job_id')
            ->leftJoin('users', 'users.id', '=', 'job_applications.job_seeker_id')
            ->where('jobs.company_id', $auth->company_id)
            ->where('job_applications.status', 'Applied')
            ->orderBy('job_applications.id', 'desc')
            ->limit(5)
            ->get();
  

    return response()->json([
        'status' => true,
        'message' => 'Candidate Information.',
        'data' => $job_candidate
    ]);

    }
    
     public function view_job_application_notification(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json(
                [
                    'status' => false,
                    'message' => 'Unauthorized',
                ],
                401
            );
        }
      
    
            $notifications = JobApplicationNotification::select(
                'id',
                'bash_id',
                'job_id',
                'job_application_id',
                'company_id',
                'type',
                'message',
                'created_at',
                'is_read'
            )
                ->where('company_id', $auth->company_id)
                ->orderBy('id', 'desc')
                ->get();
        

        return response()->json([
            'status' => true,
            'message' => 'Job Application Notification',
            'data' => $notifications
        ]);
    }
    
      public function update_job_application_notification(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'job_application_id' => 'required', 
           
            'id'=>'required',
           
        ], [
            'job_application_id.required' => 'Job Application Id is required.',
          
            'id.required'=>'Id is required'
          
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
                
            ], 422);
        }
        $status=JobApplicationNotification::where('job_application_id',$request->job_application_id)->where('company_id',$auth->company_id)
            ->where('id',$request->id)->first();
        if($status)
        {
            $status->is_read='1';
            $status->save();
        }
         return response()->json([
                'status' => true,
                'message' => 'Job Application Notification Status Changed.',
               
            ]);
    }

     public function smart_search_invitation(Request $request)
    {
         $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'job_title' => 'required', 
            'location'=>'array|required',
            'skill'=>'array|required',
            'name'=>'required',
            'email'=>'required'
           
        ], [
            'job_title.required' => 'Job Title is required.',
            'location.required'=>'Location is required.',
            'skill.required'=>'Skill is required.',
            'name.required'=>'Name is required.',
            'email.required'=>'Email is required.'
          
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
                
            ], 422);
        }
        $get_company=Company::select('name','website')->where('id',$auth->company_id)->where('active','1')->first();
          Notification::route('mail', $request->email)->notify(new CandidateInvitation($request->name,$request->job_title,$get_company->name,$get_company->website,$request->location,$request->skill));

    }
}
