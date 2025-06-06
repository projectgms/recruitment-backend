<?php

namespace App\Http\Controllers\Recruiter;
use Illuminate\Support\Facades\Notification;

use App\Http\Controllers\Controller;
use App\Models\CandidateSkillTest;
use App\Models\JobApplication;
use App\Models\Jobs;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Notifications\Recruiter\UpdateJobApplication;

use App\Models\User;
use App\Models\JobSeekerProfessionalDetails;
use App\Models\JobSeekerContactDetails;
use App\Models\Interview;
use App\Models\InterviewRound;

use App\Models\SkillAssQuestion;
use Illuminate\Support\Facades\Cache;
use Twilio\Rest\Client;
use App\Helpers\FileHelper;


use Illuminate\Support\Facades\Hash;

class InterviewController extends Controller
{
    //

     public function get_candidate_interview_status(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'job_application_id' => 'required',
            'bash_id'=>'required'
           
        ], [
            'job_application_id.required' => 'Job Application Id is required.',
            'bash_id.required'=>'Bash id is required'
           
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

        
       
            $get_interview = Interview::select(
                'interviews.id',
                'jobs.job_title',
                'users.name',
                'users.first_name',
                'users.middle_name',
                'users.last_name',
                'interviews.round_id',
                'interview_rounds.round_name',
                'interviews.score',
                'interviews.total as total_marks',
                'interviews.status',
                'interviews.interview_date',
                'interviews.interview_link',
                'interviews.room_id',
                'interviews.interview_mode'
            )
            ->join('job_applications', 'job_applications.id', '=', 'interviews.job_application_id')
            ->join('jobs', 'jobs.id', '=', 'job_applications.job_id')
            ->join('users', 'users.id', '=', 'interviews.jobseeker_id')
            ->join('interview_rounds', 'interview_rounds.id', '=', 'interviews.round_id')
            ->where('interviews.job_application_id', $request->job_application_id)
            ->where('job_applications.bash_id', $request->bash_id)
            ->get();
       
    
        return response()->json([
            'status' => true,
            'message' => 'Interview Round',
            'data' => $get_interview
        ]);
    }
    
    public function get_job_interview_status(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'job_id' => 'required',
            'bash_id'=>'required',
           'round_id'=>'required'
        ], [
            'job_id.required' => 'Job Id is required.',
            'bash_id.required'=>'Job Bash id is required',
            'round_id.required'=>'Round Id required'
           
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

   
   $candidates = Interview::select(
        'interviews.id',
         'jobs.job_title',
            'interviews.round_id',
             'interview_rounds.round_name',
            'interviews.score',
            'interviews.total as total_marks',
            'interviews.status',
            'interviews.interview_date',
            'interviews.interview_link',
            'interviews.interview_mode',
             'interviews.room_id',
                'interviews.feedback',
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
                'interviews.interviewer_id',
                'users.language_known',
                'job_applications.status as application_status',
                 'job_applications.id as job_application_id',
                'job_applications.bash_id as job_application_bash_id',
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
            )->Join('job_applications','interviews.job_application_id','=','job_applications.id')
           
        ->Join('interview_rounds', 'interview_rounds.id', '=', 'interviews.round_id')
                ->leftJoin('jobs', 'jobs.id', '=', 'job_applications.job_id')
                ->leftJoin('users', 'users.id', '=', 'job_applications.job_seeker_id')
                ->leftJoin('job_seeker_contact_details', 'users.id', '=', 'job_seeker_contact_details.user_id')
                ->leftJoin('jobseeker_education_details', 'users.id', '=', 'jobseeker_education_details.user_id')
                ->leftJoin('jobseeker_professional_details', 'users.id', '=', 'jobseeker_professional_details.user_id')
                ->where('jobs.id', $request->job_id)
                ->where('jobs.bash_id', $request->bash_id)
                   ->where('interviews.round_id', $request->round_id)
                ->get();

            $disk = env('FILESYSTEM_DISK', 'local');
            $urlPrefix = $disk === 's3' ? Storage::disk('s3') : Storage::disk('public');

            $candidates->transform(function ($candidate) use ($disk, $urlPrefix) {
                foreach (['certifications', 'publications', 'trainings', 'educations', 'experience', 'skills', 'projects', 'internship'] as $field) {
                    $candidate->{$field} = json_decode($candidate->{$field}, true);
                }
                $get_interviewer=User::select('name','email')->where('id',$candidate->interviewer_id)->first();
                if($get_interviewer)
                {
                    $candidate->interviewer_name=$get_interviewer->name;
                }else{
                    $candidate->interviewer_name='';
                }
                $candidate->open_to_work = $candidate->open_to_work == 1;

                  // Resume
                if ($candidate->resume) {
                     $candidate->resume =FileHelper::getFileUrl($candidate->resume);
                    
                }

                // Profile Picture
                if ($candidate->profile_picture) {
                      $candidate->profile_picture =FileHelper::getFileUrl($candidate->profile_picture);
                   
                }

                // Skill Test
                $candidate->skill_test = CandidateSkillTest::select('skill', 'score', 'total')
                    ->where('jobseeker_id', $candidate->job_seeker_id)
                    ->get();

                return $candidate;
            });

            return response()->json([
                'status' => true,
                'message' => 'Interview Round',
                'data' => $candidates
            ]);
    }
    
