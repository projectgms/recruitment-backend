<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\CandidateSkillTest;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

use App\Models\User;
use App\Models\JobSeekerProfessionalDetails;
use App\Models\JobSeekerContactDetails;
use App\Models\SkillAssQuestion;

class CandidateSkillController extends Controller
{
    //

   public function get_candidate_skills(Request $request)
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

    if (!$get_skills || empty($get_skills->skills)) {
        return response()->json([
            'status' => true,
            'message' => 'No skills found.',
            'data' => []
        ]);
    }

    $jobSeekerSkills = json_decode($get_skills->skills, true);
    $stopWords = ['and', 'or', 'the', 'with', 'a', 'an', 'to', 'of', 'for'];

    $validSkills = [];

    foreach ($jobSeekerSkills as $skill) {
        $words = preg_split('/[\s,]+/', strtolower($skill));

        $questionCount = SkillAssQuestion::
            where(function ($query) use ($words, $stopWords) {
                foreach ($words as $word) {
                    if (!empty($word) && !in_array($word, $stopWords)) {
                        $query->orWhereRaw("LOWER(skill) LIKE ?", ['%' . $word . '%']);
                    }
                }
            })
            ->count();

        if ($questionCount >= 10) {
            $validSkills[] = $skill;
        }
    }

    return response()->json([
        'status' => true,
        'message' => 'Candidate Skills.',
        'data' => $validSkills
    ]);
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
        ->where(function ($query) use ($request) {
             $stopWords = ['and', 'or', 'the', 'with', 'a', 'an', 'to', 'of', 'for']; // Add more if needed

                    $words = preg_split('/[\s,]+/', strtolower($request->skill));
                    foreach ($words as $word) {
                        if (!empty($word) && !in_array($word, $stopWords)) {
                            $query->orWhereRaw("LOWER(jobseeker_professional_details.skills) LIKE ?", ['%' . $word . '%']);
                        }
                    }
       
    })
        ->first();
      
        if($get_experience)
        {
         
            if($get_experience->total_year_exp<=1)
            {
                $get_que=SkillAssQuestion::select('id','skill_level','question','option1','option2','option3','option4','marks')
                ->addSelect(DB::raw("'" . $request->skill . "' as skill"))
                ->where('skill_level','Basic')
                 ->where(function ($query) use ($request) {
      
        
         $stopWords = ['and', 'or', 'the', 'with', 'a', 'an', 'to', 'of', 'for']; // Add more if needed

                    $words = preg_split('/[\s,]+/', strtolower($request->skill));
                    foreach ($words as $word) {
                        if (!empty($word) && !in_array($word, $stopWords)) {
                            $query->orWhereRaw("LOWER(skill) LIKE ?", ['%' . $word . '%']);
                        }
                    }
    })
                
                ->inRandomOrder()
                ->limit(10)
                ->get();
            }else if($get_experience->total_year_exp>1 && $get_experience->total_year_exp<=6)
            {
                $get_que=SkillAssQuestion::select('id','skill_level','question','option1','option2','option3','option4','marks')
                ->addSelect(DB::raw("'" . $request->skill . "' as skill"))
                ->where('skill_level','Medium')
                ->where(function ($query) use ($request) {
        $stopWords = ['and', 'or', 'the', 'with', 'a', 'an', 'to', 'of', 'for']; // Add more if needed

                    $words = preg_split('/[\s,]+/', strtolower($request->skill));
                    foreach ($words as $word) {
                        if (!empty($word) && !in_array($word, $stopWords)) {
                            $query->orWhereRaw("LOWER(skill) LIKE ?", ['%' . $word . '%']);
                        }
                    }
    })
                ->inRandomOrder()
                ->limit(10)
                ->get();
            }else{
             
                $get_que=SkillAssQuestion::select('id','skill','skill_level','question','option1','option2','option3','option4','marks')
               
                ->addSelect(DB::raw("'" . $request->skill . "' as skill"))
                 ->where('skill_level','High')
                 ->where(function ($query) use ($request) {
        $stopWords = ['and', 'or', 'the', 'with', 'a', 'an', 'to', 'of', 'for']; // Add more if needed

                    $words = preg_split('/[\s,]+/', strtolower($request->skill));
                    foreach ($words as $word) {
                        if (!empty($word) && !in_array($word, $stopWords)) {
                            $query->orWhereRaw("LOWER(skill) LIKE ?", ['%' . $word . '%']);
                        }
                    }
    })
                ->inRandomOrder()
                ->limit(10)
                ->get();
            }
            return response()->json(['status' => true, 'message' => 'Candidate Skill Test Questions' ,'data'=>$get_que]);
        }else{
            return response()->json(['status'=>false,'message'=>'Skill not match.']);

        }
    }

  public function submit_candidate_skill_test(Request $request)
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
            'answers'=>'array|required',
           ], [
            'skill.required' => 'Skill is required.',
            'answers.required'=>'answers is required.',
           ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $answers = [];
        $score=0;
        $total=0;
        // Handle file uploads
        $i = 1;
        foreach ($request->answers as $key => $answer) {
            $get_que=SkillAssQuestion::select('correct_answer','marks')->where('id',$answer['id'])->where('question',$answer['question'])->first();
            // Store JSON data with updated file path
            $total += $get_que->marks; 
             if ($answer['answer'] == $get_que->correct_answer) 
             {
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

        $check_test= CandidateSkillTest::where('jobseeker_id', '=', $auth->id)->where('skill',$request->skill)->count();
        if ($check_test == 0) {

            $test = new CandidateSkillTest();
            $test->bash_id = Str::uuid();
            $test->jobseeker_id = $auth->id;
            $test->skill = $request->skill;
            $test->score = $score;
            $test->total = $total;
          

            $test->save();
                $jsonData = [
                'valid_answer' =>$answers,
                'score' => $score,
                 'total'=>$total
            ];
            return response()->json(['status' => true,'data'=>$jsonData,'message' => 'Test Submited.'], 200);
        } else {

            $test= CandidateSkillTest::where('jobseeker_id', '=', $auth->id)->where('skill',$request->skill)->first();
            $test->skill = $request->skill;
            $test->score = $score;
            $test->total = $total;
            $test->save();
              $jsonData = [
                'valid_answer' =>$answers,
                'score' => $score,
                 'total'=>$total
            ];
            return response()->json(['status' => true,'data'=>$jsonData, 'message' => 'Retest Submitted.'], 200);
        }

    }
    
     public function get_skill_test_score()
    {
        $auth = JWTAuth::user();
      
        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
     
            $score = CandidateSkillTest::select('skill', 'score', 'total')
                ->where('jobseeker_id', '=', $auth->id)
                ->get();
    
             $scoreWithBatch= $score->map(function ($item) {
             if ($item->score >= 9 && $item->score <= 10) {
            $item->batch_type = 'Gold';
        } elseif ($item->score >= 7 && $item->score <= 8) {
            $item->batch_type = 'Silver';
        } elseif ($item->score >= 5 && $item->score <= 6) {
            $item->batch_type = 'Bronze';
        } else {
            $item->batch_type = null;
        }

        // Set batch true/false based on score >=5
        $item->batch = ($item->score >= 5) ? true : false;

        return $item;
            });
       
    
        return response()->json([
            'status' => true,
            'message' => 'Candidate Skill Test Questions',
            'data' => $scoreWithBatch
        ]);
      
       
    }
}
