<?php

namespace App\Http\Controllers\Recruiter;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\JobApplication;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\InterviewRound;
use App\Models\JobPostNotification;
use App\Models\GenerateResume;
use App\Models\JobSeekerProfessionalDetails;
use App\Models\Jobs;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

use Illuminate\Support\Str;
use App\Models\SkillAssQuestion;
use Illuminate\Support\Facades\Cache;
use App\Models\JobApplicationNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use function Ramsey\Uuid\v1;

class JobPostController extends Controller
{
    //

   public function check_interview_questions(Request $request)
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
            'skills_required'=>'array|required',
            'round'=>'array|required',
            'experience_required' => 'required',
           
        ], [
           
            'skills_required.required' => 'Skill is required.',
             'round.required'=>'Round is required',
            'experience_required' => 'experience is required.',
           
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        if($request->experience_required<=1)
        {
            $skill_level='Basic';
        }else if($request->experience_required>1 && $request->experience_required<=6)
        {
            $skill_level='Medium';
        }
        else{
            $skill_level='High'; 
        }
        $jobSkills =  $request->skills_required;


 
        $skillKey = implode('_', array_map('strtolower', $jobSkills));
      
       
            $missingSkills = [];
    
            foreach ($jobSkills as $skill) {
                $questionCount = SkillAssQuestion::whereRaw('LOWER(skill) LIKE ?', ['%' . strtolower($skill) . '%'])
                    ->where('skill_level', $skill_level)
                    ->count();
    
                if ($questionCount < 20) {
                    $missingSkills[] = [
                        'skill' => $skill,
                        'available' => $questionCount,
                        'required' => 20,
                        'skill_level' => $skill_level
                    ];
                }
            }
    
            if (count($missingSkills) > 0) {
                $sample_question_file = env('APP_URL') . Storage::url('app/public/interview_questions/Mcq-interview-questions.xlsx');
    
                return [
                    'status' => false,
                    'message' => 'Each skill must have at least 20 questions.',
                    'data' => [
                        'missing_skills' => $missingSkills,
                        'sample_questions' => $sample_question_file
                    ]
                ];
            }
    
    
        return response()->json( [
            'status' => true,
            'message' => 'Sufficient questions are available for each skill.'
        ]);

        
    }
    public function add_job_post(Request $request)
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
            'company_id'=>'required',
            'user_id'=>'required',
            'job_title' => 'required',
            'location' => 'array|required',
            'industry' => 'array|required',
            'contact_email' => 'required',
            'job_description' => 'required',
            'skills_required' => 'array|required',
            'status' => 'required',
            'salary_range' => 'required',
          //  'is_hot_job' => 'required',
            'expiration_date' => 'required',
            'expiration_time' => 'required',
            'job_type' => 'required',
            'round'=>'array|required',
            'experience_required' => 'required',
            'responsibilities' => 'required'

        ], [
            'company_id.required' => 'Company Id is required.',
            'user_id.required' => 'User Id is required.',
            'job_title.required' => 'Job Title is required.',
            'location.required' => 'Location is required.',
            'industry.required' => 'Industry is required.',
            'contact_email.required' => 'Email is required.',
            'job_description.required' => 'Job Description is required.',
            'skills_required.required' => 'Skill is required.',
            'status.required' => 'Status is required.',
            'salary_range.required' => 'Salary is required.',
            'expiration_date' => 'Expiration Date is required.',
            'expiration_time' => 'Expiration Time is required.',
            'job_type.required' => 'Job Type is required.',
              'round.required'=>'Round is required',
            'experience_required' => 'experience is required.',
            'responsibilities.required' => 'Responsibilities is required.'

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
          
      
        $encodedSkills = json_encode($request->skills_required);

        $check_job = Jobs::select('id')->where('company_id', $request->company_id)->where('job_title', $request->job_title)->where('experience_required', $request->experience_required)->where('skills_required', $encodedSkills)->count();
        if ($check_job == 0) {
 if ($request->hasFile('interview_questions'))
            {
                $file = $request->file('interview_questions');

                $data = Excel::toCollection(null, $file)[0]; // Load the first sheet
        
            // 4. Loop through rows (skip header)
            if($data->count()>20 )
            {
            $i=0;
            foreach ($data as  $row) {
                // Optional: Debug each row
             
                if($i>1)
                {
                   
                    $existing = SkillAssQuestion::where('question', trim($row[2]))->exists();

                if (!$existing) {
                    SkillAssQuestion::create([
                        'skill'          => $row[0],
                        'skill_level'    => $row[1],
                        'question'       => $row[2],
                        'option1'        => $row[3],
                        'option2'        => $row[4],
                        'option3'        => $row[5],
                        'option4'        => $row[6],
                        'correct_answer' => $row[7],
                    ]);
                }
                }
                  $i++;
            }
        }else{
            return response()->json(['status' => false, 'message' => 'Upload Manimum 20 questions for each skills']);
        }
    }
            $jobs = new Jobs();
            $jobs->bash_id = Str::uuid();
            $jobs->company_id = $request->company_id;
            $jobs->user_id = $request->user_id;
            $jobs->job_title = $request->job_title;

            $jobs->job_description = $request->job_description;
            $jobs->job_type = $request->job_type;
            $jobs->location = json_encode($request->location);
             $jobs->round = json_encode($request->round);

            $jobs->contact_email = $request->contact_email;
            $jobs->salary_range = $request->salary_range;
            $jobs->skills_required = json_encode($request->skills_required);
            $jobs->ai_generate_question=$request->ai_generate_question;
            $jobs->industry = json_encode($request->industry);

            $jobs->experience_required = $request->experience_required;
            $jobs->status = $request->status;
         //   $jobs->is_hot_job = $request->is_hot_job;
            $jobs->expiration_date = $request->expiration_date;
            $jobs->expiration_time = $request->expiration_time;
            $jobs->responsibilities = $request->responsibilities;
            $jobs->save();
            
             $job_post_notification=new JobPostNotification();
            $job_post_notification->bash_id = Str::uuid();
            $job_post_notification->job_id =$jobs->id;
            $job_post_notification->company_id =$request->company_id;
            $job_post_notification->type='New Job Post';
            $job_post_notification->message='New Job post added for the role '.$request->job_title;
            $job_post_notification->save();

            $data=[
                'job_id'=>$jobs->id,
                'job_bash_id'=>$jobs->bash_id,
                'company_id'=> $jobs->company_id,
                'ai_generate_question'=> $jobs->ai_generate_question
                ];
            return response()->json(['status' => true, 'data'=>$data,'message' => 'New Job Post Added.'], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'Job post already added']);
        }
    }
  public function recent_job_post()
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

       
          
        $job_post=Jobs::select(
                        'jobs.user_id', 'jobs.id', 'jobs.round', 'jobs.bash_id', 'jobs.job_title',
                        'jobs.job_description', 'jobs.job_type', 'jobs.location', 'jobs.contact_email',
                        'jobs.salary_range', 'jobs.skills_required', 'jobs.industry', 'jobs.experience_required',
                        'jobs.status', 'jobs.is_hot_job', 'jobs.expiration_date', 'jobs.expiration_time',
                        'jobs.responsibilities', 'jobs.created_at', 'companies.name as company_name', 'jobs.company_id'
                    )
                    ->Join('companies', 'companies.id', '=', 'jobs.company_id')
                    ->where('jobs.company_id', $auth->company_id)
                    ->orderBy('jobs.created_at','desc')
                    ->limit(10)
                    ->get()
                    ->map(function ($job) {
                        $user = User::select('name')->where('id', $job->user_id)->first();
                        return [
                            'id' => $job->id,
                            'bash_id' => $job->bash_id,
                            'job_title' => $job->job_title,
                            'job_description' => $job->job_description,
                            'job_type' => $job->job_type,
                            'location' => json_decode($job->location, true),
                            'contact_email' => $job->contact_email,
                            'salary_range' => $job->salary_range,
                            'skills_required' => json_decode($job->skills_required, true),
                            'industry' => json_decode($job->industry, true),
                            'experience_required' => $job->experience_required,
                            'round' => json_decode($job->round, true),
                            'status' => $job->status,
                            'is_hot_job' => $job->is_hot_job,
                            'expiration_date' => $job->expiration_date,
                            'expiration_time' => $job->expiration_time,
                            'responsibilities' => $job->responsibilities,
                            'created_at' => $job->created_at,
                            'company_name' => $job->company_name,
                            'company_id' => $job->company_id,
                            'added_by' => $user ? $user->name : null
                        ];
                    });
           

        return response()->json([
            'status' => true,
            'message' => 'Get Job Posts.',
            'data' => $job_post
        ]);
    }
    public function view_job_post(Request $request)
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
            'company_id'=>'required',
          

        ], [
            'company_id.required' => 'Company Id is required.',
          
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

           
          
        $job_post=Jobs::select(
                        'jobs.user_id', 'jobs.id', 'jobs.ai_generate_question','jobs.round', 'jobs.bash_id', 'jobs.job_title',
                        'jobs.job_description', 'jobs.job_type', 'jobs.location', 'jobs.contact_email',
                        'jobs.salary_range', 'jobs.skills_required', 'jobs.industry', 'jobs.experience_required',
                        'jobs.status', 'jobs.expiration_date', 'jobs.expiration_time',
                        'jobs.responsibilities', 'jobs.created_at', 'companies.name as company_name', 'jobs.company_id'
                    )
                    ->join('companies', 'companies.id', '=', 'jobs.company_id')
                    ->where('jobs.company_id', $request->company_id)
                       ->orderBy('jobs.created_at','desc')
                    ->get()
                    ->map(function ($job) {
                         $expirationDate = Carbon::parse($job->expiration_date)->startOfDay();
                         $currentDate = Carbon::now()->startOfDay();
                
                        $daysDifference = $currentDate->diffInDays($expirationDate, false); 
                       $isHotJob= ($daysDifference >= 0 && $daysDifference <= 15) ? 'Yes' : 'No';
                        $user = User::select('name')->where('id', $job->user_id)->first();
                        return [
                            'id' => $job->id,
                            'bash_id' => $job->bash_id,
                            'job_title' => $job->job_title,
                            'job_description' => $job->job_description,
                            'job_type' => $job->job_type,
                            'location' => json_decode($job->location, true),
                            'contact_email' => $job->contact_email,
                            'salary_range' => $job->salary_range,
                            'skills_required' => json_decode($job->skills_required, true),
                            'industry' => json_decode($job->industry, true),
                            'experience_required' => $job->experience_required,
                            'round' => json_decode($job->round, true),
                            'status' => $job->status,
                            'is_hot_job' => $isHotJob,
                            'ai_generate_question' => $job->ai_generate_question,
                            'expiration_date' => $job->expiration_date,
                            'expiration_time' => $job->expiration_time,
                            'responsibilities' => $job->responsibilities,
                            'created_at' => $job->created_at,
                            'company_name' => $job->company_name,
                            'company_id' => $job->company_id,
                            'added_by' => $user ? $user->name : null
                        ];
                    });
           

        return response()->json([
            'status' => true,
            'message' => 'Get Job Posts.',
            'data' => $job_post
        ]);
    }

    public function update_job_post(Request $request)
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
            'id'=>'required',
            'company_id'=>'required',
            'user_id'=>'required',
            'job_title' => 'required',
            'location' => 'array|required',
            'industry' => 'array|required',
            'contact_email' => 'required',
            'job_description' => 'required',
            'skills_required' => 'array|required',
            'status' => 'required',
            'salary_range' => 'required',
           // 'is_hot_job' => 'required',
            'expiration_date' => 'required',
            'expiration_time' => 'required',
            'job_type' => 'required',
            'round'=>'required', 
            'experience_required' => 'required',
            'responsibilities' => 'required'

        ], [
            'id.required' => 'Id is required.',
            'company_id'=>'required',
            'user_id'=>'required',
            'job_title.required' => 'Job Title is required.',
            'location.required' => 'Location is required.',
            'industry.required' => 'Industry is required.',
            'contact_email.required' => 'Email is required.',
            'job_description.required' => 'Job Description is required.',
            'skills_required.required' => 'Skill is required.',
            'status.required' => 'Status is required.',
            'salary_range.required' => 'Salary is required.',
            'expiration_date' => 'Expiration Date is required.',
            'expiration_time' => 'Expiration Time is required.',
            'job_type.required' => 'Job Type is required.',
            'experience_required.required' => 'experience is required.',
            'responsibilities' => 'Responsibilities is required.',
            'round.required'=>'Round is required.'

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $encodedSkills = json_encode($request->skills_required);

        $check_job = Jobs::select('id')->where('company_id', $request->company_id)->where('job_title', $request->job_title)->where('experience_required', $request->experience_required)->where('skills_required', $encodedSkills)->count();

        if ($check_job <=1) {
             if ($request->hasFile('interview_questions'))
            {
                $file = $request->file('interview_questions');

                $data = Excel::toCollection(null, $file)[0]; // Load the first sheet
        
            // 4. Loop through rows (skip header)
            if($data->count()>20 )
            {
            $i=0;
            foreach ($data as  $row) {
                // Optional: Debug each row
             
               $existing = SkillAssQuestion::where('question', trim($row[2]))->exists();

                if (!$existing) {
                    SkillAssQuestion::create([
                        'skill'          => $row[0],
                        'skill_level'    => $row[1],
                        'question'       => $row[2],
                        'option1'        => $row[3],
                        'option2'        => $row[4],
                        'option3'        => $row[5],
                        'option4'        => $row[6],
                        'correct_answer' => $row[7],
                    ]);
                }
                  $i++;
            }
        }else{
            return response()->json(['status' => false, 'message' => 'Upload Manimum 20 questions for each skills']);
        }
    }

            $jobs =Jobs::find($request->id);
          
            $jobs->job_title = $request->job_title;

            $jobs->job_description = $request->job_description;
            $jobs->job_type = $request->job_type;
            $jobs->location = json_encode($request->location);

            $jobs->contact_email = $request->contact_email;
            $jobs->salary_range = $request->salary_range;
            $jobs->skills_required = json_encode($request->skills_required);
 $jobs->ai_generate_question=$request->ai_generate_question;
            $jobs->industry = json_encode($request->industry);
            $jobs->round = json_encode($request->round);
            $jobs->experience_required = $request->experience_required;
            $jobs->status = $request->status;
           // $jobs->is_hot_job = $request->is_hot_job;
            $jobs->expiration_date = $request->expiration_date;
            $jobs->expiration_time = $request->expiration_time;
            $jobs->responsibilities = $request->responsibilities;
            $jobs->save();
           
            $data=[
                'job_id'=>$jobs->id,
                'job_bash_id'=>$jobs->bash_id,
                'company_id'=> $jobs->company_id,
                'ai_generate_question'=> $jobs->ai_generate_question
                ];
            return response()->json(['status' => true, 'data'=>$data,'message' => 'New Job Post Added.'], 200);
          
        } else {
            return response()->json(['status' => false, 'message' => 'Job post already added']);
        }
    }

