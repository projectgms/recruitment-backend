<?php

namespace App\Http\Controllers\Recruiter;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Company;
use App\Models\InterviewRound;
use App\Models\Jobs;
use Illuminate\Support\Str;

use function Ramsey\Uuid\v1;

class JobPostController extends Controller
{
    //
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
            'salary_range' => 'Salary is required.',
            'expiration_date' => 'Expiration Date is required.',
            'expiration_time' => 'Expiration Time is required.',
            'job_type' => 'Job Type is required.',
            'experience_required' => 'experience is required.',
            'responsibilities' => 'Responsibilities is required.'

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $check_job = Jobs::select('id')->where('company_id', $request->company_id)->where('job_title', $request->job_title)->where('experience_required', $request->experience_required)->where('status', $request->status)->count();
        if ($check_job == 0) {

            $jobs = new Jobs();
            $jobs->bash_id = Str::uuid();
            $jobs->company_id = $request->company_id;
            $jobs->user_id = $request->user_id;
            $jobs->job_title = $request->job_title;

            $jobs->job_description = $request->job_description;
            $jobs->job_type = $request->job_type;
            $jobs->location = json_encode($request->location);

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
            return response()->json(['status' => true, 'message' => 'New Job Post Added.'], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'Job post already added']);
        }
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

        $job_post=Jobs::select('jobs.id','jobs.bash_id','jobs.job_title','jobs.job_description','jobs.job_type','jobs.location','jobs.contact_email','jobs.salary_range','jobs.skills_required','jobs.industry','jobs.experience_required','jobs.status','jobs.is_hot_job','jobs.expiration_date','jobs.expiration_time','jobs.responsibilities','jobs.created_at','companies.name','jobs.company_id')
        ->join('companies','companies.id','=','jobs.company_id')->where('jobs.company_id',$request->company_id)->get()
        ->map(function ($job) {
            return [
                'id' => $job->id,
                'bash_id' => $job->bash_id,
                'job_title' => $job->job_title,
                'job_description' => $job->job_description,
                'job_type' => $job->job_type,
                'location' => json_decode($job->location, true), // Decode JSON
                'contact_email' => $job->contact_email,
                'salary_range' => $job->salary_range,
                'skills_required' => json_decode($job->skills_required, true), // Decode JSON
                'industry' => json_decode($job->industry, true), // Decode JSON
                'experience_required' => $job->experience_required,
                'status' => $job->status,
                'is_hot_job' => $job->is_hot_job,
                'expiration_date' => $job->expiration_date,
                'expiration_time' => $job->expiration_time,
                'responsibilities' => $job->responsibilities,
                'created_at' => $job->created_at,
                'company_name' => $job->name,
                'company_id' => $job->company_id,
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
            'salary_range' => 'Salary is required.',
            'expiration_date' => 'Expiration Date is required.',
            'expiration_time' => 'Expiration Time is required.',
            'job_type' => 'Job Type is required.',
            'experience_required' => 'experience is required.',
            'responsibilities' => 'Responsibilities is required.'

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

        $check_job = Jobs::select('id')->where('company_id', $request->company_id)->where('job_title', $request->job_title)->where('experience_required', $request->experience_required)->where('status', $request->status)->count();
        if ($check_job <=1) {

            $jobs =Jobs::find($request->id);
            $jobs->bash_id = Str::uuid();
            $jobs->company_id = $request->company_id;
            $jobs->user_id = $request->id;
            $jobs->job_title = $request->job_title;

            $jobs->job_description = $request->job_description;
            $jobs->job_type = $request->job_type;
            $jobs->location = json_encode($request->location);

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
            return response()->json(['status' => true, 'message' => ' Job Post Updated.'], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'Job post already added']);
        }
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
        $interview_rounds=InterviewRound::select('id as interview_round_id','round_name')->get();
        return response()->json([
            'status' => true,
            'message' => 'Get Interview Rounds.',
            'data' => $interview_rounds
        ]);
    }
}
