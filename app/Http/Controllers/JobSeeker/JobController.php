<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobSeekerProfessionalDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Jobs;
use App\Models\JobSeekerContactDetails;
use Illuminate\Support\Facades\Storage;
use App\Models\GenerateResume;
use App\Models\InterviewRound;
use App\Models\Company;
use App\Models\SavedJob;
use App\Models\JobseekerPrepareJob;
use App\Models\JobPostNotification;
use App\Models\JobPostNotificationStatus;

use Illuminate\Support\Facades\Notification;
use App\Models\JobApplicationNotification;
use App\Notifications\JobSeeker\UpdateJobApplication;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Helpers\FileHelper;

use Illuminate\Support\Facades\Validator;

class JobController extends Controller
{
    //

    public function job_list(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }
        $get_skills = JobSeekerProfessionalDetails::select('skills')
            ->where('user_id', $auth->id)
            ->first();
        if ($get_skills) {
            // 1) If it's JSON, decode:
            $jobSeekerSkills = json_decode($get_skills->skills, true);

            // OR if it’s comma-separated, do:
            if (!is_array($jobSeekerSkills)) {
                $jobSeekerSkills = array_map('trim', explode(',', $get_skills->skills));
            }
            $jobs = Jobs::select(
                'jobs.id',
                'jobs.bash_id',
                'jobs.job_title',
                'jobs.job_type',
                'jobs.experience_required',
                'jobs.skills_required',
                'jobs.salary_range',
                'jobs.job_description',

                'jobs.location as job_locations',
                'companies.company_logo',
                'companies.name as company_name',
                'companies.locations as company_locations',
                'jobs.created_at',
                'jobs.expiration_date'
            )
                ->leftJoin('companies', 'jobs.company_id', '=', 'companies.id')
                ->where('jobs.status', 'Active')
                ->where('jobs.expiration_date', '>=', date('Y-m-d'))
                ->where(function ($query) use ($jobSeekerSkills) {
                    // Loop each skill for an OR condition (any match)
                    foreach ($jobSeekerSkills as $skill) {
                        $stopWords = ['and', 'or', 'the', 'with', 'a', 'an', 'to', 'of', 'for']; // Add more if needed

                        $words = preg_split('/[\s,]+/', strtolower($skill));
                        foreach ($words as $word) {
                            if (!empty($word) && !in_array($word, $stopWords)) {
                                $query->orWhereRaw("LOWER(jobs.skills_required) LIKE ?", ['%' . $word . '%']);
                            }
                        }
                    }
                })->orderBy('jobs.created_at', 'desc')
                ->get();

            // 5) Transform the company_logo into a full URL
            $jobs->transform(function ($job) use ($auth) {
                $disk = env('FILESYSTEM_DISK'); // Default to 'local' if not set in .env

                if ($job->company_logo) {
                    $job->company_logo = FileHelper::getFileUrl($job->company_logo);
                }
                $job->job_locations = json_decode($job->job_locations, true);

                if (Carbon::now()->diffInDays(Carbon::parse($job->expiration_date), false) <= 3) {
                    $job->expiration_time = true;
                } else {
                    $job->expiration_time = false;
                }
                $save_job = SavedJob::select('id')->where('job_id', '=', $job->id)->where('jobseeker_id', $auth->id)->first();

                $expirationDate = Carbon::parse($job->expiration_date)->startOfDay();
                $currentDate = Carbon::now()->startOfDay();

                $daysDifference = $currentDate->diffInDays($expirationDate, false);
                $job->is_hot_job = ($daysDifference >= 0 && $daysDifference <= 15) ? 'Yes' : 'No';
                $job->expiration_time = Carbon::parse($job->expiration_date)->diffForHumans(); // Human-readable

                if ($save_job) {
                    $job->is_saved_job = true;
                } else {
                    $job->is_saved_job = false;
                }

                $check_job = JobApplication::select('status')->where('job_id', '=', $job->id)->where('job_seeker_id', '=', $auth->id)->first();
                if ($check_job) {
                    $job->job_application_status = true;
                } else {
                    $job->job_application_status = false;
                }

                return $job;
            });
            return response()->json([
                'status' => true,
                'message' => 'Matching jobs.',
                'data' => $jobs
            ]);
        } else {
            return response()->json([
                'status' => true,
                'message' => 'Matching jobs.',
                'data' => []
            ]);
        }
    }
    public function job_list_filter(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // 1) Fetch the job seeker's skills
        $get_skills = JobSeekerProfessionalDetails::select('skills')
            ->where('user_id', $auth->id)
            ->first();

        // Convert the skills to an array (either JSON or comma-separated)
        $jobSeekerSkills = json_decode($get_skills->skills, true);
        if (!is_array($jobSeekerSkills)) {
            $jobSeekerSkills = array_map('trim', explode(',', $get_skills->skills));
        }

        // 2) Build the base query for jobs
        $jobsQuery = Jobs::select(
            'jobs.id',
            'jobs.bash_id',
            'jobs.job_title',
            'jobs.job_type',
            'jobs.experience_required',
            'jobs.salary_range',
            'jobs.job_description',
            //'jobs.is_hot_job',
            'jobs.expiration_date',
            'jobs.location as job_locations',
            'jobs.responsibilities',
            'jobs.skills_required',
            'jobs.industry',
            'jobs.contact_email',
            'companies.company_logo',
            'companies.name as company_name',
            'companies.locations as company_locations',
            'companies.company_description',
            'jobs.created_at'
        )
            ->leftJoin('companies', 'jobs.company_id', '=', 'companies.id')
            ->where('jobs.expiration_date', '>=', date('Y-m-d'))
            ->where('jobs.status', 'Active');

        // 3) Add skill matching (case-insensitive) for any skill
        $jobsQuery->where(function ($query) use ($jobSeekerSkills) {
            foreach ($jobSeekerSkills as $skill) {
                $stopWords = ['and', 'or', 'the', 'with', 'a', 'an', 'to', 'of', 'for']; // Add more if needed

                $words = preg_split('/[\s,]+/', strtolower($skill));
                foreach ($words as $word) {
                    if (!empty($word) && !in_array($word, $stopWords)) {
                        $query->orWhereRaw("LOWER(jobs.skills_required) LIKE ?", ['%' . $word . '%']);
                    }
                }
            }
        });

        // 4) Apply Optional Filters

        // Filter by job_title (partial match, case-insensitive)
        if ($request->filled('job_title')) {
            $jobTitle = strtolower($request->job_title);
            $jobsQuery->whereRaw("LOWER(jobs.job_title) LIKE ?", ['%' . $jobTitle . '%']);
        }

        // Filter by job_type (exact match)
        if ($request->filled('job_type')) {
            $jobTypeInput = strtolower($request->job_type);
            $jobTypeInput = str_replace('-', '', $jobTypeInput); // remove dashes

            $jobsQuery->whereRaw(
                "REPLACE(LOWER(jobs.job_type), '-', '') LIKE ?",
                ['%' . $jobTypeInput . '%']
            );
        }

        // Filter by salary_range (minimum salary)
        if ($request->filled('minSalary') && $request->filled('maxSalary')) {
            $min = (int) $request->minSalary;
            $max = (int) $request->maxSalary;

            $jobsQuery->whereRaw("
                CAST(SUBSTRING_INDEX(jobs.salary_range, '-', 1) AS UNSIGNED) <= ?
                AND
                CAST(SUBSTRING_INDEX(jobs.salary_range, '-', -1) AS UNSIGNED) >= ?
            ", [$max, $min]);
            // $jobsQuery->where('jobs.salary_range', '>=', $request->minSalary)->where('jobs.salary_range','<=',$request->maxSalary);
        }


        // Filter by city or country (assuming both stored in companies.locations as text)
        // If you have city or country separately, adjust accordingly.
        if ($request->filled('location')) {
            // partial match, case-insensitive
            $location = strtolower($request->location);
            $jobsQuery->whereRaw("LOWER(jobs.location) LIKE ?", ['%' . $location . '%']);
        }
        // if ($request->filled('is_hot_job')) {
        //     // partial match, case-insensitive

        //     $jobsQuery->whereRaw("LOWER(jobs.is_hot_job) LIKE ?", ['%' . $request->is_hot_job . '%']);
        // }
        // If you want separate filters for 'city' and 'country', do something like:
        /*
        if ($request->filled('city')) {
            $city = strtolower($request->city);
            $jobsQuery->whereRaw("LOWER(companies.locations) LIKE ?", ['%' . $city . '%']);
        }
        if ($request->filled('country')) {
            $country = strtolower($request->country);
            $jobsQuery->whereRaw("LOWER(companies.locations) LIKE ?", ['%' . $country . '%']);
        }
        */

        // 5) Get the final list of jobs
        $jobs = $jobsQuery->orderBy('jobs.created_at', 'desc')->get();

        $jobs->transform(function ($jobs) use ($auth) {
            $disk = env('FILESYSTEM_DISK'); // Default to 'local' if not set in .env

            if ($jobs->company_logo) {
                $jobs->company_logo = FileHelper::getFileUrl($jobs->company_logo);
            }
            $jobs->skills_required = json_decode($jobs->skills_required, true);
            $jobs->industry = json_decode($jobs->industry, true);
            $jobs->company_locations = json_decode($jobs->company_locations, true);
            $jobs->job_locations = json_decode($jobs->job_locations, true);
            $expirationDate = Carbon::parse($jobs->expiration_date)->startOfDay();
            $currentDate = Carbon::now()->startOfDay();

            $daysDifference = $currentDate->diffInDays($expirationDate, false);
            $jobs->is_hot_job = ($daysDifference >= 0 && $daysDifference <= 15) ? 'Yes' : 'No';

            if (Carbon::now()->diffInDays(Carbon::parse($jobs->expiration_date), false) <= 3) {
                $jobs->expiration_time = true;
            } else {
                $jobs->expiration_time = false;
            }
            $save_job = SavedJob::select('id')->where('job_id', '=', $jobs->id)->where('jobseeker_id', $auth->id)->first();

            if ($save_job) {
                $jobs->is_saved_job = true;
            } else {
                $jobs->is_saved_job = false;
            }
            $check_job = JobApplication::select('status')->where('job_id', '=', $jobs->id)->where('job_seeker_id', '=', $auth->id)->first();
            if ($check_job) {
                $jobs->job_application_status = true;
            } else {
                $jobs->job_application_status = false;
            }
            $jobs->posted_time = Carbon::parse($jobs->created_at)->diffForHumans();

            return $jobs;
        });
        if ($request->filled('is_hot_job')) {
            $hotJobFilter = strtolower($request->is_hot_job);

            if ($hotJobFilter === 'yes') {
                $jobs = $jobs->filter(function ($job) {
                    return $job->is_hot_job === 'Yes';
                })->values();
            } elseif ($hotJobFilter === 'no') {
                $jobs = $jobs->filter(function ($job) {
                    return $job->is_hot_job === 'No';
                })->values();
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Filtered jobs fetched successfully.',
            'data' => $jobs
        ]);
    }

    public function get_job_details(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'bash_id' => 'required',
        ], [
            'id.required' => 'Job Id is required.',
            'bash_id.required' => 'Bash Id is required.'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

        $jobs = Jobs::select(
            'jobs.id',
            'jobs.bash_id',
            'jobs.job_title',
            'jobs.job_type',
            'jobs.experience_required',
            'jobs.salary_range',
            'jobs.job_description',
            'jobs.is_hot_job',
            'jobs.location as job_locations',
            'jobs.responsibilities',
            'jobs.skills_required',
            'jobs.industry',
            'jobs.contact_email',
            'companies.company_logo',
            'companies.name as company_name',
            'companies.locations as company_locations',
            'companies.company_description'
        )
            ->leftJoin('companies', 'jobs.company_id', '=', 'companies.id')
            ->where('jobs.status', 'Active')
            ->where('jobs.id', '=', $request->id)
            ->where('jobs.bash_id', '=', $request->bash_id)
            ->first();
        if ($jobs) {
            // Modify the company logo to include the full URL if it exists
            $disk = env('FILESYSTEM_DISK'); // Default to 'local' if not set in .env

            if ($jobs->company_logo) {
               $jobs->company_logo =FileHelper::getFileUrl( $jobs->company_logo);
               
            } else {
                $jobs->company_logo = null;
            }

            $jobs->skills_required = json_decode($jobs->skills_required, true);
            $jobs->industry = json_decode($jobs->industry, true);
            $jobs->company_locations = json_decode($jobs->company_locations, true);
            $jobs->job_locations = json_decode($jobs->job_locations, true);
            $check_job = JobApplication::select('status')->where('job_id', '=', $jobs->id)->where('job_seeker_id', '=', $auth->id)->first();
            if ($check_job) {
                $jobs->job_application_status = true;
            } else {
                $jobs->job_application_status = false;
            }

            $resume = GenerateResume::select('is_ai_generated')->where('job_id', '=', $jobs->id)->where('is_ai_generated', 'true')->where('user_id', $auth->id)->first();
            if ($resume) {
                $jobs->is_ai_generated = true;
            } else {
                $jobs->is_ai_generated = false;
            }

            $save_job = SavedJob::select('id')->where('job_id', '=', $jobs->id)->where('jobseeker_id', $auth->id)->first();

            if ($save_job) {
                $jobs->is_saved_job = true;
            } else {
                $jobs->is_saved_job = false;
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Job Details.',
            'data' => $jobs
        ]);
    }

    public function apply_job(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'bash_id' => 'required',
            'resume_id' => 'required',
        ], [
            'id.required' => 'Job Id is required.',
            'bash_id.required' => 'Bash Id is required.',
            'resume_id.required' => 'Resume Id is Required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $get_skills = JobSeekerProfessionalDetails::select('skills')
            ->where('user_id', $auth->id)
            ->first();

        $get_exp = JobSeekerContactDetails::select('total_year_exp', 'total_month_exp')
            ->where('user_id', $auth->id)
            ->first();

        // Convert skills to an array (JSON or comma-separated)
        $jobSeekerSkills = json_decode($get_skills->skills, true);
        if (!is_array($jobSeekerSkills)) {
            $jobSeekerSkills = array_map('trim', explode(',', $get_skills->skills));
        }

        // Convert total experience to years
        $candidateExperience = (int) $get_exp->total_year_exp + ($get_exp->total_month_exp / 12);

        // 2) Build the base query for jobs
        $jobsQuery = Jobs::select(
            'jobs.id',
            'jobs.bash_id',
            'jobs.contact_email',
            'jobs.job_title',
            'jobs.company_id',
            'jobs.experience_required',
        )
            ->where('jobs.status', 'Active')
            ->where('jobs.id', $request->id)
            ->where('jobs.expiration_date', '>=', date('Y-m-d'));
        // 3) Add experience condition (extract number from "7 years")
        $jobsQuery->whereRaw("CAST(REGEXP_SUBSTR(jobs.experience_required, '[0-9]+') AS UNSIGNED) <= ?", [$candidateExperience]);

        // 4) Add skill matching (case-insensitive)
        $jobsQuery->where(function ($query) use ($jobSeekerSkills) {
            foreach ($jobSeekerSkills as $skill) {
                $stopWords = ['and', 'or', 'the', 'with', 'a', 'an', 'to', 'of', 'for']; // Add more if needed

                $words = preg_split('/[\s,]+/', strtolower($skill));
                foreach ($words as $word) {
                    if (!empty($word) && !in_array($word, $stopWords)) {
                        $query->orWhereRaw("LOWER(jobs.skills_required) LIKE ?", ['%' . $word . '%']);
                    }
                }
            }
        });

        $matchingJobs = $jobsQuery->first();
        if ($matchingJobs) {
            $check_job = JobApplication::where('job_id', '=', $request->id)->where('job_seeker_id', '=', $auth->id)->first();
            if ($check_job) {

                // $apply=JobApplication::find($check_job->id);
                // $apply->bash_id=Str::uuid();
                // $apply->job_id=$request->id;
                // $apply->job_seeker_id=$auth->id;
                // $apply->status='Applied';
                // $apply->save();
                return response()->json([
                    'status' => true,
                    'message' => 'You already applied this job..'
                ]);
            } else {
                $resume = GenerateResume::select('resume', 'resume_json')->where('user_id', $auth->id)->where('id', $request->resume_id)->first();
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
                $apply->job_id = $request->id;
                $apply->job_seeker_id = $auth->id;
                $apply->status = 'Applied';
                $apply->resume = $resume_url;
                $apply->resume_json = $resume_json;
                $apply->save();
                $get_recruiter_contact = Company::select('users.mobile')->Join('jobs', 'jobs.company_id', '=', 'companies.id')->Join('users', 'users.id', '=', 'companies.user_id')->where('jobs.id', $matchingJobs->id)->first();
                Notification::route('mail', $matchingJobs->contact_email)->notify(new UpdateJobApplication($auth->name, $matchingJobs->job_title, $auth->email, $get_recruiter_contact->mobile));

                $job_application_notification = new JobApplicationNotification();
                $job_application_notification->bash_id = Str::uuid();
                $job_application_notification->job_id = $request->id;
                $job_application_notification->job_application_id = $apply->id;
                $job_application_notification->company_id = $matchingJobs->company_id;
                $job_application_notification->jobseeker_id = $auth->id;
                $job_application_notification->type = 'Job Application';
                $job_application_notification->message = 'New Job application added for the role ' . $matchingJobs->job_title;
                $job_application_notification->is_read = '0';
                $job_application_notification->save();

                return response()->json([
                    'status' => true,
                    'message' => 'Job Applied.',

                ]);
            }
        } else {
            return response()->json([
                'status' => true,
                'message' => 'Your Skill and Experience not match this job..',

            ]);
        }
    }


    public function get_job_round(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'bash_id' => 'required',

        ], [
            'id.required' => 'Job Id is required.',
            'bash_id.required' => 'Bash Id is required.',

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $job = Jobs::select('round')
            ->where('id', $request->id)
            ->where('bash_id', $request->bash_id)
            ->first();

        if ($job && $job->round) {
            // Decode the JSON array from the `round` column
            $roundIds = json_decode($job->round, true);

            // Fetch matching rounds from InterviewRound table
            $interviewRounds = InterviewRound::select('id as interview_round_id', 'round_name')
                ->whereIn('id', $roundIds)
                ->get();

            return response()->json([
                'status' => true,
                'data' => $interviewRounds
            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Job or rounds not found.'
            ], 404);
        }
    }

    public function submit_saved_job(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
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
        $check_job = SavedJob::where('job_id', '=', $request->job_id)->where('jobseeker_id', '=', $auth->id)->first();
        if ($check_job) {
            $check_job->delete();

            return response()->json([
                'status' => true,
                'message' => 'You Unsaved Job.'
            ]);
        } else {

            $save = new SavedJob();
            $save->bash_id = Str::uuid();
            $save->job_id = $request->job_id;
            $save->jobseeker_id = $auth->id;

            $save->save();
            return response()->json([
                'status' => true,
                'message' => 'Job Saved.',

            ]);
        }
    }

    public function my_saved_job()
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
        $jobs = SavedJob::select(
            'candidate_saved_jobs.id',
            'jobs.bash_id',

            'candidate_saved_jobs.jobseeker_id',
            'jobs.id as job_id',
            'jobs.round',
            'jobs.job_title',
            'jobs.job_type',
            'jobs.experience_required',
            'jobs.salary_range',

            'jobs.is_hot_job',
            'jobs.location as job_locations',
            'companies.company_logo',
            'companies.name as company_name',
            'companies.locations as company_locations',
            'jobs.created_at as job_post_date'
        )
            ->Join('jobs', 'jobs.id', '=', 'candidate_saved_jobs.job_id')
            ->leftJoin('companies', 'jobs.company_id', '=', 'companies.id')
            ->where('candidate_saved_jobs.jobseeker_id', '=', $auth->id)
            ->orderBy('candidate_saved_jobs.created_at', 'desc')
            ->get();

        if ($jobs) {
            $jobs->transform(function ($job) {
                $disk = env('FILESYSTEM_DISK'); // Default to 'local' if not set in .env

                if ($job->company_logo) {
                   $job->company_logo =FileHelper::getFileUrl( $job->company_logo);
               
                } else {
                    $job->company_logo = null;
                }
                $job->posted_time  = Carbon::parse($job->job_post_date)->diffForHumans();
                $job->job_locations = json_decode($job->job_locations, true);
                $job->company_locations = json_decode($job->company_locations, true);

                return $job;
            });
            return response()->json([
                'status' => true,
                'message' => 'Candidate Saved Jobs.',
                'data' => $jobs
            ]);
        } else {
            return response()->json([
                'status' => true,
                'message' => 'Candidate Saved Jobs.',
                'data' => []
            ]);
        }
    }

    public function check_job_post_notification(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }
        $get_skills = JobSeekerProfessionalDetails::select('skills')
            ->where('user_id', $auth->id)
            ->first();

        // 1) If it's JSON, decode:
        $jobSeekerSkills = json_decode($get_skills->skills, true);

        // OR if it’s comma-separated, do:
        if (!is_array($jobSeekerSkills)) {
            $jobSeekerSkills = array_map('trim', explode(',', $get_skills->skills));
        }
        $jobs = JobPostNotification::select(
            'jobs.id as id',
            'job_post_notifications.id as notification_id',
            'jobs.bash_id as bash_id',
            'job_post_notifications.type',
            'job_post_notifications.message',
            'job_post_notifications.created_at',

            \DB::raw('IFNULL(job_post_notification_status.is_read, 0) as is_read')

        )->leftJoin('jobs', 'jobs.id', '=', 'job_post_notifications.job_id')
            ->leftJoin('companies', 'jobs.company_id', '=', 'companies.id')
            ->leftJoin('job_post_notification_status', 'job_post_notification_status.job_post_notification_id', '=', 'job_post_notifications.id')
            ->where(function ($query) use ($jobSeekerSkills) {
                // Loop each skill for an OR condition (any match)
                foreach ($jobSeekerSkills as $skill) {
                    $stopWords = ['and', 'or', 'the', 'with', 'a', 'an', 'to', 'of', 'for']; // Add more if needed

                    $words = preg_split('/[\s,]+/', strtolower($skill));
                    foreach ($words as $word) {
                        if (!empty($word) && !in_array($word, $stopWords)) {
                            $query->orWhereRaw("LOWER(jobs.skills_required) LIKE ?", ['%' . $word . '%']);
                        }
                    }
                }
            })->orderBy('job_post_notifications.created_at', 'desc')
            ->get();
        return response()->json([
            'status' => true,
            'message' => 'Job Post Notification.',
            'data' => $jobs
        ]);
    }

    public function update_job_post_notification(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $validator = Validator::make($request->all(), [
            'job_id' => 'required',

            'id' => 'required',

        ], [
            'job_id.required' => 'Job Id is required.',

            'id.required' => 'Id is required'

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $status = JobPostNotificationStatus::Join('job_post_notifications', 'job_post_notifications.id', 'job_post_notification_status.job_post_notification_id')->where('job_post_notifications.job_id', $request->job_id)->where('job_post_notification_status.jobseeker_id', $auth->id)
            ->where('job_post_notification_status.job_post_notification_id', $request->id)->first();
        if ($status) {
            $status->is_read = '1';
            $status->save();
        } else {
            $notification = new JobPostNotificationStatus();
            $notification->bash_id = Str::uuid();

            $notification->jobseeker_id = $auth->id;
            $notification->job_post_notification_id = $request->id;
            $notification->is_read = '1';

            $notification->save();
        }
        return response()->json([
            'status' => true,
            'message' => 'Job Post Notification Status Changed.',

        ]);
    }

    public function prepare_for_job(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'title' => 'required',

        ], [
            'title.required' => 'Title is required.',

        ]);
        if ($request->title == 'jd') {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'bash_id' => 'required',

            ], [
                'id.required' => 'Job Application Id is required.',

                'bash_id.required' => 'Bash Id is required'

            ]);

            $job_application = JobApplication::select('job_applications.job_seeker_id', 'jobs.job_title', 'jobs.location', 'jobs.job_description', 'jobs.responsibilities', 'jobs.skills_required', 'jobs.status', 'jobs.salary_range', 'jobs.industry', 'jobs.job_type', 'jobs.contact_email', 'jobs.experience_required', 'jobs.is_hot_job', 'jobs.expiration_date', 'jobs.expiration_time', 'job_applications.id as job_application_id', 'job_applications.resume_json', 'job_applications.status')
                ->Join('jobs', 'jobs.id', '=', 'job_applications.job_id')

                ->where('job_applications.bash_id', $request->bash_id)
                ->where('job_applications.id', $request->id)
                ->first();

            if (!$job_application) {
                return response()->json([
                    'status' => false,
                    'message' => 'Resume not found',
                ], 404);
            }

            $jd = array(
                "title" => $job_application->job_title,

                "locations" => $job_application->location,
                "description" => $job_application->job_description,
                "responsibilities" => $job_application->responsibilities,
                "skills" => $job_application->skills_required,
                "status" => $job_application->status,
                "salary" => $job_application->salary_range,
                "industries" => $job_application->industry,
                "employmentType" => $job_application->job_type,
                "email" => $job_application->contact_email,
                "experience" => $job_application->experience_required,
                "hotJob" => $job_application->is_hot_job,
                "expirationDate" => $job_application->expiration_date,

                "expirationTime" => $job_application->expiration_time
            );
            $check_prepare_job = JobseekerPrepareJob::select('qa_output')->where('job_application_id', $request->id)
                ->where('jobseeker_id', $auth->id)
                ->where('title', 'jd')
                ->first();

            if ($check_prepare_job) {
                return response()->json([
                    'status' => true,
                    'message' => 'Already Prepare',
                    'data' => json_decode($check_prepare_job->qa_output),
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
                    'message' => curl_error($ch),

                ]);
            }

            curl_close($ch);
            $decoded = json_decode($response, true);

            if (isset($decoded['questions'])) {
                $decoded = $this->transformQaOutput($decoded['questions']);
                unset($decoded['questions']); // Optional: remove the raw string
            }
            $ai = new JobseekerPrepareJob();
            $ai->bash_id = Str::uuid();
            $ai->jobseeker_id = $auth->id;
            $ai->title = 'jd';
            $ai->job_application_id = $request->id;
            $ai->qa_output = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $ai->save();

            return response()->json([
                'status' => true,
                'message' => 'Candidate Analysis',
                'data' => $decoded
            ]);
        } else if ($request->title == 'resume') {
            $validator = Validator::make($request->all(), [
                'bash_id' => 'required',

                'id' => 'required',

            ], [
                'id.required' => ' Id is required.',

                'bash_id.required' => 'Bash Id is required'

            ]);

            $resume = GenerateResume::select('id', 'bash_id', 'resume_name', 'resume_json')
                ->where('user_id', $auth->id)
                ->where('id', $request->id)
                ->where('bash_id', $request->bash_id)
                ->first();

            if (!$resume) {
                return response()->json([
                    'status' => false,
                    'message' => 'Resume not found',
                ], 404);
            }
            $check_prepare_job = JobseekerPrepareJob::select('qa_output')->where('generate_resume_id', $request->id)
                ->where('jobseeker_id', $auth->id)
                ->where('title', 'resume')
                ->first();

            if ($check_prepare_job) {
                return response()->json([
                    'status' => true,
                    'message' => 'Already Prepare',
                    'data' => json_decode($check_prepare_job->qa_output),
                ]);
            }
            $ch = curl_init();
            $resumeArray = is_string($resume->resume_json)
                ? json_decode($resume->resume_json, true)
                : $resume->resume_json;

            $jsonData = [
                'jd' => new \stdClass(),
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
                    'message' => curl_error($ch),
                ]);
            }

            curl_close($ch);

            // Step 2: Decode and transform response
            $decoded = json_decode($response, true);

            if (isset($decoded['questions'])) {
                $decoded = $this->transformQaOutput($decoded['questions']);
                unset($decoded['questions']); // Optional: remove the raw string
            }

            $ai = new JobseekerPrepareJob();
            $ai->bash_id = Str::uuid();
            $ai->title = 'resume';
            $ai->jobseeker_id = $auth->id;
            $ai->generate_resume_id = $request->id;
            $ai->qa_output = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $ai->save();

            return response()->json([
                'status' => true,
                'message' => 'Candidate Analysis',
                'data' => $decoded
            ]);
        }

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
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

    public function auto_apply_job(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }
        $validator = Validator::make($request->all(), [
            'auto_apply' => 'required',
            'resume_id' => 'required',

        ], [
            'auto_apply.required' => 'Auto Apply status is required.',
            'resume_id.required' => 'Resume Id is required.'
        ]);

        $validator = Validator::make($request->all(), [
            'auto_apply' => 'required',
            'resume_id' => 'required',
        ], [
            'auto_apply.required' => 'JAuto Apply status is required.',
            'resume_id.required' => 'Resume Id is required.'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

        $auto_apply_job = JobSeekerProfessionalDetails::select('auto_apply_job', 'auto_apply_resume_id', 'id')->where('user_id', $auth->id)->first();
        if ($auto_apply_job) {
            if ($request->auto_apply == true) {
                $auto_apply = 1;
            } else {
                $auto_apply = 0;
            }
            $auto_apply_job->auto_apply_job = $auto_apply;
            $auto_apply_job->auto_apply_resume_id = $request->resume_id;
            $auto_apply_job->save();
            return response()->json([
                'status' => true,
                'message' => 'Auto Apply Status Updated.',

            ]);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Please Complete profile first.',

            ]);
        }
    }


    public function get_auto_apply_job()
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $user = JobSeekerProfessionalDetails::select('auto_apply_job')->where('user_id', $auth->id)->first();
        if ($user) {
            if ($user->auto_apply_job == 1) {
                $auto_apply_job = true;
            } else {
                $auto_apply_job = false;
            }
            return response()->json([
                "status" => true,
                "message" => "Auto Apply Status.",
                'data' => $auto_apply_job

            ]);
        } else {

            return response()->json([
                "status" => false,
                "message" => "No user data.",
                'data' => []

            ]);
        }
    }
}