public function pin_job(Request $request)
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
            'id'=>'required',
            'company_id'=>'required',
            'is_pin'=>'required'
           
        ], [
            'id.required' => 'Id is required.',
            'company_id.required'=>'required',
        
            'is_pin.required'=>'Is Pin required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        
          $jobs =Jobs::find($request->id);
          
           
            $jobs->is_pin = $request->is_pin;
            $jobs->save();
         return response()->json(['status' => true, 'message' => ' Job Pin status Updated.'], 200);
}


public function get_pin_job(Request $request)
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
            'company_id'=>'required',
          

        ], [
            'company_id.required' => 'Company Id is required.',
          
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

           
          
        $job_post=Jobs::select(
                        'jobs.user_id', 'jobs.id', 'jobs.round', 'jobs.bash_id', 'jobs.job_title',
                        'jobs.job_description', 'jobs.job_type', 'jobs.location', 'jobs.contact_email',
                        'jobs.salary_range', 'jobs.skills_required', 'jobs.industry', 'jobs.experience_required',
                        'jobs.status', 'jobs.is_hot_job', 'jobs.expiration_date', 'jobs.expiration_time','jobs.is_pin',
                        'jobs.responsibilities', 'jobs.created_at', 'companies.name as company_name', 'jobs.company_id'
                    )
                    ->join('companies', 'companies.id', '=', 'jobs.company_id')
                    ->where('jobs.company_id', $request->company_id)
                      ->where('jobs.is_pin', 'Yes')
                       ->orderBy('jobs.created_at','desc')
                    ->get()
                    ->map(function ($job) {
                        $user = User::select('name')->where('id', $job->user_id)->first();
                        return [
                            'id' => $job->id,
                            'bash_id' => $job->bash_id,
                            'job_title' => $job->job_title,
                            'job_description' => $job->job_description,
                            'job_type' => $job->job_type,
                            'location' => json_decode($job->location, true),
                            'contact_email' => $job->contact_email,
                            'salary_range' => $job->salary_range,
                            'skills_required' => json_decode($job->skills_required, true),
                            'industry' => json_decode($job->industry, true),
                            'experience_required' => $job->experience_required,
                            'round' => json_decode($job->round, true),
                            'status' => $job->status,
                            'is_hot_job' => $job->is_hot_job,
                            'expiration_date' => $job->expiration_date,
                            'expiration_time' => $job->expiration_time,
                              'is_pin' => $job->is_pin,
                            'responsibilities' => $job->responsibilities,
                            'created_at' => $job->created_at,
                            'company_name' => $job->company_name,
                            'company_id' => $job->company_id,
                            'added_by' => $user ? $user->name : null
                        ];
                    });
           

        return response()->json([
            'status' => true,
            'message' => 'Get Job Posts.',
            'data' => $job_post
        ]);
}

    public function delete_job_post(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'bash_id'=>'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }

        $delete_job_post = Jobs::where('id', $request->id)
        ->where('bash_id', $request->bash_id)
        ->first();

        // Check if job post exists
        if (!$delete_job_post) {
        return response()->json(['status' => false, 'message' => 'Job post not found'], 404);
        }

        // Mark job post as inactive
        $delete_job_post->active = 0;
        $delete_job_post->save();

        return response()->json(['status' => true, 'message' => 'Job post deleted successfully'], 200);

    }