      public function update_candidate_interview_status(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'array|required',
            'status'=>'required',
            'round_id'=>'required'
            
        ], [
            'id.required' => 'Id is required.',
            'status.required'=>'Status is required.',
            'round_id.required'=>'Round Id is required',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
       
        $interview =Interview::whereIn('id',$request->id)->where('round_id',$request->round_id)->get();
        if($interview)
          {
               foreach ($interview as $interview) {

                $update_status = Interview::where('id', $interview->id)->where('round_id',$request->round_id)
                ->first();
          if($update_status)
          {
            
                $update_job_application = JobApplication::select('users.name','users.mobile','companies.name as company_name','jobs.round','companies.website','job_applications.job_seeker_id','jobs.company_id','users.id','jobs.job_title','users.email','job_applications.id as job_application_id', 'job_applications.status')
                    ->leftJoin('jobs', 'jobs.id', '=', 'job_applications.job_id')
                    ->leftJoin('users', 'users.id', '=', 'job_applications.job_seeker_id')
                   ->leftJoin('companies', 'companies.id', '=', 'jobs.company_id')
                   
                    ->where('job_applications.id', $update_status->job_application_id)
                    ->where('jobs.status','Active')
                    ->first();
                 
              if($update_job_application)
              {
                if ($request->status=='Selected' || $request->status=='Hired') {
                       $rounds = json_decode($update_job_application->round); // e.g., ["2","1","3"]
                    $currentIndex = array_search($request->round_id, $rounds);
                    
                    $nextRoundId = null;
                    if ($currentIndex !== false && isset($rounds[$currentIndex + 1])) {
                        $nextRoundId = $rounds[$currentIndex + 1];
                    }
                    
                    if($nextRoundId)
                    {
                      if($request->interview_date && $request->interview_mode )
                     { 
                         //update current round interview status to Selected
                        $update_status->status = $request->status;
                         $update_status->feedback=$request->feedback;
                       $update_status->save();
                       //add entry for new Interview round 
                      
                        $insert_new_round = new Interview();
                        $insert_new_round->bash_id = Str::uuid();
                        $insert_new_round->job_application_id = $update_status->job_application_id;
                        $insert_new_round->jobseeker_id =$update_status->jobseeker_id;
                        $insert_new_round->company_id =$update_status->company_id;
                        $insert_new_round->round_id = $nextRoundId;
                        $insert_new_round->score = 0;
                        $insert_new_round->total =10;
                        $insert_new_round->interview_date =  $request->interview_date;
                        $insert_new_round->interview_mode = $request->interview_mode;
                        $insert_new_round->interview_link = $request->interview_link;
                        $insert_new_round->room_id = $request->meeting_room_id;
                        $insert_new_round->interviewer_id= $request->interviewer_id ? $request->interviewer_id : 0;
                        $insert_new_round->status = 'Shortlisted';
                        $insert_new_round->save();
                        
                        //Update Job Application status
                        if($update_job_application)
                           {
                              JobApplication::where('id', $update_status->job_application_id)
                            ->update(['status' => $request->status]);

                           }
                     }else{
                           return response()->json([
                                        'status' => false,
                                        'message' => 'Interview Date and Mode is required.',
                                        
                                    ]); 
                     }
                        
                    }else{
                           //update current round interview status to Selected
                        $update_status->status = $request->status;
                         $update_status->feedback=$request->feedback;
                       $update_status->save();
                          if($update_job_application)
                           {
                              JobApplication::where('id', $update_status->job_application_id)
            ->update(['status' =>  $request->status]);

                           }
                        // return response()->json([
                        //     'status' => false,
                        //     'message' =>'Next Round Id not found.',
                           
                        // ]);  
                    }
                       
                   
                }else{
                    //update current round interview status 
                        //   if($request->status=='Scheduled')
                        //   {
                        //       $update_status->interview_mode = $request->interview_mode;
                        //       $update_status->interview_date = $request->interview_date;
                        //       $update_status->interview_link = $request->interview_link;
                        //   }
                           $update_status->status = $request->status;
                            $update_status->feedback=$request->feedback;
                           $update_status->save();
                           
                           //Update Job Application status
                           if($update_job_application)
                           {
                            JobApplication::where('id', $update_status->job_application_id)
                             ->update(['status' => $request->status]);

                   
                           }
                }
                 $get_round_name=InterviewRound::select('round_name')->where('id',$request->round_id)->first();
         
                        Notification::route('mail', $update_job_application->email)->notify(new UpdateJobApplication($update_job_application->name, $update_job_application->job_title, $update_job_application->company_name, $update_job_application->website, $request->status,$get_round_name->round_name,$update_status->interview_date,$update_status->interview_mode,$update_status->interview_link,$update_job_application->mobile));
                    
              }else{
                    return response()->json([
                            'status' => false,
                            'message' =>'This Job not Active.',
                           
                        ]);  
              }
          }
            }
     
        return response()->json([
            'status' => true,
            'message' =>'Interview Round Status Updated for particular status.',
           
        ]);
        }else{
            return response()->json([
                'status' => true,
                'message' =>'Interview Id not found.',
               
            ]);
        }
    }
    
     public function today_interview(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
        $today = Carbon::today()->toDateString();

        $today_interview = Interview::select('users.name','jobs.job_title','users.email','job_applications.id as job_application_id', 'interviews.status','interview_rounds.round_name','interviews.round_id','interviews.interview_date','interviews.interview_link','interviews.interview_mode')
        ->Join('job_applications', 'job_applications.id', '=', 'interviews.job_application_id')
       
        ->Join('jobs', 'jobs.id', '=', 'job_applications.job_id')
        ->Join('users', 'users.id', '=', 'interviews.jobseeker_id')
        ->Join('interview_rounds', 'interview_rounds.id', '=', 'interviews.round_id')
    
        ->where('interviews.company_id', $auth->company_id)
        ->where('interviews.status', 'Shortlisted')
       ->whereDate('interviews.interview_date', $today)
        ->where('jobs.status','Active')
       // ->where('jobs.expiration_date','>=',date('Y-m-d'))
        ->get();
        if($today_interview)
        {
            return response()->json([
                'status' => true,
                'message' =>'Today Interview List.',
                'data'=>$today_interview
               
            ]);
        }else{
            return response()->json([
                'status' => true,
                'message' =>'No any Interview.',
                'data'=>[]
               
            ]);
        }
    }
}
