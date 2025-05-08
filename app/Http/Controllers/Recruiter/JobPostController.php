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

use App\Models\Jobs;
use App\Models\User;

use Illuminate\Support\Str;
use App\Models\SkillAssQuestion;
use Illuminate\Support\Facades\Cache;

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
            'is_hot_job' => 'required',
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

            $jobs->industry = json_encode($request->industry);

            $jobs->experience_required = $request->experience_required;
            $jobs->status = $request->status;
            $jobs->is_hot_job = $request->is_hot_job;
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

            return response()->json(['status' => true, 'message' => 'New Job Post Added.'], 200);
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
                    ->join('companies', 'companies.id', '=', 'jobs.company_id')
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
                        'jobs.user_id', 'jobs.id', 'jobs.round', 'jobs.bash_id', 'jobs.job_title',
                        'jobs.job_description', 'jobs.job_type', 'jobs.location', 'jobs.contact_email',
                        'jobs.salary_range', 'jobs.skills_required', 'jobs.industry', 'jobs.experience_required',
                        'jobs.status', 'jobs.is_hot_job', 'jobs.expiration_date', 'jobs.expiration_time',
                        'jobs.responsibilities', 'jobs.created_at', 'companies.name as company_name', 'jobs.company_id'
                    )
                    ->join('companies', 'companies.id', '=', 'jobs.company_id')
                    ->where('jobs.company_id', $request->company_id)
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
            'is_hot_job' => 'required',
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

            $jobs->industry = json_encode($request->industry);
            $jobs->round = json_encode($request->round);
            $jobs->experience_required = $request->experience_required;
            $jobs->status = $request->status;
            $jobs->is_hot_job = $request->is_hot_job;
            $jobs->expiration_date = $request->expiration_date;
            $jobs->expiration_time = $request->expiration_time;
            $jobs->responsibilities = $request->responsibilities;
            $jobs->save();
           
            return response()->json(['status' => true, 'message' => ' Job Post Updated.'], 200);
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
      
      
      
            $job_post= Jobs::select('jobs.id', 'jobs.job_title')
                ->where('jobs.company_id', $auth->company_id)
                ->where('active', '1')
                ->get()
                ->map(function ($job) {
                    $application_count = JobApplication::where('job_id', $job->id)->count();
    
                    if ($application_count > 0) {
                        return [
                            'job_title' => $job->job_title,
                            'application_count' => $application_count,
                        ];
                    }
                    return null;
                })
                ->filter()
                ->values()
                ->toArray();
      
        return response()->json([
            'status' => true,
            'message' => 'Get Job Posts Count.',
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
}