public function get_interview_round()
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }
        
                $interview_rounds = InterviewRound::select('id as interview_round_id', 'round_name')
                    ->where('active', '1')
                    ->get();
           
        return response()->json([
            'status' => true,
            'message' => 'Get Interview Rounds.',
            'data' => $interview_rounds
        ]);
    }
    
      public function get_job_post_count(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'period' => 'required',
            
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }
        $period = $request->period; // Accepts 'weekly', 'quarterly', 'halfyear', or 'yearly'

    // Define the period filters based on the requested period
    $periodFilters = [
        'weekly' => Carbon::now()->subWeek(),
         'monthly' => Carbon::now()->subMonth(),
        'quarterly' => Carbon::now()->subMonths(3),
        'halfyear' => Carbon::now()->subMonths(6),
        'yearly' => Carbon::now()->subYear(),
    ];

   

    $periodStart = $periodFilters[$period];

    // Get the job posts along with the application count based on the period
    $job_post = Jobs::select('jobs.id', 'jobs.job_title')
        ->where('jobs.company_id', $auth->company_id)
        ->where('active', '1')
        ->get()
        ->map(function ($job) use ($periodStart) {
            // Count applications based on the selected period
            $application_count = JobApplication::where('job_id', $job->id)
                ->where('created_at', '>=', $periodStart) // Filter applications by the period
                ->count();

            // Only return the job if it has applications within the period
            if ($application_count > 0) {
                return [
                    'job_title' => $job->job_title,
                    'application_count' => $application_count,
                ];
            }

            return null; // Return null if no applications match the period
        })
        ->filter() // Remove null results
        ->values() // Re-index the collection
        ->toArray();

    return response()->json([
        'status' => true,
        'message' => 'Get Job Posts Count for Period.',
        'data' => $job_post
    ]);
    }
      public function salary_insights(Request $request)
    {
         $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }
    
     
          $job_post = Jobs::select('id', 'job_title', 'salary_range')
                ->where('company_id', $auth->company_id)
                ->where('active', '1')
                ->orderBy('salary_range', 'desc')
                ->limit(3)
                ->get();
     

        return response()->json([
            'status' => true,
            'message' => 'Get Job Salary Insight.',
            'data' => $job_post
        ]);
    }
    
    
    public function auto_apply_job_application(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'job_id' => 'required',
            'job_bash_id' => 'required',
            'company_id' => 'required'

        ], [
            'job_id.required' => 'Job Id is required.',
            'job_bash_id.required' => 'Job Bash Id is required',
            'company_id.required' => 'Company Id is required',

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
          ///after subscription remove this exit
  return response()->json([
                            'status' => true,
                            'message' => 'Job Applied.',

                        ]);
        exit;
        $job_post = Jobs::select('id', 'bash_id', 'job_title', 'salary_range', 'skills_required', 'experience_required')
            ->where('company_id', $auth->company_id)
            ->where('active', '1')
            ->where('id', $request->job_id)
            ->where('bash_id', $request->job_bash_id)
            ->where('jobs.status', 'Active')
            ->where('jobs.expiration_date', '>=', date('Y-m-d'))
            ->first();
        if ($job_post) {

            $jobSkills = json_decode($job_post->skills_required, true);
            if (!is_array($jobSkills)) {
                $jobSkills = array_map('trim', explode(',', $job_post->skills_required));
            }

            $stopWords = ['and', 'or', 'the', 'with', 'a', 'an', 'to', 'of', 'for'];
            preg_match('/\d+/', $job_post->experience_required, $match);
            $requiredExperience = isset($match[0]) ? (int)$match[0] : 0;

            $get_auto_apply_candidate = JobSeekerProfessionalDetails::select(
                'users.id',
                'users.name',
                 'users.email',
                   'users.mobile',
                'jobseeker_professional_details.skills',
                'job_seeker_contact_details.total_year_exp',
                'job_seeker_contact_details.total_month_exp',
                'jobseeker_professional_details.auto_apply_resume_id'
            )
                ->leftJoin('job_seeker_contact_details', 'job_seeker_contact_details.user_id', '=', 'jobseeker_professional_details.user_id')
                ->leftJoin('users', 'users.id', 'jobseeker_professional_details.user_id')
                ->where('jobseeker_professional_details.auto_apply_job', '1')
                ->where(function ($query) use ($jobSkills, $stopWords) {
                    foreach ($jobSkills as $skill) {
                        $words = preg_split('/[\s,]+/', strtolower($skill));
                        foreach ($words as $word) {
                            if (!empty($word) && !in_array($word, $stopWords)) {
                                $query->orWhereRaw("LOWER(jobseeker_professional_details.skills) LIKE ?", ['%' . $word . '%']);
                            }
                        }
                    }
                })
                ->whereRaw('(job_seeker_contact_details.total_year_exp + job_seeker_contact_details.total_month_exp / 12) >= ?', [$requiredExperience])
                ->where('users.active', '1')
                ->get();

            if ($get_auto_apply_candidate) {
                foreach ($get_auto_apply_candidate as $get_auto_apply_candidate) {
                    $check_job = JobApplication::where('job_id', '=', $request->job_id)->where('job_seeker_id', '=', $get_auto_apply_candidate->id)->first();

                    if ($check_job) {

                        continue;
                    } else {
                        $resume = GenerateResume::select('resume', 'resume_json')->where('user_id', $get_auto_apply_candidate->id)->where('id', $get_auto_apply_candidate->auto_apply_resume_id)->first();
                        if ($resume) {
                            // Modify the company logo to include the full URL if it exists
                            if ($resume->resume) {
                                $resume_url = $resume->resume;
                            } else {
                                // If no logo exists, set it to null or a default image URL
                                $resume_url = null; // Replace with a default image URL if needed
                            }
                            $resume_json = $resume->resume_json;
                        } else {
                            $resume_url = null;
                            $resume_json = '';
                        }
                        $apply = new JobApplication();
                        $apply->bash_id = Str::uuid();
                        $apply->job_id = $request->job_id;
                        $apply->job_seeker_id = $get_auto_apply_candidate->id;
                        $apply->status = 'Applied';
                        $apply->resume = $resume_url;
                        $apply->resume_json = $resume_json;
                        $apply->save();
                        $get_recruiter_contact = Company::select('users.mobile','jobs.contact_email','companies.name as company_name','companies.website')->Join('jobs', 'jobs.company_id', '=', 'companies.id')->Join('users', 'users.id', '=', 'companies.user_id')->where('jobs.id', $request->job_id)->first();
                        //send to recruiter
                        Notification::route('mail', $get_recruiter_contact->contact_email)->notify(new \App\Notifications\JobSeeker\UpdateJobApplication($get_auto_apply_candidate->name, $job_post->job_title, $get_auto_apply_candidate->email,$get_recruiter_contact->mobile));
                        //send to jobseeker
                        Notification::route('mail', $get_auto_apply_candidate->email)->notify(new \App\Notifications\JobSeeker\JobSeekerJobUpdate($get_auto_apply_candidate->name, $get_auto_apply_candidate->mobile,$job_post->job_title, $get_recruiter_contact->contact_email,$get_recruiter_contact->mobile,$get_recruiter_contact->company_name,$get_recruiter_contact->website));

                        $job_application_notification = new JobApplicationNotification();
                        $job_application_notification->bash_id = Str::uuid();
                        $job_application_notification->job_id = $request->job_id;
                        $job_application_notification->job_application_id = $apply->id;
                        $job_application_notification->company_id = $request->company_id;
                        $job_application_notification->jobseeker_id = $get_auto_apply_candidate->id;
                        $job_application_notification->type = 'Job Application';
                        $job_application_notification->message = 'New Job application added for the role ' . $job_post->job_title;
                        $job_application_notification->is_read = '0';
                        $job_application_notification->save();

                        return response()->json([
                            'status' => true,
                            'message' => 'Job Applied.',

                        ]);
                    }
                }
            } else {
                return response()->json([
                    'status' => true,
                    'message' => 'Your Skill and Experience not match this job..',

                ]);
            }
        }
    }
    
     public function ai_generate_question(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'job_id' => 'required',
            'job_bash_id' => 'required',
            'company_id' => 'required',
            'ai_generate_question'=>'required'

        ], [
            'job_id.required' => 'Job Id is required.',
            'job_bash_id.required' => 'Job Bash Id is required',
            'company_id.required' => 'Company Id is required',
            'ai_generate_question'=>'Ai Generate Question '

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
      
         $job_post = Jobs::select('id', 'ai_generate_question','bash_id', 'job_title', 'salary_range', 'skills_required', 'experience_required')
            ->where('company_id', $auth->company_id)
            ->where('active', '1')
            ->where('ai_generate_question', '1')
            ->where('id', $request->job_id)
            ->where('bash_id', $request->job_bash_id)
            ->where('jobs.status', 'Active')
            ->first();
            if($job_post)
            {
                $ch = curl_init();
                 $jd=array("job_title"=>$job_post->job_title,

                   
                    "skills_required"=>json_decode($job_post->skills_required),
                  
                    "experience"=>$job_post->experience_required,
                  
                    );
     
                              curl_setopt_array($ch, [
                    CURLOPT_URL => 'https://job-recruiter.onrender.com/generate_mcqs',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($jd),
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Content-Type: application/json',
                    ],
                    CURLOPT_FOLLOWLOCATION => true, // Follow redirects
                    CURLOPT_FAILONERROR => false,   // Show error response bodies
                ]);
                        
                $response = curl_exec($ch);
        
                $decoded = json_decode($response, true);
                  if (isset($decoded['mcqs'])) {
                        foreach ($decoded['mcqs'] as $mcqs)
                        {
                            
                          $existing = SkillAssQuestion::where('question', trim($mcqs['question']))->exists();

                            if (!$existing) {
                                SkillAssQuestion::create([
                                    'skill'          => $mcqs['skill'],
                                    'skill_level'    => $mcqs['skill_level'],
                                    'question'       => $mcqs['question'],
                                    'option1'        => $mcqs['option1'],
                                    'option2'        => $mcqs['option2'],
                                    'option3'        => $mcqs['option3'],
                                    'option4'        => $mcqs['option4'],
                                    'correct_answer' => $mcqs['correct_answer'],
                                    'company_id'=>$auth->company_id,
                                    'job_id'=>$request->job_id
                                ]);
                            }
                        }    
                  }
        
                if (curl_errno($ch)) {
                        return response()->json([
                        'status' => false,
                        'message' =>curl_error($ch),
                        
                    ]);
          
                 }
               

            }
        else{
              return response()->json([
                            'status' => false,
                            'message' => 'No Job found for AI Generate Question Yes.',

                        ]); 
        }

    }

     public function ai_salary_range(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'job_title' => 'required',
            'location' => 'array|required',
            'job_description' => 'required',
            'skills'=>'array|required',
            'experience'=>'required'

        ], [
            'job_title.required' => 'Job Title is required.',
            'location.required' => 'location is required',
            'job_description.required' => 'Job Description is required',
            'skills.required'=>'Skill required. ',
            'experience.required'=>'Experience required.'

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
          $ch = curl_init();
                 $jd=array("job_title"=>$request->job_title,

                   
                    "location"=>$request->location,
                  
                    "job_description"=>$request->job_description,
                    "skills"=>$request->skills,
                    "experience"=>$request->experience
                  
                    );
     
                              curl_setopt_array($ch, [
                    CURLOPT_URL => 'https://job-recruiter.onrender.com/predict_salary',
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($jd),
                    CURLOPT_HTTPHEADER => [
                        'Accept: application/json',
                        'Content-Type: application/json',
                    ],
                    CURLOPT_FOLLOWLOCATION => true, // Follow redirects
                    CURLOPT_FAILONERROR => false,   // Show error response bodies
                ]);
                        
                $response = curl_exec($ch);
        
                $decoded = json_decode($response, true);
          
                $rangePart = explode(' ',$decoded['predicted_salary_range'])[0]; // Get "5,00,000–8,00,000"
                $cleanSalary = str_replace([',', '–'], ['', '-'], $rangePart); // Remove commas, replace dash

                  if (curl_errno($ch)) {
                        return response()->json([
                        'status' => false,
                        'message' =>curl_error($ch),
                        
                    ]);
          
                 }
                   return response()->json([
                        'status' => true,
                        'message' =>'Question Added',
                        'data'=>$cleanSalary
                        
                    ]);
    }
}
