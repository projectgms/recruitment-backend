<?php

namespace App\Http\Controllers\Recruiter;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RecruiterCompanyController extends Controller
{
    //
    public function company_profile()
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
        $company=Company::select('id','bash_id','name','website','industry','company_size','company_description','locations','company_logo','social_profiles')->where('user_id',$auth->id)->first();
        return response()->json([
            'status' => true,
            'message' => 'Get Company Information.',
            'data' => $company
        ]);
     
    }

    public function update_company_profile(Request $request)
    {
        $auth=JWTAuth::user();
        if(!$auth)
        {
            return response()->json([
                'status'=>false,
                'message'=>'Unauthorized',
            ],401
            );
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'website' => 'required',
            'industry'=>'array|required',
            'locations'=>'array|required',
            'company_size'=>'required',
            'company_description'=>'required',
            'company_logo'=>'required',
            'social_profiles'=>'',
           
        ], [
            'name.required' => 'Company Name is required.',
            'website.required' => 'Website is required.',
            'industry.required'=>'Industry is required.',
            'locations.required'=>'Location is required.',
            'company_size.required'=>'Company Size is required.',
            'company_description.required'=>'Company Description is required.',
            'company_logo.required'=>'Company Logo is required.'
            
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' =>$validator->errors(),
                
            ], 422);
        }
        $disk = env('FILESYSTEM_DISK'); // Default to 'local' if not set in .env

        $company = Company::where('user_id', $auth->id)->where('active', '1')->first();
        if ($company) 
        {
            if ($request->hasFile("company_logo")) {
                if ($company->company_logo) {
                    // Get the file path to delete
                    $existingFilePath = $company->company_logo;
                     if ($disk == 'local') {
                       
                        Storage::disk('public')->delete($existingFilePath);
                    } elseif ($disk == 's3') {
                      
                        Storage::disk('s3')->delete($existingFilePath);
                    }
                }
            
                $extension = $request->file('company_logo')->getClientOriginalExtension();
    
                $filename = time() . '.' . $extension;
       
                if ($disk == 'local') {
                 
                    $imagePath = $request->file('company_logo')->storeAs('company_logo', $filename, 'public');
                } elseif ($disk == 's3') {
              
                   $imagePath = $request->file('company_logo')->storeAs('company_logo', $filename, 's3');
                }
    
                $company->company_logo = $imagePath;
        
            }
            $company->name = $request->name;
            $company->website = $request->website;
            $company->company_size = $request->company_size;
            $company->company_description = $request->company_description;
            $company->industry = $request->industry;
            $company->locations =$request->locations;
            $company->save();
            return response()->json(['status' => true, 'message' => 'Company Information Updated.'], 200);
        }else{
            return response()->json(['status' => false, 'message' => 'Permission denied.']);

        }
    }
}
