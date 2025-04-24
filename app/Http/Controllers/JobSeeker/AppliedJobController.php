<?php
namespace App\Http\Controllers\JobSeeker;

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
use App\Models\Company;

use App\Models\SkillAssQuestion;
use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Hash;


class AppliedJobController extends Controller
{
    //

     public function my_applied_jobs()
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
        $jobs = JobApplication::select(
            'job_applications.id as job_application_id',
            'job_applications.bash_id',
            'job_applications.applied_at',
            'job_applications.status as application_status',
            'job_applications.job_seeker_id',
            'jobs.id as job_id',
            'jobs.round',
            'jobs.job_title',
            'jobs.job_type',
            'jobs.experience_required',
            'jobs.salary_range',

            'jobs.is_hot_job',
            'jobs.location as job_locations',
            'companies.company_logo',
            'companies.id as company_id',
            'companies.name as company_name',
            'companies.locations as company_locations',
            'jobs.created_at as job_post_date'
        )
            ->Join('jobs', 'jobs.id', '=', 'job_applications.job_id')
            ->leftJoin('companies', 'jobs.company_id', '=', 'companies.id')
            ->where('job_applications.job_seeker_id', '=', $auth->id)
            ->orderBy('job_applications.created_at','desc')
            ->get();
        if ($jobs) {
            $jobs->transform(function ($job) {
                $disk = env('FILESYSTEM_DISK'); // Default to 'local' if not set in .env

                // Modify the company logo to include the full URL if it exists
                if ($job->company_logo) {
                    if ($disk === 's3') {
                        // For S3, use Storage facade with the 's3' disk
                        $job->company_logo = Storage::disk('s3')->url($job->company_logo);
                    } else {
                        // Default to local
                        $job->company_logo = env('APP_URL') . Storage::url('app/public/' . $job->company_logo);
                    }
                } else {
                    // If no logo exists, set it to null or a default image URL
                    $job->company_logo = null; // Replace with a default image URL if needed
                }
                $job->job_locations = json_decode($job->job_locations, true);
                $job->company_locations = json_decode($job->company_locations, true);
                $roundIds = json_decode($job->round, true);

                // Fetch interview round details
                $job->interview_rounds = InterviewRound::select('interview_rounds.id as round_id', 'interview_rounds.round_name', 'interviews.status', 'interviews.interview_date as date', 'interviews.interview_mode as mode')

                    ->leftJoin('interviews', function ($join) use ($job) {
                        // Apply the condition on the join to ensure left join behavior
                        $join->on('interviews.round_id', '=', 'interview_rounds.id')
                            ->where('interviews.job_application_id', $job->job_application_id)
                            ->where('interviews.jobseeker_id', $job->job_seeker_id);
                    })
                    ->whereIn('interview_rounds.id', $roundIds ?? [])

                    ->get()
                    ->map(function ($round) {
                        // If status is null, set to 'Pending'
                        $round->mode = 'Online';
                        $round->status = $round->status ?? 'Pending';
                        return $round;
                    });
                return $job;
            });
            return response()->json([
                'status' => true,
                'message' => 'Candidate Skills.',
                'data' => $jobs
            ]);
        } else {
            return response()->json([
                'status' => true,
                'message' => 'Candidate Skills.',
                'data' => []
            ]);
        }
    }
    
     public function mcq_interview_instruction(Request $request)
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
            'bash_id' => 'required',
            'job_id' => 'required',
            'round_id' => 'required',
        ], [
            'job_application_id.required' => 'Job Application Id is required.',
            'bash_id.required' => 'Bash Id is required.',
            'job_id.required' => 'Job Id is required.',
            'round_id.required' => 'Round Id is required.'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $check_job = Jobs::select('jobs.company_id', 'jobs.id', 'companies.name', 'jobs.skills_required')
            ->Join('job_applications', 'job_applications.job_id', '=', 'jobs.id')
            ->Join('companies', 'companies.id', '=', 'jobs.company_id')
            ->where('jobs.id', $request->job_id)
            ->where('job_applications.id', $request->job_application_id)
            ->where('job_applications.bash_id', $request->bash_id)
            ->where('jobs.status', 'Active')
            ->where('jobs.active', '1')->first();
        if ($check_job) {
            $check_test = Interview::where('jobseeker_id', '=', $auth->id)->where('job_application_id', $request->job_application_id)->where('company_id', $check_job->company_id)->where('round_id', $request->round_id)->count();
            if ($check_test > 0) {
                $test_status = true;
            } else {
                $test_status = false;
            }

            $data = array(
                'skill' => json_decode($check_job->skills_required),
                'company_name' => $check_job->name,
                'company_id' => $check_job->company_id,
                'test_status' => $test_status,
                'total_question' => '10',
                'total_time' => '10 Mins'
            );
            return response()->json(['status' => true, 'message' => 'Mcq Interview Instrction', 'data' => $data]);
        } else {

            return response()->json([
                'status' => false,
                'message' => 'Job Status not Active.',
                'data' => []
            ]);
        }
    }
    
   public function mcq_interview_questions(Request $request)
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
            'bash_id'=>'required',
            'job_id'=>'required',
        ], [
            'job_application_id.required' => 'Job Application Id is required.',
            'bash_id.required'=>'Bash Id is required.',
            'job_id.required'=>'Job Id is required.'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        
          $get_experience=JobSeekerContactDetails::select('jobs.skills_required','job_seeker_contact_details.user_id','job_seeker_contact_details.total_year_exp')
        ->Join('job_applications','job_applications.job_seeker_id','=','job_seeker_contact_details.user_id')
        ->Join('jobs','jobs.id','=','job_applications.job_id')
        ->where('job_applications.job_seeker_id','=',$auth->id)
            ->where('jobs.id',$request->job_id)
         ->where('job_applications.id',$request->job_application_id)
          ->where('job_applications.bash_id',$request->bash_id)
        ->first();
        
        if($get_experience)
        {
      
           $skills = json_decode( $get_experience->skills_required, true);

        // OR if it’s comma-separated, do:
        if (!is_array($skills)) {
            $skills = array_map('trim', explode(',',  $get_experience->skills_required));
        }
                // Determine the experience level
                if ($get_experience->total_year_exp <= 1) {
                    $level = 'Basic';
                } elseif ($get_experience->total_year_exp > 1 && $get_experience->total_year_exp <= 6) {
                    $level = 'Medium';
                } else {
                    $level = 'High';
                }
            
                // Fetch questions matching any of the required skills at the appropriate level
                $get_que = SkillAssQuestion::select('id', 'skill', 'skill_level', 'question', 'option1', 'option2', 'option3', 'option4', 'correct_answer', 'marks')
                    ->where('skill_level', $level)
                    ->where(function ($query) use ($skills) {
                        foreach ($skills as $skill) {
                    $stopWords = ['and', 'or', 'the', 'with', 'a', 'an', 'to', 'of', 'for']; // Add more if needed

                    $words = preg_split('/[\s,]+/', strtolower($skill));
                    foreach ($words as $word) {
                        if (!empty($word) && !in_array($word, $stopWords)) {
                            $query->orWhereRaw("LOWER(skill) LIKE ?", ['%' . $word . '%']);
                        }
                    }
                }
                    })
                    
                    
             
                    ->inRandomOrder()
                    ->limit(10)
                    ->get();
            return response()->json(['status' => true, 'message' => 'Candidate Skill Test Questions' ,'data'=>$get_que]);
        }else{
            return response()->json(['status'=>false,'message'=>'Skill not match.']);

        }
    }
    
      public function submit_mcq_interview_questions(Request $request)
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
            'company_id'=>'required',
            'round_id' => 'required',
            'score'=>'required',
            'total'=>'required'
            
        ], [
            'job_application_id.required' => 'Job Application Id is required.',
            'company_id.required'=>'Company Id is required.',
            'round_id.required' => 'Round Id is required',
            'score.required'=>'Score is required',
            'total.required'=>'Total marks required'
           
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        
        $check_test= Interview::where('jobseeker_id', '=', $auth->id)->where('job_application_id',$request->job_application_id)->where('company_id',$request->company_id)->where('round_id',$request->round_id)->count();
        if ($check_test === 0) {

            $test = new Interview();
            $test->bash_id = Str::uuid();
            $test->job_application_id = $request->job_application_id;
            $test->jobseeker_id = $auth->id;
             $test->company_id = $request->company_id;
              $test->round_id = $request->round_id;
          
            $test->score = $request->score;
            $test->total = $request->total;
              $test->interview_date = date('Y-m-d H:i:s');
                $test->interview_mode = 'Online';
             $test->status = 'Completed';
          

            $test->save();
            return response()->json(['status' => true, 'message' => 'Test Submited.'], 200);
        } else {

            // $test= Interview::where('jobseeker_id', '=', $auth->id)->where('job_application_id',$request->job_application_id)->where('company_id',$request->company_id)->where('round_id',$request->round_id)->first();
            //  $test->score = $request->score;
            // $test->total = $request->total;
            //   $test->interview_date = date('Y-m-d H:i:s');
            //     $test->interview_mode = 'Online';
            //  $test->status = 'Completed';
            // $test->save();
            return response()->json(['status' => false, 'message' => 'already Submitted.']);
        }
    }
    
}
