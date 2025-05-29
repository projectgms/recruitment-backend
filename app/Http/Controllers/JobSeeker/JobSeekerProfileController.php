<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\GenerateResume;
use App\Models\JobSeekerContactDetails;
use App\Models\JobSeekerEducationDetails;
use App\Models\JobSeekerProfessionalDetails;
use App\Models\AIAnalysisResume;
use App\Models\CandidateReview;
use Illuminate\Http\Request;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Jobs;
use Illuminate\Support\Facades\Cache;


use Illuminate\Support\Facades\Storage;

use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Validator;

class JobSeekerProfileController extends Controller
{
    //

    public function personal_info(Request $request)
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
            'profilePicture' => 'required',
            'firstName' => 'required',
            
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
         
 'work_status' => 'required',
            'totalExpYear' => 'required',
            'totalExpMonth' => 'required',
        ], [
            'profilePicture.required' => 'profilePicture is required.',
            'firstName.required' => 'firstName is required.',
         
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
         
'work_status.required'=>'work status required',
            'totalExpYear.required' => 'total year Exp is required',
            'totalExpMonth.required' => 'total month Exp is required',
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
            if ($personal->profile_picture) {
                // Get the file path to delete
                $existingFilePath = $personal->profile_picture;

                // Determine which disk to use and delete the file accordingly
                if ($disk == 'local') {
                    // Delete from local disk
                    Storage::disk('public')->delete($existingFilePath);
                } elseif ($disk == 's3') {
                    // Delete from S3 disk
                    Storage::disk('s3')->delete($existingFilePath);
                }
            }

            $extension = $request->file('profilePicture')->getClientOriginalExtension();

            // Create a unique filename using time and the original extension
            $filename = time() . '.' . $extension;

            // Check which disk is selected and store the file accordingly
            if ($disk == 'local') {

                // Store the file on the local disk under the 'jobseeker_profile_picture' folder
                $imagePath = $request->file('profilePicture')->storeAs('jobseeker_profile_picture', $filename, 'public');
            } elseif ($disk == 's3') {

                // Store the file on the S3 disk under the 'jobseeker_profile_picture' folder
                $imagePath = $request->file('profilePicture')->storeAs('jobseeker_profile_picture', $filename, 's3');
            }

            // Save the file path to the profile_picture column in the model
            $personal->profile_picture = $imagePath;
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
$contact->work_status = $request->work_status;
            $contact->total_year_exp = $request->totalExpYear;
            $contact->total_month_exp = $request->totalExpMonth;

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
            $contact->work_status = $request->work_status;

            $contact->total_year_exp = $request->totalExpYear;
            $contact->total_month_exp = $request->totalExpMonth;
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

        $personal_data = User::select('users.*','job_seeker_contact_details.work_status','job_seeker_contact_details.total_year_exp', 'job_seeker_contact_details.total_month_exp', 'job_seeker_contact_details.country', 'job_seeker_contact_details.state', 'job_seeker_contact_details.city', 'job_seeker_contact_details.zipcode', 'job_seeker_contact_details.course', 'job_seeker_contact_details.primary_specialization', 'job_seeker_contact_details.dream_company')
            ->join('job_seeker_contact_details', 'users.id', '=', 'job_seeker_contact_details.user_id')
            ->where('users.id', $auth->id)

            ->first();
       $disk = env('FILESYSTEM_DISK'); // Default to 'local' if not set in .env

           if($personal_data)
           {
            if ($personal_data->profile_picture) {
                if ($disk=== 's3') {
                    // For S3, use Storage facade with the 's3' disk
                    $personal_data->profile_picture = Storage::disk('s3')->url($personal_data->profile_picture);
                } else {
                    // Default to local
                    $personal_data->profile_picture = env('APP_URL') . Storage::url('app/public/' .$personal_data->profile_picture);
                }
              
            } else {
                // If no logo exists, set it to null or a default image URL
                $personal_data->profile_picture = null; // Replace with a default image URL if needed
            }

        return response()->json([
            'status' => true,
            'message' => 'Get Personal Information.',
            'data' => $personal_data
        ]);
           }else{
               return response()->json([
            'status' => true,
            'message' => 'Get Personal Information.',
            'data' => []
        ]);
           }
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
           

        ], [
            'secondaryPhone.required' => 'Secondary mobile Number is required.',
            'otherEmail.required' => 'Other Email is required.',
           

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
            return response()->json(['status' => true, 'message' => 'Contact Details Updated']);
        } else {
            $contact = new JobSeekerContactDetails();
            $contact->user_id = $auth->id;
            $contact->bash_id = Str::uuid();
            $contact->secondary_mobile = $request->secondaryPhone;
            $contact->secondary_email = $request->otherEmail;
            $contact->linkedin_url = $request->linkedInUrl;
            $contact->github_url = $request->githubUrl;
            $contact->save();
            return response()->json(['status' => true, 'message' => 'Contact Details Added']);
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
            // ✅ Authenticate user
            $auth = JWTAuth::user();
            if (!$auth) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }
        
            // ✅ Validate input
            $validator = Validator::make($request->all(), [
                'documents' => 'required'
            ]);
        
            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }
        
            $input = $request->documents;
        
            // ✅ Normalize to array
            if (!is_array($input) || !array_is_list($input)) {
                $input = [$input];
            }
        
            $newDocuments = [];
        
            foreach ($input as $index => $doc) {
                $filePath = null;
        
                // ✅ Check if this document has a file uploaded (documents.0.file, documents.1.file, etc.)
                if ($request->hasFile("documents.file")) {
                    $file = $request->file("documents.file");
                    $filename = time() . '_' . $file->getClientOriginalName();
                    $filePath = $file->storeAs('jobseeker_documents', $filename, 'public');
                }
        
                $newDocuments[] = [
                    "doc_id" => null,
                    "type"    => $doc['type'] ?? 'Unknown',
                    "file"    => $filePath ?? $doc['file'] ?? null, // fallback if no new upload
                ];
            }
        
            // ✅ Get existing documents
            $userDocument = JobSeekerEducationDetails::where('user_id', $auth->id)->first();
            $existingDocuments = $userDocument ? json_decode($userDocument->documents, true) : [];
        
            if (!is_array($existingDocuments)) {
                $existingDocuments = [];
            }
        
            // ✅ Assign next docu_ids
            $lastId = collect($existingDocuments)->pluck('doc_id')->max() ?? 0;
        
            foreach ($newDocuments as &$doc) {
                $lastId++;
                $doc['doc_id'] = $lastId;
            }
        
            $finalData = array_merge($existingDocuments, $newDocuments);
        
            // ✅ Save or update
            if ($userDocument) {
                $userDocument->documents = json_encode($finalData, JSON_PRETTY_PRINT);
                $userDocument->save();
            } else {
                JobSeekerEducationDetails::create([
                    'user_id' => $auth->id,
                    'bash_id' => Str::uuid(),
                    'documents' => json_encode($finalData, JSON_PRETTY_PRINT),
                ]);
            }
        
            return response()->json([
                'status' => true,
                'message' => 'Documents added successfully!',
            ], 200);
        
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
                'doc_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }

            $doc_id = (int) $request->doc_id; // Ensure it's an integer

            // Fetch user's education records
            $userDocument= JobSeekerEducationDetails::select('documents', 'user_id', 'id')
                ->where('user_id', $auth->id)
                ->first();

            if (!$userDocument) {
                return response()->json(['status' => false, 'message' => 'Document record not found.'], 404);
            }

            // Decode the education JSON data
            $documents = json_decode($userDocument->documents, true);

            if (!is_array($documents)) {
                return response()->json(['status' => false, 'message' => 'Documents data is corrupted.'], 422);
            }

            // Debugging: Check available education IDs

            // Find the index where `education_id` matches inside `data`
            $index = collect($documents)->search(fn($document) => isset($document['doc_id']) && (int) $document['doc_id'] === $doc_id);

            if ($index === false) {
                return response()->json([
                    'status' => false,
                    'message' => "Education ID $doc_id not found.",
                    'available_doc_ids' => collect($documents)->pluck('doc_id')->toArray()
                ], 404);
            }

            // Remove the education record
            unset($documents[$index]);

            // Re-index the array (to avoid missing index numbers)
            $documents = array_values($documents);

            // Save updated educations
            $userDocument->documents = json_encode($documents, JSON_PRETTY_PRINT);
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

        $userDocument = JobSeekerEducationDetails::where('user_id', $auth->id)->first();

    if (!$userDocument || !$userDocument->documents) {
        return response()->json([
            'status' => true,
            'message' => 'No documents found',
            'data' => []
        ], 200);
    }

    // Decode documents JSON
    $documents = json_decode($userDocument->documents, true);

    // Append full file path
    foreach ($documents as &$doc) {
        if (!empty($doc['file'])) {
           
            $doc['file'] = env('APP_URL') . Storage::url('app/public/' .$doc['file']);
    }
    }
        return response()->json([
            'status' => true,
            'message' => 'Document List',
            'data' => $documents
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
                "designation" => $experience['designation'] ? $experience['designation'] : null,
                "organisation" => $experience['organisation'] ? $experience['organisation'] : null,
                "industrySector" => $experience['industrySector'] ? $experience['industrySector'] : null,
                "department" => $experience['department'] ? $experience['department'] : null,
                "city" => $experience['city'] ? $experience['city'] : null,
                "country" => $experience['country'] ? $experience['country'] : null,
                "state" => $experience['state'] ? $experience['state'] : null,
                "ctc" => $experience['ctc'] ? $experience['ctc'] : null,
                "currentlyWorking" => $experience['currentlyWorking'] == '1' ? true : false,
                "skills" => $experience['skills'] ? $experience['skills'] : null,
                "from" => $experience['from'] ? $experience['from'] : null,
                "to" => $experience['to'] ? $experience['to'] : null,
                "description" => $experience['description'] ? $experience['description'] : null
            ];
            $i++;
        }
        $userExp = JobSeekerProfessionalDetails::where('user_id', $auth->id)->first();


        if ($userExp) {

            $existingExperiences = json_decode($userExp->experience, true);

            // Check if the internship field is empty or not in array format
            if (!is_array($existingExperiences)) {
                $existingExperiences = []; // Initialize as empty array if it's not a valid array
            }


            if (!empty($existingExperiences)) {
                // Find the maximum education_id from the existing array
                $maxExperienceId = max(array_column($existingExperiences, 'exp_id')) ?? 0;
            } else {
                // If empty, set a default education_id
                $maxExperienceId = 0;
            }
            // Assign new certification_ids starting from the max + 1
            foreach ($experiences as &$newExperience) {
                $maxExperienceId++;
                $newExperience['exp_id'] = $maxExperienceId;
            }

            // Merge the existing certifications with the new ones
            $userExp->experience = json_encode(array_merge($existingExperiences, $experiences), JSON_PRETTY_PRINT);
            //   $userExp->experience = $experiences;
            // Save the updated certifications back to the database
            $userExp->save();
        } else {
            // If no existing record, create a new one
            $userExp = JobSeekerProfessionalDetails::create([
                'user_id' => $auth->id,
                'bash_id' => Str::uuid(),
                'experience' => json_encode($experiences), // Store the array directly
            ]);
        }


        return response()->json([
            'status' => true,
            'message' => 'Experience Added successfully!',

        ], 200);
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
            'data' => json_decode($userexp)
        ], 200);
    }

    public function update_professional_exp(Request $request)
    {
        try {
            $auth = JWTAuth::user();
            if (!$auth) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'exp_id' => 'required|integer',
                'experience' => 'required|array', // Make sure experience data is passed in the request
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }

            $exp_id = $request->exp_id;
            $newExperienceData = $request->experience; // The updated experience data

            // Find the user document record
            $userexp = JobSeekerProfessionalDetails::select('experience', 'user_id', 'id')->where('user_id', $auth->id)->first();
            if (!$userexp) {
                return response()->json(['status' => false, 'message' => 'Document not found.'], 404);
            }

            // Decode the existing experience JSON data
            $experiences = json_decode($userexp->experience, true);

            // Ensure that experience is an array and not a string
            if (!is_array($experiences)) {
                return response()->json(['status' => false, 'message' => 'Experience field is not an array.'], 422);
            }

            // Find the specific experience record by exp_id
            $find_exp_id = collect($experiences)->firstWhere('exp_id', $exp_id);

            if (!$find_exp_id) {
                return response()->json(['status' => false, 'message' => 'Experience not found.'], 404);
            }

            // Find the index of the experience
            $index = collect($experiences)->search(function ($experience) use ($exp_id) {
                return $experience['exp_id'] === $exp_id;
            });

            // Merge the new data with the existing experience data
            $experiences[$index] = array_merge($experiences[$index], $newExperienceData);


            // Save the updated experiences back to the database
            $userexp->experience = json_encode($experiences, JSON_PRETTY_PRINT);
            $userexp->save();

            return response()->json([
                'status' => true,
                'message' => 'Experience updated successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function delete_professional_exp(Request $request)
    {
        try {
            $auth = JWTAuth::user();
            if (!$auth) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'exp_id' => 'required',

            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }
            $exp_id = $request->exp_id;

            // Find the user document record
            $userExp = JobSeekerProfessionalDetails::select('experience', 'user_id', 'id')->where('user_id', $auth->id)->first();

            if (!$userExp) {
                return response()->json(['status' => false, 'message' => 'Experience not found.'], 404);
            }

            $experiences = json_decode($userExp->experience, true);

            // Ensure that documents is an array and not a string
            if (!is_array($experiences)) {
                return response()->json(['status' => false, 'message' => 'Experience field is not an array.'], 422);
            }

            $experienceToDelete = collect($experiences)->firstWhere('exp_id', $exp_id);


            if (!$experienceToDelete) {
                return response()->json(['status' => false, 'message' => 'experience ID not found.'], 404);
            }


            // Remove the document from the documents array
            $updatedExperiences = collect($experiences)->reject(function ($item) use ($exp_id) {
                return $item['exp_id'] == $exp_id;
            })->values()->all();


            // Re-encode the documents array to JSON and save it back to the database
            $userExp->experience = json_encode($updatedExperiences);
            $userExp->save();


            return response()->json([
                'status' => true,
                'message' => 'Experience deleted successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function add_internship(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'internships' => 'required|array',

        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }


        $internships = [];

        // Handle file uploads
        $i = 1;
        foreach ($request->internships as $key => $internship) {

            // Store JSON data with updated file path
            $internships[] = [
                "internship_id" => $i,
                "title" => $internship['title'] ? $internship['title'] : null,
                "organisation" => $internship['organisation'] ? $internship['organisation'] : null,
                "industrySector" => $internship['industrySector'] ? $internship['industrySector'] : null,
                "department" => $internship['department'] ? $internship['department'] : null,
                "stipend" => $internship['stipend'] ? $internship['stipend'] : null,
                "city" => $internship['city'] ? $internship['city'] : null,
                "country" => $internship['country'] ? $internship['country'] : null,
                "state" => $internship['state'] ? $internship['state'] : null,

                "currentlyWorking" => $internship['currentlyWorking'] == '1' ? true : false,
                "skills" => $internship['skills'] ? $internship['skills'] : null,
                "from" => $internship['from'] ? $internship['from'] : null,
                "to" => $internship['to'] ? $internship['to'] : null,
                "description" => $internship['description'] ? $internship['description'] : null
            ];
            $i++;
        }

        $userInternship = JobSeekerProfessionalDetails::where('user_id', $auth->id)->first();

        if ($userInternship) {
            $existingInternships = json_decode($userInternship->internship, true);

            // Check if the internship field is empty or not in array format
            if (!is_array($existingInternships)) {
                $existingInternships = []; // Initialize as empty array if it's not a valid array
            }


            if (!empty($existingInternships)) {
                // Find the maximum education_id from the existing array
                $maxInternshipId = max(array_column($existingInternships, 'internship_id')) ?? 0;
            } else {
                // If empty, set a default education_id
                $maxInternshipId = 0;
            }
            // Assign new certification_ids starting from the max + 1
            foreach ($internships as &$newInternship) {
                $maxInternshipId++;
                $newInternship['internship_id'] = $maxInternshipId;
            }

            // Merge the existing certifications with the new ones
            $userInternship->internship = json_encode(array_merge($existingInternships, $internships), JSON_PRETTY_PRINT);
            //   $userExp->experience = $experiences;
            // Save the updated certifications back to the database
            $userInternship->save();
            // Merge new experiences with the existing ones to avoid duplicates

        } else {
            // If no existing record, create a new one
            $userInternship = JobSeekerProfessionalDetails::create([
                'user_id' => $auth->id,
                'bash_id' => Str::uuid(),
                'internship' => json_encode($internships), // Store the array directly
            ]);
        }


        return response()->json([
            'status' => true,
            'message' => 'Internship Added successfully!',

        ], 200);
    }

    public function get_internship()
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $userInternship = JobSeekerProfessionalDetails::select('internship')->where('user_id', $auth->id)->first();
        return response()->json([
            'status' => true,
            'message' => 'Internship List',
            'data' => json_decode($userInternship)
        ], 200);
    }

    public function update_internship(Request $request)
    {
        try {
            $auth = JWTAuth::user();
            if (!$auth) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'internship_id' => 'required|integer',
                'internships' => 'required|array', // Make sure experience data is passed in the request
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }

            $internship_id = $request->internship_id;
            $newInternshipData = $request->internships; // The updated experience data

            // Find the user document record
            $userInternship = JobSeekerProfessionalDetails::select('internship', 'user_id', 'id')->where('user_id', $auth->id)->first();
            if (!$userInternship) {
                return response()->json(['status' => false, 'message' => 'Internship not found.'], 404);
            }

            // Decode the existing experience JSON data
            $internships = json_decode($userInternship->internship, true);

            // Ensure that experience is an array and not a string
            if (!is_array($internships)) {
                return response()->json(['status' => false, 'message' => 'Internship field is not an array.'], 422);
            }

            // Find the specific experience record by exp_id
            $find_internship_id = collect($internships)->firstWhere('internship_id', $internship_id);

            if (!$find_internship_id) {
                return response()->json(['status' => false, 'message' => 'Internship not found.'], 404);
            }

            // Find the index of the experience
            $index = collect($internships)->search(function ($internship) use ($internship_id) {
                return $internship['internship_id'] === $internship_id;
            });

            // Merge the new data with the existing experience data
            $internships[$index] = array_merge($internships[$index], $newInternshipData);


            // Save the updated experiences back to the database
            $userInternship->internship = json_encode($internships, JSON_PRETTY_PRINT);
            $userInternship->save();

            return response()->json([
                'status' => true,
                'message' => 'Internship updated successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function delete_internship(Request $request)
    {
        try {
            $auth = JWTAuth::user();
            if (!$auth) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'internship_id' => 'required',

            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }
            $internship_id = $request->internship_id;

            // Find the user document record
            $userInternship = JobSeekerProfessionalDetails::select('internship', 'user_id', 'id')->where('user_id', $auth->id)->first();

            if (!$userInternship) {
                return response()->json(['status' => false, 'message' => 'Internship not found.'], 404);
            }

            $internships = json_decode($userInternship->internship, true);

            // Ensure that documents is an array and not a string
            if (!is_array($internships)) {
                return response()->json(['status' => false, 'message' => 'Internship field is not an array.'], 422);
            }

            $internshipToDelete = collect($internships)->firstWhere('internship_id', $internship_id);


            if (!$internshipToDelete) {
                return response()->json(['status' => false, 'message' => 'internship ID not found.'], 404);
            }


            // Remove the document from the documents array
            $updatedInternship = collect($internships)->reject(function ($item) use ($internship_id) {
                return $item['internship_id'] == $internship_id;
            })->values()->all();


            // Re-encode the documents array to JSON and save it back to the database
            $userInternship->internship = json_encode($updatedInternship);
            $userInternship->save();


            return response()->json([
                'status' => true,
                'message' => 'Internship deleted successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function add_project(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'projects' => 'required|array',

        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }


        $projects = [];

        // Handle file uploads
        $i = 1;
        foreach ($request->projects as $key => $project) {

            // Store JSON data with updated file path
            $projects[] = [
                "project_id" => $i,
                "name" => $project['name'] ? $project['name'] : null,
                "projectLink" => $project['projectLink'] ? $project['projectLink'] : null,
                "from" => $project['from'] ? $project['from'] : null,
                "to" => $project['to'] ? $project['to'] : null,
                "mentor" => $project['mentor'] ? $project['mentor'] : null,
                "teamSize" => $project['teamSize'] ? $project['teamSize'] : null,
                "skills" => $project['skills'] ? $project['skills'] : null,
                "description" => $project['description'] ? $project['description'] : null
            ];
            $i++;
        }

        $userProject = JobSeekerProfessionalDetails::where('user_id', $auth->id)->first();

        if ($userProject) {
            $existingProject = json_decode($userProject->projects, true);

            // Check if the internship field is empty or not in array format
            if (!is_array($existingProject)) {
                $existingProject = []; // Initialize as empty array if it's not a valid array
            }


            if (!empty($existingProject)) {
                // Find the maximum education_id from the existing array
                $maxProjectId = max(array_column($existingProject, 'project_id')) ?? 0;
            } else {
                // If empty, set a default education_id
                $maxProjectId = 0;
            }
            // Assign new certification_ids starting from the max + 1
            foreach ($projects as &$newProject) {
                $maxProjectId++;
                $newProject['project_id'] = $maxProjectId;
            }

            // Merge the existing certifications with the new ones
            $userProject->projects = json_encode(array_merge($existingProject, $projects), JSON_PRETTY_PRINT);
            //   $userExp->experience = $experiences;
            // Save the updated certifications back to the database
            $userProject->save();
        } else {
            // If no existing record, create a new one
            $userProject = JobSeekerProfessionalDetails::create([
                'user_id' => $auth->id,
                'bash_id' => Str::uuid(),
                'projects' => json_encode($projects), // Store the array directly
            ]);
        }


        return response()->json([
            'status' => true,
            'message' => 'Project Added successfully!',

        ], 200);
    }

    public function get_project()
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $userProject = JobSeekerProfessionalDetails::select('projects')->where('user_id', $auth->id)->first();
        return response()->json([
            'status' => true,
            'message' => 'Projects List',
            'data' => json_decode($userProject)
        ], 200);
    }

    public function update_project(Request $request)
    {
        try {
            $auth = JWTAuth::user();
            if (!$auth) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'project_id' => 'required|integer',
                'projects' => 'required|array', // Make sure experience data is passed in the request
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }

            $project_id = $request->project_id;
            $newProjectData = $request->projects; // The updated experience data

            // Find the user document record
            $userProject = JobSeekerProfessionalDetails::select('projects', 'user_id', 'id')->where('user_id', $auth->id)->first();
            if (!$userProject) {
                return response()->json(['status' => false, 'message' => 'Internship not found.'], 404);
            }

            // Decode the existing experience JSON data
            $projects = json_decode($userProject->projects, true);

            // Ensure that experience is an array and not a string
            if (!is_array($projects)) {
                return response()->json(['status' => false, 'message' => 'Project field is not an array.'], 422);
            }

            // Find the specific experience record by exp_id
            $find_project_id = collect($projects)->firstWhere('project_id', $project_id);

            if (!$find_project_id) {
                return response()->json(['status' => false, 'message' => 'Internship not found.'], 404);
            }

            // Find the index of the experience
            $index = collect($projects)->search(function ($project) use ($project_id) {
                return $project['project_id'] === $project_id;
            });

            // Merge the new data with the existing experience data
            $projects[$index] = array_merge($projects[$index], $newProjectData);


            // Save the updated experiences back to the database
            $userProject->projects = json_encode($projects, JSON_PRETTY_PRINT);
            $userProject->save();

            return response()->json([
                'status' => true,
                'message' => 'Project updated successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function delete_project(Request $request)
    {
        try {
            $auth = JWTAuth::user();
            if (!$auth) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'project_id' => 'required',

            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }
            $project_id = $request->project_id;

            // Find the user document record
            $userProject = JobSeekerProfessionalDetails::select('projects', 'user_id', 'id')->where('user_id', $auth->id)->first();

            if (!$userProject) {
                return response()->json(['status' => false, 'message' => 'Project not found.'], 404);
            }

            $projects = json_decode($userProject->projects, true);

            // Ensure that documents is an array and not a string
            if (!is_array($projects)) {
                return response()->json(['status' => false, 'message' => 'Project field is not an array.'], 422);
            }

            $projectToDelete = collect($projects)->firstWhere('project_id', $project_id);


            if (!$projectToDelete) {
                return response()->json(['status' => false, 'message' => 'Project ID not found.'], 404);
            }


            // Remove the document from the documents array
            $updatedProject = collect($projects)->reject(function ($item) use ($project_id) {
                return $item['project_id'] == $project_id;
            })->values()->all();


            // Re-encode the documents array to JSON and save it back to the database
            $userProject->projects = json_encode($updatedProject);
            $userProject->save();


            return response()->json([
                'status' => true,
                'message' => 'Project deleted successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function add_research_paper(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'publications' => 'required|array',

        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }


        $publications = [];

        // Handle file uploads
        $i = 1;
        foreach ($request->publications as $key => $publication) {

            // Store JSON data with updated file path
            $publications[] = [
                "publication_id" => $i,
                "name" => $publication['name'] ? $publication['name'] : null,
                "publicationName" => $publication['publicationName'] ? $publication['publicationName'] : null,
                "publicationDate" => $publication['publicationDate'] ? $publication['publicationDate'] : null,
                "mentor" => $publication['mentor'] ? $publication['mentor'] : null,
                "authorsCount" => $publication['authorsCount'] ? $publication['authorsCount'] : null,
                "status" => $publication['status'] ? $publication['status'] : null,
                "skills" => $publication['skills'] ? $publication['skills'] : null,
                "description" => $publication['description'] ? $publication['description'] : null
            ];
            $i++;
        }

        $userPublication = JobSeekerEducationDetails::where('user_id', $auth->id)->first();

        if ($userPublication) {
            $existingPublication = json_decode($userPublication->publications, true);

            // Check if the internship field is empty or not in array format
            if (!is_array($existingPublication)) {
                $existingPublication = []; // Initialize as empty array if it's not a valid array
            }


            if (!empty($existingPublication)) {
                // Find the maximum education_id from the existing array
                $maxPublicationId = max(array_column($existingPublication, 'publication_id')) ?? 0;
            } else {
                // If empty, set a default education_id
                $maxPublicationId = 0;
            }
            // Assign new certification_ids starting from the max + 1
            foreach ($publications as &$newPublication) {
                $maxPublicationId++;
                $newPublication['publication_id'] = $maxPublicationId;
            }

            // Merge the existing certifications with the new ones
            $userPublication->publications = json_encode(array_merge($existingPublication, $publications), JSON_PRETTY_PRINT);
            //   $userExp->experience = $experiences;
            // Save the updated certifications back to the database
            $userPublication->save();
            // Merge new experiences with the existing ones to avoid duplicates

        } else {
            // If no existing record, create a new one
            $userPublication = JobSeekerEducationDetails::create([
                'user_id' => $auth->id,
                'bash_id' => Str::uuid(),
                'publications' => json_encode($publications), // Store the array directly
            ]);
        }


        return response()->json([
            'status' => true,
            'message' => 'Publications Added successfully!',

        ], 200);
    }

    public function get_research_paper()
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $userPublication = JobSeekerEducationDetails::select('publications')->where('user_id', $auth->id)->first();
        return response()->json([
            'status' => true,
            'message' => 'Publication List',
            'data' => json_decode($userPublication)
        ], 200);
    }

    public function update_research_paper(Request $request)
    {
        try {
            $auth = JWTAuth::user();
            if (!$auth) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'publication_id' => 'required|integer',
                'publications' => 'required|array', // Make sure experience data is passed in the request
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }

            $publication_id = $request->publication_id;
            $newPublicationData = $request->publications; // The updated experience data

            // Find the user document record
            $userPublication = JobSeekerEducationDetails::select('publications', 'user_id', 'id')->where('user_id', $auth->id)->first();
            if (!$userPublication) {
                return response()->json(['status' => false, 'message' => 'Publication not found.'], 404);
            }

            // Decode the existing experience JSON data
            $publications = json_decode($userPublication->publications, true);

            // Ensure that experience is an array and not a string
            if (!is_array($publications)) {
                return response()->json(['status' => false, 'message' => 'Project field is not an array.'], 422);
            }

            // Find the specific experience record by exp_id
            $find_publication_id = collect($publications)->firstWhere('publication_id', $publication_id);

            if (!$find_publication_id) {
                return response()->json(['status' => false, 'message' => 'Publication not found.'], 404);
            }

            // Find the index of the experience
            $index = collect($publications)->search(function ($publication) use ($publication_id) {
                return $publication['publication_id'] === $publication_id;
            });

            // Merge the new data with the existing experience data
            $publications[$index] = array_merge($publications[$index], $newPublicationData);


            // Save the updated experiences back to the database
            $userPublication->publications = json_encode($publications, JSON_PRETTY_PRINT);
            $userPublication->save();

            return response()->json([
                'status' => true,
                'message' => 'Publication updated successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function delete_research_paper(Request $request)
    {
        try {
            $auth = JWTAuth::user();
            if (!$auth) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'publication_id' => 'required',

            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }
            $publication_id = $request->publication_id;

            // Find the user document record
            $userPublication = JobSeekerEducationDetails::select('publications', 'user_id', 'id')->where('user_id', $auth->id)->first();

            if (!$userPublication) {
                return response()->json(['status' => false, 'message' => 'Publication not found.'], 404);
            }

            $publications = json_decode($userPublication->publications, true);

            // Ensure that documents is an array and not a string
            if (!is_array($publications)) {
                return response()->json(['status' => false, 'message' => 'Project field is not an array.'], 422);
            }

            $publicationToDelete = collect($publications)->firstWhere('publication_id', $publication_id);


            if (!$publicationToDelete) {
                return response()->json(['status' => false, 'message' => 'Publication ID not found.'], 404);
            }


            // Remove the document from the documents array
            $updatedPublication = collect($publications)->reject(function ($item) use ($publication_id) {
                return $item['publication_id'] == $publication_id;
            })->values()->all();


            // Re-encode the documents array to JSON and save it back to the database
            $userPublication->publications = json_encode($updatedPublication);
            $userPublication->save();


            return response()->json([
                'status' => true,
                'message' => 'Publication deleted successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function add_training(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'trainings' => 'required|array',

        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }


        $trainings = [];

        // Handle file uploads
        $i = 1;
        foreach ($request->trainings as $key => $training) {

            // Store JSON data with updated file path
            $trainings[] = [
                "training_id" => $i,
                "name" => $training['name'] ? $training['name'] : null,
                "instituteName" => $training['instituteName'] ? $training['instituteName'] : null,
                "from" => $training['from'] ? $training['from'] : null,
                "to" => $training['to'] ? $training['to'] : null,
                "skills" => $training['skills'] ? $training['skills'] : null,
                "description" => $training['description'] ? $training['description'] : null
            ];
            $i++;
        }

        $userTraining = JobSeekerEducationDetails::where('user_id', $auth->id)->first();

        if ($userTraining) {
            $existingTrainig = json_decode($userTraining->trainings, true);

            // Check if the internship field is empty or not in array format
            if (!is_array($existingTrainig)) {
                $existingTrainig = []; // Initialize as empty array if it's not a valid array
            }


            if (!empty($existingTrainig)) {
                // Find the maximum education_id from the existing array
                $maxTrainingId = max(array_column($existingTrainig, 'training_id')) ?? 0;
            } else {
                // If empty, set a default education_id
                $maxTrainingId = 0;
            }
            // Assign new certification_ids starting from the max + 1
            foreach ($trainings as &$newTraining) {
                $maxTrainingId++;
                $newTraining['training_id'] = $maxTrainingId;
            }

            // Merge the existing certifications with the new ones
            $userTraining->trainings = json_encode(array_merge($existingTrainig, $trainings), JSON_PRETTY_PRINT);
            //   $userExp->experience = $experiences;
            // Save the updated certifications back to the database
            $userTraining->save();
            // Merge new experiences with the existing ones to avoid duplicates

        } else {
            // If no existing record, create a new one
            $userPublication = JobSeekerEducationDetails::create([
                'user_id' => $auth->id,
                'bash_id' => Str::uuid(),
                'trainings' => json_encode($trainings), // Store the array directly
            ]);
        }


        return response()->json([
            'status' => true,
            'message' => 'Trainings Added successfully!',

        ], 200);
    }
    public function get_training()
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $userTraining = JobSeekerEducationDetails::select('trainings')->where('user_id', $auth->id)->first();
        return response()->json([
            'status' => true,
            'message' => 'Training List',
            'data' => json_decode($userTraining)
        ], 200);
    }

    public function update_training(Request $request)
    {
        try {
            $auth = JWTAuth::user();
            if (!$auth) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'training_id' => 'required|integer',
                'trainings' => 'required|array', // Make sure experience data is passed in the request
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }

            $training_id = $request->training_id;
            $newTrainingData = $request->trainings; // The updated experience data

            // Find the user document record
            $userTraining = JobSeekerEducationDetails::select('trainings', 'user_id', 'id')->where('user_id', $auth->id)->first();
            if (!$userTraining) {
                return response()->json(['status' => false, 'message' => 'Training not found.'], 404);
            }

            // Decode the existing experience JSON data
            $trainings = json_decode($userTraining->trainings, true);

            // Ensure that experience is an array and not a string
            if (!is_array($trainings)) {
                return response()->json(['status' => false, 'message' => 'trainings field is not an array.'], 422);
            }

            // Find the specific experience record by exp_id
            $find_training_id = collect($trainings)->firstWhere('training_id', $training_id);

            if (!$find_training_id) {
                return response()->json(['status' => false, 'message' => 'Training not found.'], 404);
            }

            // Find the index of the experience
            $index = collect($trainings)->search(function ($training) use ($training_id) {
                return $training['training_id'] === $training_id;
            });

            // Merge the new data with the existing experience data
            $trainings[$index] = array_merge($trainings[$index], $newTrainingData);


            // Save the updated experiences back to the database
            $userTraining->trainings = json_encode($trainings, JSON_PRETTY_PRINT);
            $userTraining->save();

            return response()->json([
                'status' => true,
                'message' => 'Training updated successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }
    public function delete_training(Request $request)
    {
        try {
            $auth = JWTAuth::user();
            if (!$auth) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'training_id' => 'required',

            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }
            $training_id = $request->training_id;

            // Find the user document record
            $userTraining = JobSeekerEducationDetails::select('trainings', 'user_id', 'id')->where('user_id', $auth->id)->first();

            if (!$userTraining) {
                return response()->json(['status' => false, 'message' => 'Training not found.'], 404);
            }

            $trainings = json_decode($userTraining->trainings, true);

            // Ensure that documents is an array and not a string
            if (!is_array($trainings)) {
                return response()->json(['status' => false, 'message' => 'training field is not an array.'], 422);
            }

            $trainingToDelete = collect($trainings)->firstWhere('training_id', $training_id);


            if (!$trainingToDelete) {
                return response()->json(['status' => false, 'message' => 'Training ID not found.'], 404);
            }


            // Remove the document from the documents array
            $updatedTraining = collect($trainings)->reject(function ($item) use ($training_id) {
                return $item['training_id'] == $training_id;
            })->values()->all();


            // Re-encode the documents array to JSON and save it back to the database
            $userTraining->trainings = json_encode($updatedTraining);
            $userTraining->save();


            return response()->json([
                'status' => true,
                'message' => 'Training deleted successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function add_certification(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'certifications' => 'required|array',

        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }


        $certifications = [];

        // Handle file uploads
        $i = 1;
        foreach ($request->certifications as $key => $certification) {

            // Store JSON data with updated file path
            $certifications[] = [
                "certification_id" => $i,
                "name" => $certification['name'] ? $certification['name'] : ' ',
                "provider" => $certification['provider'] ? $certification['provider'] : ' ',
                "enrollmentNumber" => $certification['enrollmentNumber'] ? $certification['enrollmentNumber'] : ' ',
                "validUpto" => $certification['validUpto'] ? $certification['validUpto'] : ' ',
                "marksType" => $certification['marksType'] ? $certification['marksType'] : ' ',
                "aggregate" => $certification['aggregate'] ? $certification['aggregate'] : '0',
                "max" => $certification['max'] ? $certification['max'] : '0',
                "certificateLink"=>$certification['certificateLink']?$certification['certificateLink']:'',

                "skills" => $certification['skills'] ? $certification['skills'] : ' ',
                "description" => $certification['description'] ? $certification['description'] : ' ',
            ];
            $i++;
        }

        $userCertification = JobSeekerEducationDetails::where('user_id', $auth->id)->first();

        if ($userCertification) {
            $existingCertification = json_decode($userCertification->certifications, true);

            // Check if the internship field is empty or not in array format
            if (!is_array($existingCertification)) {
                $existingCertification = []; // Initialize as empty array if it's not a valid array
            }


            if (!empty($existingCertification)) {
                // Find the maximum education_id from the existing array
                $maxCertificationId = max(array_column($existingCertification, 'certification_id')) ?? 0;
            } else {
                // If empty, set a default education_id
                $maxCertificationId = 0;
            }

            // Assign new certification_ids starting from the max + 1
            foreach ($certifications as &$newCertification) {
                $maxCertificationId++;
                $newCertification['certification_id'] = $maxCertificationId;
            }

            // Merge the existing certifications with the new ones
            $userCertification->certifications = json_encode(array_merge($existingCertification, $certifications), JSON_PRETTY_PRINT);
            //   $userExp->experience = $experiences;
            // Save the updated certifications back to the database
            $userCertification->save();
        } else {
            // If no existing record, create a new one
            $userCertification = JobSeekerEducationDetails::create([
                'user_id' => $auth->id,
                'bash_id' => Str::uuid(),
                'certifications' => json_encode($certifications), // Store the array directly
            ]);
        }


        return response()->json([
            'status' => true,
            'message' => 'Certifications Added successfully!',

        ], 200);
    }

    public function get_certification()
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $userCertification = JobSeekerEducationDetails::select('certifications')->where('user_id', $auth->id)->first();
        return response()->json([
            'status' => true,
            'message' => 'Certification List',
            'data' => json_decode($userCertification)
        ], 200);
    }


    public function update_certification(Request $request)
    {
        try {
            $auth = JWTAuth::user();
            if (!$auth) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'certification_id' => 'required|integer',
                'certifications' => 'required|array', // Make sure experience data is passed in the request
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }

            $certification_id = $request->certification_id;
            $newCertificationData = $request->certifications; // The updated experience data

            // Find the user document record
            $userCertification = JobSeekerEducationDetails::select('certifications', 'user_id', 'id')->where('user_id', $auth->id)->first();
            if (!$userCertification) {
                return response()->json(['status' => false, 'message' => 'Training not found.'], 404);
            }

            // Decode the existing experience JSON data
            $certifications = json_decode($userCertification->certifications, true);

            // Ensure that experience is an array and not a string
            if (!is_array($certifications)) {
                return response()->json(['status' => false, 'message' => 'Certification field is not an array.'], 422);
            }

            // Find the specific experience record by exp_id
            $find_certification_id = collect($certifications)->firstWhere('certification_id', $certification_id);

            if (!$find_certification_id) {
                return response()->json(['status' => false, 'message' => 'Certification not found.'], 404);
            }

            // Find the index of the experience
            $index = collect($certifications)->search(function ($certification) use ($certification_id) {
                return $certification['certification_id'] === $certification_id;
            });

            // Merge the new data with the existing experience data
            $certifications[$index] = array_merge($certifications[$index], $newCertificationData);


            // Save the updated experiences back to the database
            $userCertification->certifications = json_encode($certifications, JSON_PRETTY_PRINT);
            $userCertification->save();

            return response()->json([
                'status' => true,
                'message' => 'Certification updated successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function delete_certification(Request $request)
    {
        try {
            $auth = JWTAuth::user();
            if (!$auth) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'certification_id' => 'required',

            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }
            $certification_id = $request->certification_id;

            // Find the user document record
            $userCertification = JobSeekerEducationDetails::select('certifications', 'user_id', 'id')->where('user_id', $auth->id)->first();

            if (!$userCertification) {
                return response()->json(['status' => false, 'message' => 'Certification not found.'], 404);
            }

            $certifications = json_decode($userCertification->certifications, true);

            // Ensure that documents is an array and not a string
            if (!is_array($certifications)) {
                return response()->json(['status' => false, 'message' => 'certification field is not an array.'], 422);
            }

            $certificationToDelete = collect($certifications)->firstWhere('certification_id', $certification_id);


            if (!$certificationToDelete) {
                return response()->json(['status' => false, 'message' => 'Certification ID not found.'], 404);
            }


            // Remove the document from the documents array
            $updatedCertification = collect($certifications)->reject(function ($item) use ($certification_id) {
                return $item['certification_id'] == $certification_id;
            })->values()->all();


            // Re-encode the documents array to JSON and save it back to the database
            $userCertification->certifications = json_encode($updatedCertification);
            $userCertification->save();


            return response()->json([
                'status' => true,
                'message' => 'Certification deleted successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }


    public function add_education(Request $request)
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        // Validate input
        $validator = Validator::make($request->all(), [
            'educations' => 'required', // Ensure educations field is present
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => false, 'message' => $validator->errors()], 422);
        }

        $educations = [];

        // Check if input is an object instead of an array and convert it to an array

        $request->educations = [$request->educations];

        // Process new education entries
        foreach ($request->educations as $education) {
            // Ensure 'data' is present and is an array
            if (!isset($education['data']) || !is_array($education['data'])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid education data format. "data" must be an array.'
                ], 422);
            }

            $educations[] = [
                "type" => $education['type'] ?? 'Unknown', // Default value if 'type' is missing
                "data" => [
                    "education_id" => null, // Will be updated dynamically
                    "qualification" => $education['data']['qualification'] ?? null,
                    "stream" => $education['data']['stream'] ?? null,
                    "college" => $education['data']['college'] ?? null,
                    "collegeCity" => $education['data']['collegeCity'] ?? null,
                    "joiningYear" => $education['data']['joiningYear'] ?? null,
                    "completionYear" => $education['data']['completionYear'] ?? null,
                    "graduationType" => $education['data']['graduationType'] ?? null,
                    "aggregateType" => $education['data']['aggregateType'] ?? null,
                    "aggregate" => $education['data']['aggregate'] ?? null,
                    "max" => $education['data']['max'] ?? null,
                    "activeBacklogs" => $education['data']['activeBacklogs'] ?? null
                ]
            ];
        }

        // Fetch the existing education record for the user
        $userEducation = JobSeekerEducationDetails::where('user_id', $auth->id)->first();

        if ($userEducation) {
            // Decode existing education data
            $existingEducation = json_decode($userEducation->educations, true);

            // Ensure existing education is an array
            if (!is_array($existingEducation)) {
                $existingEducation = [];
            }

            // Get the highest existing education_id
            $educationIds = array_column(array_column($existingEducation, 'data'), 'education_id');
            $maxEducationId = !empty($educationIds) ? max($educationIds) : 0;

            // Assign new education_ids and append them
            foreach ($educations as &$newEducation) {
                $maxEducationId++;
                $newEducation['data']['education_id'] = $maxEducationId;
            }

            // Merge the existing and new educations
            $userEducation->educations = json_encode(array_merge($existingEducation, $educations), JSON_PRETTY_PRINT);
            $userEducation->save();
        } else {
            // If no existing record, create a new one
            foreach ($educations as $index => &$newEducation) {
                $newEducation['data']['education_id'] = $index + 1;
            }

            $userEducation = JobSeekerEducationDetails::create([
                'user_id' => $auth->id,
                'bash_id' => Str::uuid(),
                'educations' => json_encode($educations, JSON_PRETTY_PRINT),
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Education added successfully!',
        ], 200);
    }


    public function get_education()
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }

        $userEducation = JobSeekerEducationDetails::select('educations')->where('user_id', $auth->id)->first();
        return response()->json([
            'status' => true,
            'message' => 'Education List',
            'data' => json_decode($userEducation)
        ], 200);
    }

    public function update_education(Request $request)
    {
        try {
            $auth = JWTAuth::user();
            if (!$auth) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'education_id' => 'required',
                'educations' => 'required', // Make sure experience data is passed in the request
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }

            $education_id = $request->education_id;
            $newEducationData = $request->educations; // The updated experience data

            // Find the user document record
            $userEducation = JobSeekerEducationDetails::select('educations', 'user_id', 'id')->where('user_id', $auth->id)->first();
            if (!$userEducation) {
                return response()->json(['status' => false, 'message' => 'Education not found.'], 404);
            }

            // Decode the existing experience JSON data
            $educations = json_decode($userEducation->educations, true);

            // Ensure that experience is an array and not a string
            if (!is_array($educations)) {
                return response()->json(['status' => false, 'message' => 'Certification field is not an array.'], 422);
            }
            $find_education = collect($educations)->first(function ($education) use ($education_id) {
                return isset($education['data']['education_id']) && (int) $education['data']['education_id'] === $education_id;
            });

            if (!$find_education) {
                return response()->json([
                    'status' => false,
                    'message' => "Education ID $education_id not found.",

                ], 404);
            }

            // Find the index of the matched education record
            $index = collect($educations)->search(fn($education) => isset($education['data']['education_id']) && (int) $education['data']['education_id'] === $education_id);

            if ($index === false) {
                return response()->json(['status' => false, 'message' => 'Education not found in array.'], 404);
            }

            // Merge the new data inside the "data" array
            $educations[$index]['data'] = array_merge($educations[$index]['data'], $newEducationData);

            // Save updated data
            $userEducation->educations = json_encode($educations, JSON_PRETTY_PRINT);
            $userEducation->save();

            return response()->json([
                'status' => true,
                'message' => 'Education updated successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function delete_education(Request $request)
    {
        try {
            $auth = JWTAuth::user();
            if (!$auth) {
                return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
            }

            // Validate input
            $validator = Validator::make($request->all(), [
                'education_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }

            $education_id = (int) $request->education_id; // Ensure it's an integer

            // Fetch user's education records
            $userEducation = JobSeekerEducationDetails::select('educations', 'user_id', 'id')
                ->where('user_id', $auth->id)
                ->first();

            if (!$userEducation) {
                return response()->json(['status' => false, 'message' => 'Education record not found.'], 404);
            }

            // Decode the education JSON data
            $educations = json_decode($userEducation->educations, true);

            if (!is_array($educations)) {
                return response()->json(['status' => false, 'message' => 'Education data is corrupted.'], 422);
            }

            // Debugging: Check available education IDs

            // Find the index where `education_id` matches inside `data`
            $index = collect($educations)->search(fn($education) => isset($education['data']['education_id']) && (int) $education['data']['education_id'] === $education_id);

            if ($index === false) {
                return response()->json([
                    'status' => false,
                    'message' => "Education ID $education_id not found.",
                    'available_education_ids' => collect($educations)->pluck('data.education_id')->toArray()
                ], 404);
            }

            // Remove the education record
            unset($educations[$index]);

            // Re-index the array (to avoid missing index numbers)
            $educations = array_values($educations);

            // Save updated educations
            $userEducation->educations = json_encode($educations, JSON_PRETTY_PRINT);
            $userEducation->save();

            return response()->json([
                'status' => true,
                'message' => 'Education deleted successfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function profile_other_details(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'summary' => 'required',
            'skills' => 'required|array',
            'extra_curricular' => 'array',
            'achievement' => 'array',
            'soft_skills' => 'array'

        ], [
            'summary.required' => 'Summary is required.',
            'skills.required' => 'Skill is required.',


        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

           $other_details = JobSeekerProfessionalDetails::where('user_id', '=', $auth->id)->first();
        if ($other_details) {
            $other_details->summary = $request->summary;
            $other_details->skills = $request->skills;
            $other_details->achievement = $request->achievement;
            $other_details->extra_curricular = $request->extra_curricular;
            $other_details->soft_skills = $request->soft_skills;
            $other_details->save();
            return response()->json(['status' => true, 'message' => 'Other Details Added']);
        } else {
            $other_details = new JobSeekerProfessionalDetails();
            $other_details->user_id = $auth->id;
            $other_details->bash_id = Str::uuid();
          $other_details->summary = $request->summary;
            $other_details->skills = $request->skills;
            $other_details->achievement = $request->achievement;
            $other_details->extra_curricular = $request->extra_curricular;
            $other_details->soft_skills = $request->soft_skills;
            $other_details->save();
            return response()->json(['status' => true, 'message' => 'Other Details Added']);
        }
    }
    public function get_profile_other_details()
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $contact_data = JobSeekerProfessionalDetails::select('summary', 'skills', 'achievement', 'extra_curricular', 'soft_skills')

            ->where('user_id', $auth->id)

            ->first();
        return response()->json([
            'status' => true,
            'message' => 'Get Other Information.',
            'data' => $contact_data
        ]);
    }
    public function check_profile_complete()
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $personal_data = User::select(
            'users.*',
            'job_seeker_contact_details.country',
            'job_seeker_contact_details.state',
            'job_seeker_contact_details.city',
            'job_seeker_contact_details.zipcode',
            'job_seeker_contact_details.course',
            'job_seeker_contact_details.primary_specialization',
            'job_seeker_contact_details.dream_company',
              'job_seeker_contact_details.work_status',
            'job_seeker_contact_details.total_year_exp',
            'job_seeker_contact_details.total_month_exp',
            'job_seeker_contact_details.secondary_mobile',
            'job_seeker_contact_details.secondary_email',
            'job_seeker_contact_details.linkedin_url',
            'job_seeker_contact_details.github_url',
            'jobseeker_education_details.certifications',
            'jobseeker_education_details.publications',
            'jobseeker_education_details.trainings',
            'jobseeker_education_details.educations',
            'jobseeker_professional_details.experience',
            'jobseeker_professional_details.summary',
            'jobseeker_professional_details.soft_skills',
            'jobseeker_professional_details.skills',
            'jobseeker_professional_details.achievement',
            'jobseeker_professional_details.extra_curricular',
            'jobseeker_professional_details.projects',
            'jobseeker_professional_details.internship'
        )
        ->leftJoin('job_seeker_contact_details', 'users.id', '=', 'job_seeker_contact_details.user_id')
        ->leftJoin('jobseeker_education_details', 'users.id', '=', 'jobseeker_education_details.user_id')
        ->leftJoin('jobseeker_professional_details', 'users.id', '=', 'jobseeker_professional_details.user_id')
      
            ->where('users.id', $auth->id)

            ->first();
        $knownLanguages = json_decode($personal_data->language_known, true);

        $responseData = [
            'personalInformation' => [
                'profilePicture' => $personal_data->profile_picture, // Assuming this field exists
                'firstName' => $personal_data->first_name,
                'middleName' => $personal_data->middle_name,
                'lastName' => $personal_data->last_name,
                'dateOfBirth' => $personal_data->dob,
                'gender' => $personal_data->gender,
                'maritalStatus' => $personal_data->marital_status,
                'addressLine1' => $personal_data->location,
                'addressLine2' => '',
                'city' => $personal_data->city,
                'state' => $personal_data->state,
                'country' => $personal_data->country,
                'zipCode' => $personal_data->zipcode,
                'course' => $personal_data->course,
                'specialization' => $personal_data->primary_specialization,
                'bloodGroup' => $personal_data->blood_group,
                'medicalHistory' => $personal_data->medical_history,
                'disability' => $personal_data->disability,
                'knownLanguages' => $knownLanguages, // Assuming it's stored as comma separated values
                  'work_status' => $personal_data->work_status,
                'totalExpYear' => $personal_data->total_year_exp,
                'totalExpMonth' => $personal_data->total_month_exp,
            ],
           
            'projectDetails' => json_decode($personal_data->projects),
          
            'otherDetails' => [
                'summary' => $personal_data->summary,
                'skills' => json_decode($personal_data->skills),
                'achievement' => $personal_data->achievement,
                'extra_curricular' => $personal_data->extra_curricular,
                'soft_skills' => $personal_data->soft_skills
            ],
            'educationDetails' => json_decode($personal_data->educations),
           
        ];
        $errors = [];

        if (empty($responseData['personalInformation'])) {
            $errors[] = 'Personal Information required';
        }
        if (empty($responseData['educationDetails'])) {
            $errors[] = 'Education Details required';
        }
        if (empty($responseData['projectDetails'])) {
            $errors[] = 'Project Details required';
        }
        if (empty($responseData['otherDetails'])) {
            $errors[] = 'Other Details required';
        }
    
        // If there are errors, return response with status false
        if (!empty($errors)) {
            return response()->json([
                'status' => false,
                'message' => $errors, // Returning as an array
            ]);
        }
        return response()->json([
            'status' => true,
            'message' => 'Get Other Information.',
            'data' => $responseData
        ]);
    
    }
     public function generate_resume_by_jd(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'job_id' => 'required',
            'bash_id' => 'required'

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
         $personal_data = User::select(
            'users.*',
          
            'jobseeker_education_details.certifications',
           
            'jobseeker_professional_details.experience',
            'jobseeker_professional_details.summary',
            'jobseeker_professional_details.soft_skills',
            'jobseeker_professional_details.skills',
            'jobseeker_professional_details.achievement',
            'jobseeker_professional_details.extra_curricular',
            'jobseeker_professional_details.projects',
            'jobseeker_professional_details.internship'
        )
        ->leftJoin('job_seeker_contact_details', 'users.id', '=', 'job_seeker_contact_details.user_id')
        ->leftJoin('jobseeker_education_details', 'users.id', '=', 'jobseeker_education_details.user_id')
        ->leftJoin('jobseeker_professional_details', 'users.id', '=', 'jobseeker_professional_details.user_id')
      
            ->where('users.id', $auth->id)

            ->first();
        if($personal_data)
        {
            
        $responseData = [
           
            'certificationDetails' => json_decode($personal_data->certifications),
           
            'professionalDetails' => json_decode($personal_data->experience),
            'projectDetails' => json_decode($personal_data->projects),
           
            'otherDetails' => [
                'summary' => $personal_data->summary,
                'skills' => json_decode($personal_data->skills),
                'achievement' => json_decode($personal_data->achievement),
                'extra_curricular' => json_decode($personal_data->extra_curricular),
                'soft_skills' => json_decode($personal_data->soft_skills)
            ],
         
        ];
        
         $job = Jobs::select('jobs.job_title','jobs.location','jobs.job_description','jobs.responsibilities','jobs.skills_required','jobs.status','jobs.salary_range','jobs.industry','jobs.job_type','jobs.contact_email','jobs.experience_required','jobs.is_hot_job','jobs.expiration_date','jobs.expiration_time')
        ->where('jobs.id', $request->job_id)
       
        ->first();
       
       $jd=array("title"=>$job->job_title,
     
       "locations"=>$job->location,
       "description"=>$job->job_description,
       "responsibilities"=>$job->responsibilities,
       "skills"=>$job->skills_required,
       "status"=>$job->status,
       "salary"=>$job->salary_range,
       "industries"=>$job->industry,
       "employmentType"=>$job->job_type,
       "email"=>$job->contact_email,
       "experience"=>$job->	experience_required,
       "hotJob"=>$job->is_hot_job,
       "expirationDate"=>$job->expiration_date,
       
       "expirationTime"=>$job->expiration_time
       );
       
       
         $ch = curl_init();
            
       
        
        $jsonData = [
            'jd' => $jd,
            'resume' => $responseData
        ];

       
            curl_setopt_array($ch, [
                CURLOPT_URL => 'https://job-fso4.onrender.com/RESUME_GENERATOR',
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
                'message' =>curl_error($ch),
                
            ]);
              
            }
            
             curl_close($ch);
              return response()->json([
            'status' => true,
            'message' => 'Generate Resume By Jd.',
            'data' =>json_decode($response)
        ]);
        }else{
              return response()->json([
            'status' => false,
            'message' => 'No data found.',
            'data' => []
        ]);
        }
    }
    public function master_resume_json()
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $personal_data = User::select(
            'users.*',
            'job_seeker_contact_details.country',
            'job_seeker_contact_details.state',
            'job_seeker_contact_details.city',
            'job_seeker_contact_details.zipcode',
            'job_seeker_contact_details.course',
            'job_seeker_contact_details.primary_specialization',
            'job_seeker_contact_details.dream_company',
              'job_seeker_contact_details.work_status',
            'job_seeker_contact_details.total_year_exp',
            'job_seeker_contact_details.total_month_exp',
            'job_seeker_contact_details.secondary_mobile',
            'job_seeker_contact_details.secondary_email',
            'job_seeker_contact_details.linkedin_url',
            'job_seeker_contact_details.github_url',
            'jobseeker_education_details.certifications',
            'jobseeker_education_details.publications',
            'jobseeker_education_details.trainings',
            'jobseeker_education_details.educations',
            'jobseeker_professional_details.experience',
            'jobseeker_professional_details.summary',
            'jobseeker_professional_details.soft_skills',
            'jobseeker_professional_details.skills',
            'jobseeker_professional_details.achievement',
            'jobseeker_professional_details.extra_curricular',
            'jobseeker_professional_details.projects',
            'jobseeker_professional_details.internship'
        )
        ->leftJoin('job_seeker_contact_details', 'users.id', '=', 'job_seeker_contact_details.user_id')
        ->leftJoin('jobseeker_education_details', 'users.id', '=', 'jobseeker_education_details.user_id')
        ->leftJoin('jobseeker_professional_details', 'users.id', '=', 'jobseeker_professional_details.user_id')
      
            ->where('users.id', $auth->id)

            ->first();
        $knownLanguages = json_decode($personal_data->language_known, true);
        if($personal_data->open_to_work=="0")
        {
            $open_to_work=false;
        }else{
               $open_to_work=true;
        }
       $disk = env('FILESYSTEM_DISK'); // Default to 'local' if not set in .env

           
        if ($personal_data->profile_picture) {
            if ($disk=== 's3') {
                // For S3, use Storage facade with the 's3' disk
                $profile_picture = Storage::disk('s3')->url($personal_data->profile_picture);
            } else {
                // Default to local
                $profile_picture = env('APP_URL') . Storage::url('app/public/' .$personal_data->profile_picture);
            }
          
        } else {
            // If no logo exists, set it to null or a default image URL
            $profile_picture = null; // Replace with a default image URL if needed
        }
        $responseData = [
            'personalInformation' => [
                'profilePicture' => $profile_picture, // Assuming this field exists
                'firstName' => $personal_data->first_name,
                'middleName' => $personal_data->middle_name,
                'lastName' => $personal_data->last_name,
                'email' => $personal_data->email,
                'phoneNumber' => $personal_data->mobile,
                'dateOfBirth' => $personal_data->dob,
                'gender' => $personal_data->gender,
                'maritalStatus' => $personal_data->marital_status,
                'addressLine1' => $personal_data->location,
                'addressLine2' => '',
                'city' => $personal_data->city,
                'state' => $personal_data->state,
                'country' => $personal_data->country,
                'zipCode' => $personal_data->zipcode,
                'course' => $personal_data->course,
                'specialization' => $personal_data->primary_specialization,
                'bloodGroup' => $personal_data->blood_group,
                'medicalHistory' => $personal_data->medical_history,
                'disability' => $personal_data->disability,
                'knownLanguages' => $knownLanguages, // Assuming it's stored as comma separated values
                'work_status' => $personal_data->work_status,
                'totalExpYear' => $personal_data->total_year_exp,
                'totalExpMonth' => $personal_data->total_month_exp,
                'open_to_work'=>$open_to_work,
            ],
            'certificationDetails' => json_decode($personal_data->certifications),
            'contactDetails' => [
                'secondaryPhone' => $personal_data->secondary_mobile,
                'otherEmail' => $personal_data->secondary_email,
                'linkedInUrl' => $personal_data->linkedin_url,
                'githubUrl' => $personal_data->github_url
            ],
            'professionalDetails' => json_decode($personal_data->experience),
            'projectDetails' => json_decode($personal_data->projects),
            'researchPapers' => json_decode($personal_data->publications),
            'trainingDetails' => json_decode($personal_data->trainings),
            'otherDetails' => [
                'summary' => $personal_data->summary,
                'skills' => json_decode($personal_data->skills),
                'achievement' => json_decode($personal_data->achievement),
                'extra_curricular' => json_decode($personal_data->extra_curricular),
                'soft_skills' => json_decode($personal_data->soft_skills)
            ],
            'educationDetails' => json_decode($personal_data->educations),
            'internshipDetails' => json_decode($personal_data->internship),
        ];
        return response()->json([
            'status' => true,
            'message' => 'Get Other Information.',
            'data' => $responseData
        ]);
    }

    public function generate_resume(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'resume_name' => 'required',
            'resume' => 'required',
            'resume_json' => 'required',
            'is_ai_generated'=>'required',
            'job_id'=>'required',

        ], [
            'resume_name.required' => 'Resume Name is required.',
            'resume.required' => 'Resume is required.',
            'resume_json.required' => 'Resume JSON is required.',
            'job_id.required'=>'Job Id is required'

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $disk = env('FILESYSTEM_DISK'); // Default to 'local' if not set in .env

        $resume = new GenerateResume();
        if ($request->hasFile("resume")) {

            $extension = $request->file('resume')->getClientOriginalExtension();

            $filename = time() . '.' . $extension;

            if ($disk == 'local') {

                $imagePath = $request->file('resume')->storeAs('jobseeker_resume', $filename, 'public');
            } elseif ($disk == 's3') {

                $imagePath = $request->file('resume')->storeAs('jobseeker_resume', $filename, 's3');
            }

            $resume->resume = $imagePath;
        }

        $resume->user_id = $auth->id;
        $resume->bash_id = Str::uuid();
        $resume->resume_name = $request->resume_name;
        $resume->resume_json = $request->resume_json;
         $resume->job_id = $request->job_id;
          $resume->is_ai_generated = $request->is_ai_generated;
        $resume->save();
        return response()->json(['status' => true, 'message' => 'Resume Generated.'], 200);
    }

    public function view_generate_resume()
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
        $resume = GenerateResume::select('id','bash_id', 'resume_name', 'resume', 'resume_json','is_ai_generated')->where('user_id', $auth->id)->get();
        $resume->transform(function ($resume) {
             $disk = env('FILESYSTEM_DISK'); // Default to 'local' if not set in .env

           
            if ($resume->resume) {
                if ($disk=== 's3') {
                    // For S3, use Storage facade with the 's3' disk
                    $resume->resume = Storage::disk('s3')->url($resume->resume);
                } else {
                    // Default to local
                    $resume->resume= env('APP_URL') . Storage::url('app/public/' .$resume->resume);
                }
              
            } else {
                // If no logo exists, set it to null or a default image URL
                $resume->resume = null; // Replace with a default image URL if needed
            }
            if ($resume->resume_json) {
                $resume->resume_json = json_decode($resume->resume_json, true); // Decodes to an associative array
            }

            return $resume;
        });
        return response()->json([
            'status' => true,
            'message' => 'View Resume.',
            'data' => $resume
        ]);
    }
    public function get_resume_by_id(Request $request)
    {
         $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required',
           'bash_id'=>'required',

        ], [
            'id.required' => 'Id is required.',
            'bash_id.required'=>'Bash_id is required'
          
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
         $resume = GenerateResume::select('id','bash_id', 'resume_name', 'resume_json')->where('user_id', $auth->id)->where('id',$request->id)->where('bash_id',$request->bash_id)->first();
          if ($resume->resume_json) {
                $resume->resume_json = json_decode($resume->resume_json, true); // Decodes to an associative array
            }
            return response()->json([
            'status' => true,
            'message' => 'View Resume.',
            'data' => $resume
        ]);
    }
    public function delete_generate_resume(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'id' => 'required',
           

        ], [
            'id.required' => 'Id is required.',
          
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $delete = GenerateResume::where('id', $request->id)
     
        ->where('user_id', $auth->id)
        ->first();
        $disk = env('FILESYSTEM_DISK'); // Default to 'local' if not set in .env

    // Check if the document exists
    if (!$delete) {
        return response()->json([
            'status' => false,
            'message' => 'not found'
        ], 404);
    }
   
    // Get the document image path (assuming the column stores the relative path)
    $imagePath = 'public/' . $delete->resume;

    // Check if the image exists in storage and delete it
    if (Storage::exists($imagePath)) {
        if($delete)
        {
            
      
             $ai_delete = AIAnalysisResume::where('resume_generate_id', $request->id)
         
            ->where('jobseeker_id', $auth->id)
            ->delete();
    //         if($ai_delete)
    //   {
    //           $ai_delete->delete();
    //   }
        }
        // if ($disk == 'local') {
        //     // Delete from local disk
        //     Storage::disk('public')->delete($imagePath);
        // } elseif ($disk == 's3') {
        //     // Delete from S3 disk
        //     Storage::disk('s3')->delete( $imagePath);
        // }
    }

    // Delete the document record from the database
    $delete->delete();
        return response()->json([
           'status'=>true,
           'message'=>'resume deleted.'
       ]);
    }

    public function open_to_work(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'open_to_work' => 'required', 
        ], [
            'open_to_work.required' => 'Open to Work status is required.',  
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

        $user = User::where('id', $auth->id)->first();
        if($user)
        {
            if( $request->open_to_work==true)
            {
                $open_to_work=1;
                
            }else{
                 $open_to_work=0;
            }
            $user->open_to_work = $open_to_work;
            $user->save();
            return response()->json([
                "status" => true,
                "message" => "Open to Work status Changed.",
                
            ], 200);
        }else{
            return response()->json([
                "status" => false,
                "message" => "User not found.",
                
            ]);
        }

    }

    public function get_open_to_work()
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
        $user = User::select('open_to_work')->where('id', $auth->id)->first();
        if($user->open_to_work==1)
            {
                $open_to_work=true;
                
            }else{
                $open_to_work=false;
            }
        return response()->json([
            "status" => true,
            "message" => "Open to Work Status.",
            'data'=>$open_to_work
            
        ]);
    }

    public function submit_candidate_review(Request $request)
    {
        $auth = JWTAuth::user();

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $validator = Validator::make($request->all(), [
            'review' => 'required',
            'rating'=>'required'
        ], [
            'review.required' => 'Review is required.', 
            'rating.required'=>'Rating is required'
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

        $review=new CandidateReview();
        $review->bash_id=Str::uuid();
        $review->jobseeker_id=$auth->id;
        $review->review=$request->review;
        $review->rating=$request->rating;
        $review->status='Pending';
        $review->save();
         return response()->json([
            "status" => true,
            "message" => "Review Added.",
           
        ]);
    }
}
