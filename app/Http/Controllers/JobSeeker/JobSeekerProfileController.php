<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\JobSeekerContactDetails;
use App\Models\JobSeekerEducationDetails;
use App\Models\JobSeekerProfessionalDetails;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Validator;

class JobSeekerProfileController extends Controller
{
    //

    public function personal_info(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
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
            'city' => 'required',
            'zipCode' => 'required',
            'course' => 'required',
            'specialization' => 'required',
            'bloodGroup' => 'required',
            'disability' => 'required',
            'knownLanguages' => 'required|array', // Ensure it's an array
            'knownLanguages.*' => 'string',
            'medicalHistory' => 'required',
            'dreamCompany' => 'required',
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
            'disability.required' => 'disability is required.',
            'knownLanguages.required' => 'knownLanguages is required.',
            'medicalHistory.required' => 'medicalHistory is required.',
            'dreamCompany.required' => 'dreamCompany is required.'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

        $personal = User::find($auth->id)->where('bash_id', '=', $auth->bash_id)->first();
        if (!$personal) {
            return response()->json(['status' => false, 'message' => 'User not found'], 404);
        }
        if ($request->hasFile('profilePicture')) {
            $extension = $request->file('profilePicture')->getClientOriginalExtension();

            // Create a unique filename using time and original extension
            $filename = time() . '.' . $extension;

            // Store the file and get the path
            $imagePath = $request->file('profilePicture')->storeAs('jobseeker_profile_picture', $filename, 'public'); // Store in 'storage/app/public/ticket_images'
            $personal->profile_picture = $imagePath; // Make sure you have this column in your Ticket model
        }

        $personal->first_name = $request->firstName;
        $personal->middle_name = $request->middleName;
        $personal->last_name = $request->lastName;
        $personal->dob = $request->dateOfBirth;
        $personal->gender = $request->gender;
        $personal->marital_status = $request->maritalStatus;
        $personal->location = $request->addressLine1;
        $personal->blood_group = $request->bloodGroup;
        $personal->disability = $request->disability;

        $personal->language_known = implode(',', $request->knownLanguages);

        $personal->medical_history = $request->medicalHistory;

        $personal->save();

        $check_contact = JobSeekerContactDetails::where('user_id', '=', $auth->id)->count();
        if ($check_contact == 0) {

            $contact = new JobSeekerContactDetails();
            $contact->bash_id = Str::uuid();
            $contact->user_id = $auth->id;
            $contact->country = $request->country;
            $contact->state = $request->state;
            $contact->city = $request->city;
            $contact->zipcode = $request->zipCode;
            $contact->course = $request->course;
            $contact->primary_specialization = $request->specialization;
            $contact->dream_company = $request->dreamCompany;

            $contact->save();
            return response()->json(['status' => true, 'message' => 'Personal Information Added.'], 200);
        } else {

            $contact = JobSeekerContactDetails::where('user_id', '=', $auth->id)->first();
            $contact->country = $request->country;
            $contact->state = $request->state;
            $contact->city = $request->city;
            $contact->zipcode = $request->zipCode;
            $contact->course = $request->course;
            $contact->primary_specialization = $request->specialization;
            $contact->dream_company = $request->dreamCompany;
            $contact->save();
            return response()->json(['status' => true, 'message' => 'Personal Information Updated.'], 200);
        }
    }

    public function get_personal_info()
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $personal_data = User::select('users.*', 'job_seeker_contact_details.country', 'job_seeker_contact_details.state', 'job_seeker_contact_details.city', 'job_seeker_contact_details.zipcode', 'job_seeker_contact_details.course', 'job_seeker_contact_details.primary_specialization', 'job_seeker_contact_details.dream_company')
            ->join('job_seeker_contact_details', 'users.id', '=', 'job_seeker_contact_details.user_id')
            ->where('users.id', $auth->id)

