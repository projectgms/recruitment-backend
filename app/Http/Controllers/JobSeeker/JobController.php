<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\JobSeekerProfessionalDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\jobs;
use Illuminate\Support\Facades\Storage;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;


use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

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
            'jobs.salary_range',
            'jobs.job_description',
            'jobs.is_hot_job',
            'jobs.location as job_locations',
            'companies.company_logo',
            'companies.name as company_name',
            'companies.locations as company_locations'
        )
            ->leftJoin('companies', 'jobs.company_id', '=', 'companies.id')
            ->where('jobs.status', 'Active')
            ->where(function ($query) use ($jobSeekerSkills) {
                // Loop each skill for an OR condition (any match)
                foreach ($jobSeekerSkills as $skill) {
                    // Convert the skill to lowercase for case-insensitive
                    $lowerSkill = strtolower($skill);
                    // orWhereRaw with LIKE for partial match
                    $query->orWhereRaw("LOWER(jobs.skills_required) LIKE ?", ['%' . $lowerSkill . '%']);
                }
            })
            ->get();

        // 5) Transform the company_logo into a full URL
        $jobs->transform(function ($job) {
            if ($job->company_logo) {

                $job->company_logo =  env('APP_URL') . Storage::url('app/public/' . $job->company_logo);
            }
            return $job;
        });
        return response()->json([
            'status' => true,
            'message' => 'Matching jobs.',
            'data' => $jobs
        ]);
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
            'jobs.is_hot_job',
            'jobs.location as job_locations',
            'companies.company_logo',
            'companies.name as company_name',
            'companies.locations as company_locations'
        )
            ->leftJoin('companies', 'jobs.company_id', '=', 'companies.id')
            ->where('jobs.status', 'Active');

        // 3) Add skill matching (case-insensitive) for any skill
        $jobsQuery->where(function ($query) use ($jobSeekerSkills) {
            foreach ($jobSeekerSkills as $skill) {
                $lowerSkill = strtolower($skill);
                $query->orWhereRaw("LOWER(jobs.skills_required) LIKE ?", ['%' . $lowerSkill . '%']);
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
        if ($request->filled('salary')) {
            // e.g. if 'salary' means "minimum salary"
            $jobsQuery->where('jobs.salary_range', '>=', $request->salary);
        }

        // Filter by city or country (assuming both stored in companies.locations as text)
        // If you have city or country separately, adjust accordingly.
        if ($request->filled('location')) {
            // partial match, case-insensitive
            $location = strtolower($request->location);
            $jobsQuery->whereRaw("LOWER(jobs.location) LIKE ?", ['%' . $location . '%']);
        }

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
        $jobs = $jobsQuery->get();

        $jobs->transform(function ($job) {
            if ($job->company_logo) {
                // Adjust if your 'company_logo' path is stored differently
                $job->company_logo = env('APP_URL') . Storage::url('app/public/' . $job->company_logo);
            }
            return $job;
        });

        return response()->json([
            'status' => true,
            'message' => 'Filtered jobs fetched successfully.',
            'data' => $jobs
        ]);
    }
}
