<?php

namespace App\Http\Controllers\Recruiter;

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

use App\Models\User;
use App\Models\JobSeekerProfessionalDetails;
use App\Models\JobSeekerContactDetails;
use App\Models\Interview;
use App\Models\InterviewRound;

use App\Models\SkillAssQuestion;
use Illuminate\Support\Facades\Cache;

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
            'interviews.interview_mode',
            'job_applications.id as job_application_id',
            'job_applications.bash_id as job_application_bash_id'
        )
        ->join('job_applications', 'job_applications.id', '=', 'interviews.job_application_id')
        ->join('jobs', 'jobs.id', '=', 'job_applications.job_id')
        ->join('users', 'users.id', '=', 'interviews.jobseeker_id')
        ->join('interview_rounds', 'interview_rounds.id', '=', 'interviews.round_id')
        ->where('jobs.id', $request->job_id)
        ->where('jobs.bash_id', $request->bash_id)
        ->where('interviews.round_id', $request->round_id)
        ->get();
   

    return response()->json([
        'status' => true,
        'message' => 'Interview Round',
        'data' => $get_interview
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
            
        ], [
            'id.required' => 'Id is required.',
            'status.required'=>'Status is required.'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
       
        $interview =Interview::whereIn('id',$request->id)->get();
        if($interview)
          {
               foreach ($interview as $interview) {

                $update_status = Interview::where('id', $interview->id)
                  
                    ->first();

                if ($update_status) {
                    $update_status->status = $request->status;
                    $update_status->save();
                    // if ($request->job_application_status != $job_application->status) {
                    //     Notification::route('mail', $job_application->email)->notify(new UpdateJobApplication($job_application->name, $job_application->job_title, $job_application->company_name, $job_application->website, $request->job_application_status));
                    // }
                }
            }
     
        return response()->json([
            'status' => true,
            'message' =>'Interview Round Status Updated.',
           
        ]);
        }else{
            return response()->json([
                'status' => true,
                'message' =>'Interview Id not found.',
               
            ]);
        }
    }
}
