<?php

namespace App\Http\Controllers\Recruiter;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\JobApplication;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
class CandidateController extends Controller
{
    //

    public function job_applicant(Request $request)
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
            'job_id'=>'required',
            'bash_id'=>'required',
          
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

        $job_candidate=JobApplication::select('users.name','users.first_name','users.middle_name','users.last_name','users.email','users.mobile','users.location','users.gender','users.dob','users.marital_status','users.medical_history','users.disability','users.language_known',
        'job_applications.status as application_status','job_applications.id','job_seeker_contact_details.country', 'job_seeker_contact_details.state', 'job_seeker_contact_details.city', 'job_seeker_contact_details.zipcode', 'job_seeker_contact_details.course', 'job_seeker_contact_details.primary_specialization', 'job_seeker_contact_details.dream_company',
        'job_seeker_contact_details.total_year_exp','job_seeker_contact_details.total_month_exp','job_seeker_contact_details.secondary_mobile','job_seeker_contact_details.secondary_email','job_seeker_contact_details.linkedin_url','job_seeker_contact_details.github_url',
        'jobseeker_education_details.certifications','jobseeker_education_details.publications','jobseeker_education_details.trainings','jobseeker_education_details.educations',
        'jobseeker_professional_details.experience','jobseeker_professional_details.summary','jobseeker_professional_details.skills','jobseeker_professional_details.achievement','jobseeker_professional_details.extra_curricular','jobseeker_professional_details.projects','jobseeker_professional_details.internship')
        ->join('jobs','jobs.id','=','job_applications.job_id')
        ->join('users','users.id','=','job_applications.job_seeker_id')
        ->join('job_seeker_contact_details', 'users.id', '=', 'job_seeker_contact_details.user_id')
        ->join('jobseeker_education_details','users.id','=','jobseeker_education_details.user_id')
        ->join('jobseeker_professional_details','users.id','=','jobseeker_professional_details.user_id')
       
        ->get();
        $job_candidate->transform(function ($candidate) {
            $candidate->certifications = json_decode($candidate->certifications, true);
            $candidate->publications = json_decode($candidate->publications, true);
            $candidate->trainings = json_decode($candidate->trainings, true);
            $candidate->educations = json_decode($candidate->educations, true);
            $candidate->experience = json_decode($candidate->experience, true);
            $candidate->skills = json_decode($candidate->skills, true);
            $candidate->projects = json_decode($candidate->projects, true);
            $candidate->internship = json_decode($candidate->internship, true);
        
            return $candidate;
        });
        if($job_candidate)
        {

        return response()->json([
            'status' => true,
            'message' => 'Candidate Information.',
            'data' => $job_candidate
        ]);
    }else{
        return response()->json([
            'status' => true,
            'message' => 'Candidate Information.',
            'data' => $job_candidate
        ]);
    }
    }
}
