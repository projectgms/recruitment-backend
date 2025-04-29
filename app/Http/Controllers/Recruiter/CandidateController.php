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
use App\Models\CandidateSkillTest;

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

        $job_application = JobApplication::select('users.name','companies.name as company_name','companies.website','users.id','jobs.job_title','users.email','job_applications.id as job_application_id', 'job_applications.status')
        ->leftJoin('jobs', 'jobs.id', '=', 'job_applications.job_id')
        ->leftJoin('users', 'users.id', '=', 'job_applications.job_seeker_id')
       ->leftJoin('companies', 'companies.id', '=', 'users.company_id')
       
        ->where('jobs.id', $request->job_id)
        ->whereIn('job_applications.id', $request->job_application_id)
        ->get();
    if ($job_application) {
        foreach ($job_application as $job_application) {
            
            $update_status = JobApplication::where('id', $job_application->job_application_id)
                ->where('job_id', $request->job_id)
                ->first();
            
            if ($update_status) {
                $update_status->status = $request->job_application_status;
                $update_status->save();
                if($request->job_application_status!=$job_application->status)
                {
                Notification::route('mail', $job_application->email)->notify(new UpdateJobApplication($job_application->name,$job_application->job_title,$job_application->company_name,$job_application->website,$request->job_application_status));
       
                }
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'Job Application Status .',
            
        ]);
    }
    }
    
       public function update_filter_job_applicant_test(Request $request)
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

        $job_application = JobApplication::select('users.name','companies.name as company_name','jobs.round','companies.website','job_applications.job_seeker_id','jobs.company_id','users.id','jobs.job_title','users.email','job_applications.id as job_application_id', 'job_applications.status')
        ->leftJoin('jobs', 'jobs.id', '=', 'job_applications.job_id')
        ->leftJoin('users', 'users.id', '=', 'job_applications.job_seeker_id')
       ->leftJoin('companies', 'companies.id', '=', 'users.company_id')
       
        ->where('jobs.id', $request->job_id)
        ->whereIn('job_applications.id', $request->job_application_id)
        ->get();
    if ($job_application) {
        foreach ($job_application as $job_application) {
            
            $update_status = JobApplication::where('id', $job_application->job_application_id)
                ->where('job_id', $request->job_id)
                ->first();
            
            if ($update_status) {
                $update_status->status = $request->job_application_status;
                $update_status->save();
                if($request->job_application_status!=$job_application->status)
                {
                    if($request->job_application_status=='Shortlisted')
                    {
                        
                                                $rounds = json_decode($job_application->round, true); // true => decode as array
                        
                        // Get the first round ID safely
                        $first_round_id = $rounds[0] ?? null; // null if not set
                        
                        if ($first_round_id) {
                            $check_round_already_present = Interview::select('round_id')
                                ->where('job_application_id', $job_application->job_application_id)
                                 ->where('jobseeker_id', $job_application->job_seeker_id)
                                  ->where('company_id', $job_application->company_id)
                                ->where('round_id', $first_round_id)
                                ->first();
                        
                            if (!$check_round_already_present) {
                                // If not present, create a new interview or whatever you want
                                  $test = new Interview();
                                $test->bash_id = Str::uuid();
                                $test->job_application_id = $job_application->job_application_id;
                                $test->jobseeker_id =$job_application->job_seeker_id;
                                 $test->company_id =$job_application->company_id;
                                  $test->round_id = $first_round_id;
                              
                                $test->score = 0;
                                $test->total =10;
                                  $test->interview_date = null;
                                    $test->interview_mode = '';
                                 $test->status = 'Pending';
                              
                    
                                $test->save();
                            }
                        }
                    }
             //   Notification::route('mail', $job_application->email)->notify(new UpdateJobApplication($job_application->name,$job_application->job_title,$job_application->company_name,$job_application->website,$request->job_application_status));
       
                }
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'Job Application Status .',
            
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
                    CURLOPT_URL => 'https://job-portal-recruiter-1.onrender.com/CANDIDATE_ANALYSIS',
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
            'job_applications.id',
            'job_applications.bash_id',
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
            'job_id.required' => 'Job Id is required.',
          
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
}
