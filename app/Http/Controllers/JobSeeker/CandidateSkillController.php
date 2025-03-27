<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

use App\Models\User;
use App\Models\JobSeekerProfessionalDetails;
use App\Models\JobSeekerContactDetails;
use App\Models\SkillAssQuestion;

class CandidateSkillController extends Controller
{
    //

    public function get_candidate_skills()
    {
        $auth = JWTAuth::user();
       
        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $get_skills = JobSeekerProfessionalDetails::select('skills')
        ->where('user_id', $auth->id)
        ->first();
        if($get_skills)
        {
        // 1) If it's JSON, decode:
        $jobSeekerSkills = json_decode($get_skills->skills, true);
        return response()->json([
            'status' => true,
            'message' => 'Candidate Skills.',
            'data' => $jobSeekerSkills
        ]);
        }else{
            return response()->json([
                'status' => true,
                'message' => 'Candidate Skills.',
                'data' => []
            ]);
        }

    }

    public function candidate_skill_test(Request $request)
    {
        $auth = JWTAuth::user();
      
        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
        $validator = Validator::make($request->all(), [
            'skill' => 'required',
          
        ], [
            'skill.required' => 'Skill is required.',
          
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $get_skills = JobSeekerProfessionalDetails::select('skills')
        ->where('user_id', $auth->id)
        ->whereRaw('LOWER(skills) LIKE ?', ['%' . strtolower($request->skill) . '%'])
        ->first();
        if($get_skills)
        {
        $data=array(
            'skill'=>$request->skill,
            'total_question'=>'10',
            'total_time'=>'10 Mins'
        );
        return response()->json(['status' => true, 'message' => 'Candidate Skill Test','data'=>$data]);
    }else{
        return response()->json(['status'=>false,'message'=>'Skill not match.']);
    }
    }

    public function candidate_skill_test_que(Request $request)
    {
        $auth = JWTAuth::user();
      
        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
        $validator = Validator::make($request->all(), [
            'skill' => 'required',
          
        ], [
            'skill.required' => 'Skill is required.',
          
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

        $get_experience=JobSeekerContactDetails::select('jobseeker_professional_details.skills','job_seeker_contact_details.user_id','job_seeker_contact_details.total_year_exp')
        ->join('jobseeker_professional_details','jobseeker_professional_details.user_id','=','job_seeker_contact_details.user_id')
        ->where('job_seeker_contact_details.user_id','=',$auth->id)
        ->whereRaw('LOWER(jobseeker_professional_details.skills) LIKE ?', ['%' . strtolower($request->skill) . '%'])
        ->first();
        if($get_experience)
        {
         
            if($get_experience->total_year_exp<=1)
            {
                $get_que=SkillAssQuestion::select('id','skill_level','question','option1','option2','option3','option4','correct_answer','marks')
                ->addSelect(DB::raw("'" . $request->skill . "' as skill"))
                ->where('skill_level','Basic')
                ->whereRaw('LOWER(skill) LIKE ?', '%' . strtolower($request->skill) . '%')
                ->inRandomOrder()
                ->limit(10)
                ->get();
            }else if($get_experience->total_year_exp>1 && $get_experience->total_year_exp<=6)
            {
                $get_que=SkillAssQuestion::select('id','skill_level','question','option1','option2','option3','option4','correct_answer','marks')
                ->addSelect(DB::raw("'" . $request->skill . "' as skill"))
                ->where('skill_level','Medium')
                ->whereRaw('LOWER(skill) LIKE ?', '%' . strtolower($request->skill) . '%')
                ->inRandomOrder()
                ->limit(10)
                ->get();
            }else{
               
                $get_que=SkillAssQuestion::select('id','skill_level','question','option1','option2','option3','option4','correct_answer','marks')
               
                ->addSelect(DB::raw("'" . $request->skill . "' as skill"))
                 ->where('skill_level','High')
                ->whereRaw('LOWER(skill) LIKE ?', '%' . strtolower($request->skill) . '%')
                ->inRandomOrder()
                ->limit(10)
                ->get();
            }
            return response()->json(['status' => true, 'message' => 'Candidate Skill Test Questions' ,'data'=>$get_que]);
        }else{
            return response()->json(['status'=>false,'message'=>'Skill not match.']);

        }
    }
}
