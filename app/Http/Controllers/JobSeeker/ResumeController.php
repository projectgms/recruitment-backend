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
use App\Models\AIAnalysisResume;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

use Illuminate\Support\Facades\Validator;

class ResumeController extends Controller
{
    //
    
    //  public function submit_ai_resume_analysis(Request $request)
    // {
    //     $auth = JWTAuth::user();
    //     if (!$auth) {
    //         return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
    //     }

    //     $validator = Validator::make($request->all(), [
    //         'id' => 'required', 
    //         'bash_id'=>'required',
            
    //     ], [
    //         'id.required' => 'Id Id is required.',
    //         'bash_id.required'=>'Bash Id is required.',
            
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => $validator->errors(),
                
    //         ], 422);
    //     }
    //     $check_analysis=AIAnalysisResume::select('id','ai_analysis')->where('resume_generate_id',$request->id)->where('jobseeker_id',$auth->id)->first();
    //     if($check_analysis)
    //     {
    //           $resume = GenerateResume::select('id','bash_id', 'resume_name', 'resume_json')->where('user_id', $auth->id)->where('id',$request->id)->where('bash_id',$request->bash_id)->first();
        
    //         $data=['resume_name'=>$resume->resume_name,
    //       'resume_json'=>json_decode($check_analysis->ai_analysis)];
          
    //         return response()->json([
    //             'status' => true,
    //             'message' =>'Already analysis',
    //             'data'=>$data
    //         ]);
         
    //     }else{
    //         $resume = GenerateResume::select('id','bash_id', 'resume_name', 'resume_json')->where('user_id', $auth->id)->where('id',$request->id)->where('bash_id',$request->bash_id)->first();
    //         $ch = curl_init();
            
    //         // Assuming $resume->resume_json is a JSON string
    //         $jsonData = $resume->resume_json;
            
                        
    //         curl_setopt_array($ch, [
    //             CURLOPT_URL => 'https://job-fso4.onrender.com/ANALYSER',
    //             CURLOPT_RETURNTRANSFER => true,
    //             CURLOPT_POST => true,
    //             CURLOPT_POSTFIELDS => $jsonData,
    //             CURLOPT_HTTPHEADER => [
    //                 'Accept: application/json',
    //                 'Content-Type: application/json', // This is CRITICAL
    //             ],
    //         ]);
            
    //         $response = curl_exec($ch);
            
           
       
    //         if (curl_errno($ch)) {
    //              return response()->json([
    //             'status' => false,
    //             'message' =>curl_error($ch),
                
    //         ]);
              
    //         }
    //      $data=['resume_name'=>$resume->resume_name,
    //       'resume_json'=>$response];
          
    //         $resume=new AIAnalysisResume();
    //         $resume->bash_id=Str::uuid();
    //         $resume->jobseeker_id=$auth->id;
    //         $resume->resume_generate_id=$request->id;
    //         $resume->ai_analysis=$response;
    //         $resume->save();
    //         curl_close($ch);
          
        
    //         return response()->json([
    //             'status' => true,
    //             'message' =>'Analysis Submitted',
    //              'data'=>$data
    //         ]);
    //     }
    // }
  public function submit_ai_resume_analysis(Request $request)
{
    $auth = JWTAuth::user();
    if (!$auth) {
        return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
    }

    $validator = Validator::make($request->all(), [
        'id' => 'required',
        'bash_id' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => $validator->errors(),
        ], 422);
    }

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

    $check_analysis = AIAnalysisResume::where('resume_generate_id', $request->id)
        ->where('jobseeker_id', $auth->id)
        ->first();

    if ($check_analysis) {
        return response()->json([
            'status' => true,
            'message' => 'Already analysis',
            'data' => [
                'resume_name' => $resume->resume_name,
                'resume_json' => json_decode($check_analysis->ai_analysis, true),
            ]
        ]);
    }

    // Step 1: Send request to external API
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => env('AI_RESUME_ANALYSIS'),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $resume->resume_json,
        CURLOPT_HTTPHEADER => [
            'Accept: application/json',
            'Content-Type: application/json',
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
 
    $structured = $this->transformAnalysisResponse($decoded);

    // Step 3: Save structured analysis directly to DB
    $ai = new AIAnalysisResume();
    $ai->bash_id = Str::uuid();
    $ai->jobseeker_id = $auth->id;
    $ai->resume_generate_id = $request->id;
    $ai->ai_analysis = json_encode($structured, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $ai->save();

    return response()->json([
        'status' => true,
        'message' => 'Analysis Submitted',
        'data' => [
            'resume_name' => $resume->resume_name,
            'resume_json' => $structured,
        ]
    ]);
}
private function transformAnalysisResponse($raw)
{
    $analysis = $raw['analysis'] ?? '';

    if (empty($analysis)) {
        return [
            'resumeTitle' => [],
            'summary' => [],
            'suggestedRoles' => [],
            'additionalSkills' => [],
            'rating' => [],
            'improvements' => [],
            'grammar' => [],
        ];
    }

    $lines = explode("\n", $analysis);
    $data = [
        'resumeTitle' => [],
        'summary' => [],
        'suggestedRoles' => [],
        'additionalSkills' => [],
        'rating' => [],
        'improvements' => [],
        'grammar' => [],
    ];

    $section = '';
    $improvementTemp = [];

    foreach ($lines as $line) {
        $line = trim($line);

        if (stripos($line, 'Resume Title:') === 0) {
            $section = 'resumeTitle';
            $data['resumeTitle'][] = trim(str_replace('Resume Title:', '', $line));
        } elseif (stripos($line, 'Professional Summary:') === 0) {
            $section = 'summary';
        } elseif (stripos($line, 'Suggested Job Role:') === 0) {
            $section = 'suggestedRoles';
        } elseif (stripos($line, 'Additional Skills to Learn:') === 0 || stripos($line, 'Additional Skills:') === 0) {
            $section = 'additionalSkills';
        } elseif (stripos($line, 'Resume Rating:') === 0) {
            $section = 'rating';
        } elseif (stripos($line, 'Resume Improvement:') === 0) {
            $section = 'improvements';
        } elseif (stripos($line, 'Grammar Check:') === 0) {
            $section = 'grammar';
        } else {
            switch ($section) {
                case 'summary':
                    if (!empty($line)) $data['summary'][] = $line;
                    break;
                case 'suggestedRoles':
                    if (preg_match('/^\d+\.\s+/', $line)) {
                        $data['suggestedRoles'][] = $line;
                    }
                    break;
                case 'additionalSkills':
                    if (str_starts_with($line, '-')) {
                        $data['additionalSkills'][] = $line;
                    }
                    break;
                case 'rating':
                    if (!empty($line)) $data['rating'][] = $line;
                    break;
                case 'improvements':
                    if (str_starts_with($line, '-')) {
                        if (!empty($improvementTemp)) {
                            $data['improvements'][] = $improvementTemp;
                            $improvementTemp = [];
                        }
                        $improvementTemp['title'] = $line;
                    } elseif (!empty($line)) {
                        if (!isset($improvementTemp['description'])) {
                            $improvementTemp['description'] = $line;
                        } else {
                            $improvementTemp['description'] .= ' ' . $line;
                        }
                    }
                    break;
                case 'grammar':
                    if (!empty($line)) $data['grammar'][] = $line;
                    break;
            }
        }
    }

    // Push final improvement if in progress
    if (!empty($improvementTemp)) {
        $data['improvements'][] = $improvementTemp;
    }

    return $data;
}


}
