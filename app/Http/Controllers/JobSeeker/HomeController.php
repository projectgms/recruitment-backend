<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use App\Models\JobSeekerProfessionalDetails;
use App\Models\SavedJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Jobs;
use Illuminate\Support\Facades\Storage;
use App\Models\Company;
use App\Models\CompanyEvent;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

class HomeController extends Controller
{
    //

    public function company_list()
    {
         $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        } 
          $company=Company::select('id','bash_id','name','company_logo')->where('active','1')->get();
        if ($company) {
            $company->transform(function ($company) {
                $disk = env('FILESYSTEM_DISK'); // Default to 'local' if not set in .env

                // Modify the company logo to include the full URL if it exists
                if ($company->company_logo) {
                    if ($disk === 's3') {
                        // For S3, use Storage facade with the 's3' disk
                        $company->company_logo = Storage::disk('s3')->url($company->company_logo);
                    } else {
                        // Default to local
                        $company->company_logo = env('APP_URL') . Storage::url('app/public/' . $company->company_logo);
                    }
                } else {
                    // If no logo exists, set it to null or a default image URL
                    $company->company_logo = null; // Replace with a default image URL if needed
                }
             
                return $company;
            });
            
        }
        return response()->json([
            'status' => true,
            'message' => 'Get Company Information.',
            'data' => $company
        ]);
    }
    
      public function company_details(Request $request)
    {
        $auth = JWTAuth::user();
        $disk = env('FILESYSTEM_DISK'); // Default to 'local' if not set in .env

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'bash_id'=>'required'
            ], [
            'id.required' => 'Company Id is required.',
            'bash_id.required'=>'Company Bash Id is required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' =>$validator->errors(),
                
            ], 422);
        }
        $company=Company::select('id','bash_id','name','website','industry','company_size','company_description','locations','company_logo','social_profiles','facebook_url','linkedin_url','twitter_url','instagram_url')->where('id',$request->id)->where('bash_id',$request->bash_id)->first();
        if ($company) {
            // Modify the company logo to include the full URL if it exists
            if ($company->company_logo) {
                if ($disk=== 's3') {
                    // For S3, use Storage facade with the 's3' disk
                    $company->company_logo = Storage::disk('s3')->url($company->company_logo);
                } else {
                    // Default to local
                    $company->company_logo = env('APP_URL') . Storage::url('app/public/' . $company->company_logo);
                }
            } else {
                $company->company_logo = null; // Or use a default image URL
            }
        
           $company->locations = json_decode($company->locations, true);
                $company->industry = json_decode($company->industry, true);
                
        }
        
        ///
          $company_event=CompanyEvent::select('title','event_images','description')->where('company_id',$request->id)->where('active','1')->orderBy('id','desc')->get();
        if ($company_event) {
          $company_event->transform(function ($company_event) {
                $disk = env('FILESYSTEM_DISK'); // 'local' or 's3'
            
                if ($company_event->event_images) {
                    $images = json_decode($company_event->event_images, true); // Decode JSON array
                    $imageUrls = [];
            
                    foreach ($images as $image) {
                        if ($disk === 's3') {
                            $imageUrls[] = Storage::disk('s3')->url($image);
                        } else {
                            $imageUrls[] = env('APP_URL') . Storage::url('app/public/' .$image);
                        }
                    }
            
                    $company_event->event_images = $imageUrls; // Assign the array of full URLs
                } else {
                    $company_event->event_images = [];
                }
            
                return $company_event;
            });
            
        }else{
            $company_event=[];
        }
        
        
        ////
        
         $get_skills = JobSeekerProfessionalDetails::select('skills')
            ->where('user_id', $auth->id)
            ->first();
        if($get_skills)
        {
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
           
            'jobs.created_at',
            'jobs.expiration_date'
        )
            ->leftJoin('companies', 'jobs.company_id', '=', 'companies.id')
            ->where('jobs.status', 'Active')
            ->where('jobs.active', '1')
              ->where('companies.active', '1')
            ->where('jobs.expiration_date','>=',date('Y-m-d'))
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
            })->orderBy('jobs.created_at','desc')
            ->limit(5)
            ->get();
              $jobs->transform(function ($job) use ($auth) {
           
              $job->job_locations = json_decode($job->job_locations, true);
               $job->posted_time  = Carbon::parse($job->created_at)->diffForHumans();
                $save_job=SavedJob::select('id')->where('job_id','=',$job->id)->where('jobseeker_id',$auth->id)->first();
               
                $expirationDate = Carbon::parse($job->expiration_date)->startOfDay();
                $currentDate = Carbon::now()->startOfDay();
                
                $daysDifference = $currentDate->diffInDays($expirationDate, false); 
                   $job->is_hot_job= ($daysDifference >= 0 && $daysDifference <= 15) ? 'Yes' : 'No';

                if($save_job)
                {
                    $job->is_saved_job=true;
                }else{
                    $job->is_saved_job=false;
                }
                
                 $check_job=JobApplication::select('status')->where('job_id','=',$job->id)->where('job_seeker_id','=',$auth->id)->first();
                if($check_job)
                {
                    $job->job_application_status=true;
                }else{
                    $job->job_application_status=false;
                }
             
            return $job;
        });
        }else{
            $jobs=[];
        }
      $response_data=[
            "company_profile"=>$company,
            "jobs"=>$jobs,
            "events"=>$company_event
        ];
        return response()->json([
            'status' => true,
            'message' => 'Get Company Information.',
            'data' => $response_data
        ]);
    }

    public function top_job_post()
    {
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
            ->where('jobs.expiration_date','>=',date('Y-m-d'))
            ->where('jobs.active','1')
            ->orderBy('jobs.created_at','desc')
            ->limit(6)
            ->get();

        // 5) Transform the company_logo into a full URL
        $jobs->transform(function ($job) {
             $disk = env('FILESYSTEM_DISK'); // Default to 'local' if not set in .env
 
            if ($job->company_logo) {
                if ($disk=== 's3') {
                    // For S3, use Storage facade with the 's3' disk
                    $job->company_logo = Storage::disk('s3')->url($job->company_logo);
                } else {
                    // Default to local
                    $job->company_logo = env('APP_URL') . Storage::url('app/public/' . $job->company_logo);
                }
             
            }
              $job->job_locations = json_decode($job->job_locations, true);
               $job->posted_time  = Carbon::parse($job->created_at)->diffForHumans();
               
                $expirationDate = Carbon::parse($job->expiration_date)->startOfDay();
                $currentDate = Carbon::now()->startOfDay();
                
                $daysDifference = $currentDate->diffInDays($expirationDate, false); 
                   $job->is_hot_job= ($daysDifference >= 0 && $daysDifference <= 15) ? 'Yes' : 'No';

            return $job;
        });
        return response()->json([
            'status' => true,
            'message' => ' jobs.',
            'data' => $jobs
        ]);
    }
}
