<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\JobSeekerContactDetails;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;


use Illuminate\Support\Facades\Validator;
class JobSeekerProfileController extends Controller
{
    //

    public function personal_info(Request $request)
    {
        $auth = JWTAuth::user();
       
        if (!$auth ) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
        $validator = Validator::make($request->all(), [
            'profilePicture' => 'required',
            'firstName' => 'required',
            'middleName' => 'required',
            'lastName' => 'required',
            'dateOfBirth' => 'required',
            'gender' => 'required',
            'maritalStatus' => 'required',
            'addressLine1' => 'required',
            'country' => 'required',
            'state' => 'required',
            'city'=>'required',
            'zipCode'=>'required',
            'course'=>'required',
            'specialization'=>'required',
            'bloodGroup'=>'required',
            'disability'=>'required',
            'knownLanguages'=>'required',
            'medicalHistory'=>'required',
            'dreamCompany'=>'required',
        ], [
            'profilePicture.required' => 'profilePicture is required.',
            'firstName.required' => 'firstName is required.',
            'middleName.required' => 'middleName is required.',
            'lastName.required' => 'lastName is required.',
            'dateOfBirth.required' => 'dateOfBirth is required.',
            'gender.required' => 'gender is required.',
            'maritalStatus.required' => 'maritalStatus is required.',
            'addressLine1.required' => 'address is required.',
            'country.required' => 'country is required.',
            'state.required' => 'state is required.',
            'city.required' => 'city is required.',
            'zipCode.required' => 'zipCode is required.',
            'course.required' => 'course is required.',
            'specialization.required' => 'specialization is required.',
            'bloodGroup.required' => 'bloodGroup is required.',
            'disability.required'=>'disability is required.',
            'knownLanguages.required'=>'knownLanguages is required.',
            'medicalHistory.required'=>'medicalHistory is required.',
            'dreamCompany.required'=>'dreamCompany is required.'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
               
            ], 422);
        }

        $personal= User::find($auth->id)->where('bash_id','=',$auth->bash_id);
        if ($request->hasFile('profilePicture')) 
        {
            $extension = $request->file('profilePicture')->getClientOriginalExtension();
    
            // Create a unique filename using time and original extension
            $filename = time() . '.' . $extension;
            
            // Store the file and get the path
            $imagePath = $request->file('profilePicture')->storeAs('jobseeker_profile_picture', $filename, 'public'); // Store in 'storage/app/public/ticket_images'
            $personal->profile_picture = $imagePath; // Make sure you have this column in your Ticket model
        }
        $personal->first_name=$request->firstName;
        $personal->middle_name=$request->middleName;
        $personal->last_name=$request->lastName;
        $personal->dob=$request->dateOfBirth;
        $personal->gender=$request->gender;
        $personal->marital_status=$request->maritalStatus;
        $personal->location=$request->addressLine1;
        $personal->blood_group=$request->bloodGroup;
        $personal->disability=$request->disability;
        $personal->language_known=implode(',',$request->knownLanguages);
        $personal->medical_history=$request->medicalHistory;

        $personal->save();

        $check_contact=JobSeekerContactDetails::where('user_id','=',$auth->id)->count();
        if($check_contact==0)
        {
            $contact=new JobSeekerContactDetails();
            $contact->bash_id=Str::uuid();
            $contact->country=$request->country;
            $contact->state=$request->state;
            $contact->city=$request->city;
            $contact->zipcode=$request->zipCode;
            $contact->course=$request->course;
            $contact->primary_specialization=$request->specialization;
            $contact->dream_company=$request->dreamCompany;
            $contact->save();
            return response()->json(['status' => true, 'message' => 'Personal Information Added.'], 200);
        }else{
            $contact=JobSeekerContactDetails::where('user_id','=',$auth->id);
            $contact->country=$request->country;
            $contact->state=$request->state;
            $contact->city=$request->city;
            $contact->zipcode=$request->zipCode;
            $contact->course=$request->course;
            $contact->primary_specialization=$request->specialization;
            $contact->dream_company=$request->dreamCompany;
            $contact->save();
            return response()->json(['status' => true, 'message' => 'Personal Information Updated.'], 200);
        }
    
    }
}
