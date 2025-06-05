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
            ->orderBy('job_applications.created_at', 'desc')
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
                $job->interview_rounds = InterviewRound::select('interview_rounds.id as round_id', 'interview_rounds.round_name', 'interviews.status', 'interviews.interview_date as date', 'interviews.interview_link as link', 'interviews.interview_mode as mode', 'interviews.room_id')

                    ->leftJoin('interviews', function ($join) use ($job) {
                        // Apply the condition on the join to ensure left join behavior
                        $join->on('interviews.round_id', '=', 'interview_rounds.id')
                            ->where('interviews.job_application_id', $job->job_application_id)
                            ->where('interviews.jobseeker_id', $job->job_seeker_id);
                    })
                    ->whereIn('interview_rounds.id', $roundIds ?? [])
                    ->orderByRaw('FIELD(interview_rounds.id, ' . implode(',', $roundIds) . ')')
                    ->get();
                // ->map(function ($round) {
                //     // If status is null, set to 'Pending'
                //     $round->mode = 'Online';
                //     $round->status = $round->status ?? 'Pending';
                //     return $round;
                // });
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


            $data = array(
                'skill' => json_decode($check_job->skills_required),
                'company_name' => $check_job->name,
                'company_id' => $check_job->company_id,

                'total_question' => '30',
                'total_time' => '30 Mins'
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
            'bash_id' => 'required',
            'job_id' => 'required',
        ], [
            'job_application_id.required' => 'Job Application Id is required.',
            'bash_id.required' => 'Bash Id is required.',
            'job_id.required' => 'Job Id is required.'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

        $get_experience = JobSeekerContactDetails::select('jobs.ai_generate_question', 'job_applications.job_id', 'jobs.id', 'jobs.company_id', 'jobs.skills_required', 'job_seeker_contact_details.user_id', 'job_seeker_contact_details.total_year_exp')
            ->Join('job_applications', 'job_applications.job_seeker_id', '=', 'job_seeker_contact_details.user_id')
            ->Join('jobs', 'jobs.id', '=', 'job_applications.job_id')
            ->where('job_applications.job_seeker_id', '=', $auth->id)
            ->where('jobs.id', $request->job_id)
            ->where('job_applications.id', $request->job_application_id)
            ->where('job_applications.bash_id', $request->bash_id)
            ->first();

        if ($get_experience) {

            $skills = json_decode($get_experience->skills_required, true);

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

            $query = SkillAssQuestion::select('id', 'job_id', 'skill', 'skill_level', 'question', 'option1', 'option2', 'option3', 'option4', 'marks')
                ->where('skill_level', $level);

            // Filter questions based on skills
            $query->where(function ($query) use ($skills) {
                $stopWords = ['and', 'or', 'the', 'with', 'a', 'an', 'to', 'of', 'for'];

                foreach ($skills as $skill) {
                    $words = preg_split('/[\s,]+/', strtolower($skill));
                    foreach ($words as $word) {
                        if (!empty($word) && !in_array($word, $stopWords)) {
                            $query->orWhereRaw("LOWER(skill) LIKE ?", ['%' . $word . '%']);
                        }
                    }
                }
            });

            // Now check ai_generate_question flag
            if ($get_experience->ai_generate_question == 1) {
                // Filter by company and job
                // $get_experience->id;
                //  echo  $get_experience->id;
                $query->where('company_id', $get_experience->company_id);
                $query->where('job_id', $get_experience->job_id);
            }

            $get_que = $query->inRandomOrder()->limit(30)->get();
            // dd($get_que);
            return response()->json(['status' => true, 'message' => 'Candidate Skill Test Questions', 'data' => $get_que]);
        } else {
            return response()->json(['status' => false, 'message' => 'Skill not match.']);
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
            'company_id' => 'required',
            'round_id' => 'required',
            'answers' => 'array|required',


        ], [
            'job_application_id.required' => 'Job Application Id is required.',
            'company_id.required' => 'Company Id is required.',
            'round_id.required' => 'Round Id is required',
            'answers.required' => 'answers is required',

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

        $answers = [];
        $score = 0;
        $total = 0;
        // Handle file uploads
        $i = 1;
        foreach ($request->answers as $key => $answer) {
            $get_que = SkillAssQuestion::select('correct_answer', 'marks')->where('id', $answer['id'])->where('question', $answer['question'])->first();
            // Store JSON data with updated file path
            $total += $get_que->marks;
            if ($answer['answer'] == $get_que->correct_answer) {
                $valid_answer = true;
                $score += $get_que->marks;  // Add marks if the answer is correct
            }
            $answers[] = [

                "id" => $answer['id'] ? $answer['id'] : null,
                "question" => $answer['question'] ? $answer['question'] : null,
                "answer" => $answer['answer'] ? $answer['answer'] : null,
                "correct_answer" => $get_que->correct_answer ? $get_que->correct_answer : null,

            ];
            $i++;
        }

        $check_test = Interview::where('jobseeker_id', '=', $auth->id)->where('job_application_id', $request->job_application_id)->where('company_id', $request->company_id)->where('round_id', $request->round_id)->count();
        if ($check_test > 0) {
            $test = Interview::where('jobseeker_id', '=', $auth->id)->where('job_application_id', $request->job_application_id)->where('company_id', $request->company_id)->where('round_id', $request->round_id)->first();
            $test->score = $score;
            $test->total = $total;

            $test->status = 'Completed';
            $test->save();
            $jsonData = [
                'valid_answer' => $answers,
                'score' => $score,
                'total' => $total
            ];
            return response()->json(['status' => true, 'data' => $jsonData, 'message' => ' Submitted.']);
        }
    }


         public function submit_mock_interview(Request $request)
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
            'user_chat_id'=>'required',
          
        ], [
            'job_application_id.required' => 'Job Application Id is required.',
            'company_id.required'=>'Company Id is required.',
            'round_id.required' => 'Round Id is required',
           'user_chat_id.required'=>'user_chat_id is required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
          $check_test= Interview::where('jobseeker_id', '=', $auth->id)->where('job_application_id',$request->job_application_id)->where('company_id',$request->company_id)->where('round_id',$request->round_id)->count();
        if ($check_test>0)
        {
            $test= Interview::where('jobseeker_id', '=', $auth->id)->where('job_application_id',$request->job_application_id)->where('company_id',$request->company_id)->where('round_id',$request->round_id)->first();
           
                  $job_details=array("user_id"=>$request->user_chat_id,
             
               
               );
          
         
           $postData = http_build_query($job_details);
                
                $ch = curl_init();
                
                curl_setopt_array($ch, [
                    CURLOPT_URL => 'http://34.131.125.195:8000/final_report',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $postData, // 🟢 SEND AS FORM
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Content-Type: application/x-www-form-urlencoded', // 🧠 CRUCIAL HEADER
                    ],
                ]);
                
                $response = curl_exec($ch);
              curl_close($ch);
            
            if (curl_errno($ch)) {
                return response()->json([
                    'status' => false,
                    'message' => curl_error($ch),
                ]);
            }
            
            $responseData = json_decode($response, true);
           if (isset($responseData['report']))
           {
                $test->interview_report = $responseData['report'];
          
           }else{
                  $test->interview_report = '';
           }
           
            
             $test->status = 'Completed';
            $test->save();
            
            //clear Chat
             $user_chat_id=array("user_id"=>$request->user_chat_id );
          
         
           $postuserData = http_build_query($user_chat_id);
                
                $ch1 = curl_init();
                
                curl_setopt_array($ch1, [
                    CURLOPT_URL => 'http://34.131.125.195:8000/clear_chat',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $postuserData, // 🟢 SEND AS FORM
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Content-Type: application/json', // 🧠 CRUCIAL HEADER
                    ],
                ]);
                
                $response1 = curl_exec($ch1);
              curl_close($ch1);
              
            return response()->json(['status' => true, 'message' => ' Submitted.']);
        }else{
            return response()->json(['status' => false, 'message' => ' No round found.']);  
        }
    }
    
    public function talk_interview(Request $request)
    {
       $auth=JWTAuth::user();
       if(!$auth)
       {
           return response()->json([
               'status'=>false,
               'message'=>'Unauthorized'],401);
       }
       
        $validator = Validator::make($request->all(), [
            'job_application_id' => 'required',
            'job_application_bash_id'=>'required',
           // 'interview_id'=>'required',
            'message'=>'required',
            'user_chat_id'=>'required',
           
        ], [
            'job_application_id.required' => 'Job Application Id is required.',
         //   'interview_bash_id.required'=>'Bash Id is required.',
            'job_application_bash_id.required'=>'Bash Id is required.',
            'message.required'=>'Message is required.',
            'user_chat_id.required'=>'user_chat_id required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        
        $get_chat=Interview::select('jobs.job_title','users.bash_id','users.name','jobs.skills_required','jobs.experience_required')
        ->Join('job_applications','job_applications.id','=','interviews.job_application_id')
        ->Join('jobs','jobs.id','=','job_applications.job_id')
        ->Join('users','users.id','=','job_applications.job_seeker_id')
        ->where('job_applications.job_seeker_id','=',$auth->id)
        ->where('job_applications.id',$request->job_application_id)
        ->where('job_applications.bash_id',$request->job_application_bash_id)
        ->where('interviews.job_application_id',$request->job_application_id)
        ->first();
        if($get_chat)
        {
                $skills = json_decode($get_chat->skills_required, true);
                $skillsString = is_array($skills) ? implode(', ', $skills) : $get_chat->skills_required;
    
                  $job_details=array("user_id"=>$request->user_chat_id,
             
               "firstname"=>$get_chat->name,
              
               "skills"=>$skillsString,
              
              "role"=>$get_chat->job_title,
               "experience"=>$get_chat->experience_required,
               "message"=>$request->message,
              
               );
          
         
           $postData = http_build_query($job_details);
                
                $ch = curl_init();
                
                curl_setopt_array($ch, [
                    CURLOPT_URL => 'http://34.131.125.195:8000/talk',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $postData, // 🟢 SEND AS FORM
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Content-Type: application/x-www-form-urlencoded', // 🧠 CRUCIAL HEADER
                    ],
                ]);
                
                $response = curl_exec($ch);
              curl_close($ch);
            
            if (curl_errno($ch)) {
                return response()->json([
                    'status' => false,
                    'message' => curl_error($ch),
                ]);
            }
            
            $responseData = json_decode($response, true);
            
            if (!isset($responseData['session_id'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Session ID not found in response',
                ]);
            }
            
             $sessionId = $responseData['session_id'];
           sleep(5); // ⏱️ Delay added here
           $nextUrl = 'http://34.131.125.195:8000/get_audio/'.urlencode($request->user_chat_id);
            
                $ch = curl_init();
                curl_setopt_array($ch, [
                    CURLOPT_URL => $nextUrl,
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                    ],
                ]);
            
                $nextResponse = curl_exec($ch);
                curl_close($ch);
            
                $responseData2 = json_decode($nextResponse, true);
              
              
          //  $responseData2 = json_decode($nextResponse, true);
             return response()->json([
                    'status' => true,
                    'message' =>'Audio',
                     'data'=>$responseData2
                ]);
        }
    }
      public function talk_interview_test(Request $request)
    {
       $auth=JWTAuth::user();
       if(!$auth)
       {
           return response()->json([
               'status'=>false,
               'message'=>'Unauthorized'],401);
       }
       
        $validator = Validator::make($request->all(), [
           
            'role'=>'required',
            'message'=>'required',
            'skills'=>'array|required',
            'experience'=>'required',
            'user_chat_id'=>'required'
        ], [
            
            'role.required'=>'role is required.',
            'message.required'=>'Message is required.',
            'skills.required'=>'Skills required.',
            'experience.required'=>'Experience required.',
            'user_chat_id.required'=>'user_chat_id required.'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        
        $get_chat=User::select('users.bash_id','users.name','jobseeker_professional_details.skills','job_seeker_contact_details.total_year_exp','job_seeker_contact_details.total_month_exp')
        ->Join('jobseeker_professional_details','jobseeker_professional_details.user_id','=','users.id')
        ->Join('job_seeker_contact_details','job_seeker_contact_details.user_id','=','users.id')
        
        ->where('users.id','=',$auth->id)
        ->where('users.bash_id',$auth->bash_id)
       
        ->first();
        if($get_chat)
        {
                $skills = $request->skills;
                $skillsString = is_array($skills) ? implode(', ', $skills) : $request->skills;
   
                  $job_details=array("user_id"=>$request->user_chat_id,
             
               "firstname"=>$get_chat->name,
              
               "skills"=>$skillsString,
              
              "role"=>$request->role,
               "experience"=>$get_chat->total_year_exp.' '.$get_chat->total_month_exp,
               "message"=>$request->message,
              
               );
          
         
           $postData = http_build_query($job_details);
                
                $ch = curl_init();
                
                curl_setopt_array($ch, [
                    CURLOPT_URL => 'http://34.131.125.195:8000/talk',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $postData, // 🟢 SEND AS FORM
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Content-Type: application/x-www-form-urlencoded', // 🧠 CRUCIAL HEADER
                    ],
                ]);
                
                $response = curl_exec($ch);
              curl_close($ch);
            
            if (curl_errno($ch)) {
                return response()->json([
                    'status' => false,
                    'message' => curl_error($ch),
                ]);
            }
            
            $responseData = json_decode($response, true);
            
            if (!isset($responseData['session_id'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Session ID not found in response',
                ]);
            }
            
             $sessionId = $responseData['session_id'];
           sleep(5); 
           $nextUrl = 'http://34.131.125.195:8000/get_audio/'.urlencode($request->user_chat_id);
        
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $nextUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                ],
            ]);
        
            $nextResponse = curl_exec($ch);
            curl_close($ch);
        
            $responseData2 = json_decode($nextResponse, true);
            
            //$responseData2 = json_decode($nextResponse, true);
             return response()->json([
                    'status' => true,
                    'message' =>'Audio',
                     'data'=>$responseData2
                ]);
        }
    }
     public function mock_interview_test_report(Request $request)
    {
        $auth = JWTAuth::user();
       
        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
        $validator = Validator::make($request->all(), [
           
           
            'user_chat_id'=>'required'
        ], [
            
           
            'user_chat_id.required'=>'user_chat_id'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        
        
        $job_details=array("user_id"=>$request->user_chat_id,
         );
          
         
           $postData = http_build_query($job_details);
                
                $ch = curl_init();
                
                curl_setopt_array($ch, [
                    CURLOPT_URL => 'http://34.131.125.195:8000/final_report',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $postData, // 🟢 SEND AS FORM
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Content-Type: application/x-www-form-urlencoded', // 🧠 CRUCIAL HEADER
                    ],
                ]);
                
                $response = curl_exec($ch);
              curl_close($ch);
            
            if (curl_errno($ch)) {
                return response()->json([
                    'status' => false,
                    'message' => curl_error($ch),
                ]);
            }
            
            $responseData = json_decode($response, true);
                   if (isset($responseData['report'])) {
                return response()->json([
                    'status' => true,
                    'message' => 'Report fetched successfully.',
                    'data' => $responseData['report']
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Report not available.',
                    'data' => null
                ]);
            }
    }
    public function clear_mock_interview_test(Request $request)
    {
         $auth = JWTAuth::user();
       
        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
           
           
            'user_chat_id'=>'required'
        ], [
            
           
            'user_chat_id.required'=>'user_chat_id'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        
        
                  $job_details=array("user_id"=>$request->user_chat_id,
             
               
               );
          
         
           $postData = http_build_query($job_details);
                
                $ch = curl_init();
                
                curl_setopt_array($ch, [
                    CURLOPT_URL => 'http://34.131.125.195:8000/clear_chat',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => $postData, // 🟢 SEND AS FORM
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Content-Type: application/json', // 🧠 CRUCIAL HEADER
                    ],
                ]);
                
                $response = curl_exec($ch);
              curl_close($ch);
            
            if (curl_errno($ch)) {
                return response()->json([
                    'status' => false,
                    'message' => curl_error($ch),
                ]);
            }
            
           
                return response()->json([
                    'status' => true,
                    'message' => 'Chat Cleared.',
                
                ]);
        
    }
}