            ->first();
        return response()->json([
            'status' => true,
            'message' => 'Get Personal Information.',
            'data' => $personal_data
        ]);
    }

    public function contact_details(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'secondaryPhone' => 'required',
            'otherEmail' => 'required',
            'linkedInUrl' => 'required',
            'githubUrl' => 'required',

        ], [
            'secondaryPhone.required' => 'Secondary mobile Number is required.',
            'otherEmail.required' => 'Other Email is required.',
            'linkedInUrl.required' => 'Linkedin Url is required.',
            'githubUrl.required' => 'Github Url is required.',

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

        $contact = JobSeekerContactDetails::where('user_id', '=', $auth->id)->first();
        if ($contact) {
            $contact->secondary_mobile = $request->secondaryPhone;
            $contact->secondary_email = $request->otherEmail;
            $contact->linkedin_url = $request->linkedInUrl;
            $contact->github_url = $request->githubUrl;
            $contact->save();
            return response()->json(['status' => false, 'message' => 'Contact Details Updated']);
        } else {
            $contact = new JobSeekerContactDetails();
            $contact->secondary_mobile = $request->secondaryPhone;
            $contact->secondary_email = $request->otherEmail;
            $contact->linkedin_url = $request->linkedInUrl;
            $contact->github_url = $request->githubUrl;
            $contact->save();
            return response()->json(['status' => false, 'message' => 'Contact Details Added']);
        }
    }

    public function get_contact_details()
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $contact_data = JobSeekerContactDetails::select('job_seeker_contact_details.secondary_mobile', 'job_seeker_contact_details.secondary_email', 'job_seeker_contact_details.linkedin_url', 'job_seeker_contact_details.github_url')

            ->where('job_seeker_contact_details.user_id', $auth->id)

            ->first();
        return response()->json([
            'status' => true,
            'message' => 'Get Contact Information.',
            'data' => $contact_data
        ]);
    }

    public function add_document(Request $request)
    {

        try {
            // Authenticate user
            $auth = JWTAuth::user();
            if (!$auth) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'documents' => 'required|array',
                'documents.*.type' => 'required|string',
                'documents.*.document' => 'nullable|file|mimes:jpeg,png,jpg,gif,pdf|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }


            $documents = [];

            // Handle file uploads
            $i = 1;
            foreach ($request->documents as $key => $document) {
                $filePath = null;

                if ($request->hasFile("documents.$key.document")) {
                    $oldFilePath = $document['document']; // Assuming the document field contains the current file path

                    // Delete the old file if it exists and it's not null
                    if ($oldFilePath && Storage::exists('public/' . $oldFilePath)) {
                        Storage::delete('public/' . $oldFilePath);
                    }
                    $file = $request->file("documents.$key.document");
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('jobseeker_documents', $filename, 'public');
                }

                // Store JSON data with updated file path
                $documents[] = [
                    'doc_id' => $i,
                    'type' => $document['type'],
                    'document' => $filePath ? $filePath : null,
                ];
                $i++;
            }
            $userDocument = JobSeekerEducationDetails::where('user_id', $auth->id)->first();

            if ($userDocument) {
                // Update the educations column with new data
                $userDocument->documents = $documents;
                $userDocument->save();
            } else {
                // If no existing record, create a new one
                $userDocument = JobSeekerEducationDetails::create([
                    'user_id' => $auth->id,
                    'bash_id' => Str::uuid(),
                    'documents' => $documents, // Store the array directly
                ]);
            }


            return response()->json([
                'status' => true,
                'message' => 'Documents uploaded successfully!',

            ], 201);
        } catch (\Exception $e) {

            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function delete_document(Request $request)
    {
        try {
            $auth = JWTAuth::user();
            if (!$auth) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'doc_id' => 'required',

            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }
            $doc_id = $request->doc_id;

            // Find the user document record
            $userDocument = JobSeekerEducationDetails::select('documents')->where('user_id', $auth->id)->first();

            if (!$userDocument) {
                return response()->json(['status' => false, 'message' => 'Document not found.'], 404);
            }

            $documents = json_decode($userDocument->documents, true);

            // Ensure that documents is an array and not a string
            if (!is_array($documents)) {
                return response()->json(['status' => false, 'message' => 'Documents field is not an array.'], 422);
            }

            $documentToDelete = collect($documents)->firstWhere('doc_id', $doc_id);


            if (!$documentToDelete) {
                return response()->json(['status' => false, 'message' => 'Document ID not found.'], 404);
            }

            // Delete the file from storage
            $filePath = $documentToDelete['document']; // The path of the file in the 'document' field
            if (Storage::exists('public/' . $filePath)) {
                // If the file exists, delete it
                Storage::delete('public/' . $filePath);
            }

            // Remove the document from the documents array
            $updatedDocuments = collect($documents)->reject(function ($item) use ($doc_id) {
                return $item['doc_id'] == $doc_id;
            })->values()->all();


            // Re-encode the documents array to JSON and save it back to the database
            $userDocument->documents = json_encode($updatedDocuments);
            $userDocument->save();


            return response()->json([
                'status' => true,
                'message' => 'Document deleted successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function get_document()
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $userDocument = JobSeekerEducationDetails::select('documents')->where('user_id', $auth->id)->first();
        return response()->json([
            'status' => true,
            'message' => 'Document List',
            'data'=>$userDocument
        ], 200);

    }

    public function add_professional_exp(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'experiences' => 'required|array',
          
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }

        $experiences = [];

        // Handle file uploads
        $i = 1;
        foreach ($request->experiences as $key => $experience) {
           
            // Store JSON data with updated file path
            $experiences[] = [
                "exp_id" => $i,
                "designation"=>$experience['designation'] ? $experience['designation'] : null,
                "organisation"=>$experience['organisation'] ? $experience['organisation'] : null,
                "industrySector"=>$experience['industrySector'] ? $experience['industrySector'] : null,
                "department"=>$experience['department'] ? $experience['department'] : null,
                "city"=>$experience['city'] ? $experience['city'] : null,
                "country"=>$experience['country'] ? $experience['country'] : null,
                "state"=>$experience['state'] ? $experience['state'] : null,
                "ctc"=>$experience['ctc'] ? $experience['ctc'] : null,
                "currentlyWorking"=>$experience['currentlyWorking']=='1' ? 'true' : 'false',
                "skills"=>$experience['skills'] ? $experience['skills'] : null,
                "from"=>$experience['from'] ? $experience['from'] : null,
                "to"=>$experience['to'] ? $experience['to'] : null,
                "description"=>$experience['description'] ? $experience['description'] : null
            ];
            $i++;
        }
        $userExp = JobSeekerProfessionalDetails::where('user_id', $auth->id)->first();

       
        if ($userExp) {
            $existingExperiences = $userExp->experience;

            // Merge new experiences with the existing ones to avoid duplicates
            $userExp->experience = array_merge($existingExperiences, $experiences);
    
            // Update the educations column with new data
           // $userExp->experience = $experiences;
            $userExp->save();
        } else {
            // If no existing record, create a new one
            $userExp = JobSeekerProfessionalDetails::create([
                'user_id' => $auth->id,
                'bash_id' => Str::uuid(),
                'experience' => $experiences, // Store the array directly
            ]);
        }


        return response()->json([
            'status' => true,
            'message' => 'Experience Added successfully!',

        ], 201);
    }

    public function get_professional_exp()
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $userexp = JobSeekerProfessionalDetails::select('experience')->where('user_id', $auth->id)->first();
        return response()->json([
            'status' => true,
            'message' => 'Experience List',
            'data'=>json_decode($userexp)
        ], 200);

    }

    public function update_professional_exp(Request $request)
    {
        echo 'hii';
    }
}
