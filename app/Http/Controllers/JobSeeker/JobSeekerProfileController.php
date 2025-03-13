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
          
            'totalExpYear' => 'required',
            'totalExpMonth' => 'required',
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

        $personal_data = User::select('users.*','job_seeker_contact_details.total_year_exp','job_seeker_contact_details.total_month_exp', 'job_seeker_contact_details.country', 'job_seeker_contact_details.state', 'job_seeker_contact_details.city', 'job_seeker_contact_details.zipcode', 'job_seeker_contact_details.course', 'job_seeker_contact_details.primary_specialization', 'job_seeker_contact_details.dream_company')
            ->join('job_seeker_contact_details', 'users.id', '=', 'job_seeker_contact_details.user_id')
            ->where('users.id', $auth->id)

            ->first();
            if ($personal_data) {
                // Modify the company logo to include the full URL if it exists
                if ($personal_data->profile_picture) {
                    $personal_data->profile_picture = env('APP_URL') . Storage::url('app/public/' . $personal_data->profile_picture);
                } else {
                    // If no logo exists, set it to null or a default image URL
                    $personal_data->profile_picture = null; // Replace with a default image URL if needed
                }
            
              
            }
          
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
            return response()->json(['status' => true, 'message' => 'Contact Details Updated']);
        } else {
            $contact = new JobSeekerContactDetails();
            $contact->user_id= $auth->id;
            $contact->bash_id= Str::uuid();
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
            'data' => $userDocument
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
                "currentlyWorking" => $experience['currentlyWorking'] == '1' ? 'true' : 'false',
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
                'experience' => $experiences, // Store the array directly
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

                "currentlyWorking" => $internship['currentlyWorking'] == '1' ? 'true' : 'false',
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
                'internship' => $internships, // Store the array directly
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
                'projects' => $projects, // Store the array directly
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
                "type" => $publication['type'] ? $publication['type'] : null,
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
                'publications' => $publications, // Store the array directly
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

    public function delete_publication(Request $request)
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
                'trainings' => $trainings, // Store the array directly
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
                "name" => $certification['name'] ? $certification['name'] : null,
                "provider" => $certification['provider'] ? $certification['provider'] : null,
                "enrollmentNumber"=>$certification['enrollmentNumber'] ? $certification['enrollmentNumber'] : null,
                "validUpto" => $certification['validUpto'] ? $certification['validUpto'] : null,
                "marksType" => $certification['marksType'] ? $certification['marksType'] : null,
                "aggregate" => $certification['aggregate'] ? $certification['aggregate'] : null,
                "max" => $certification['max'] ? $certification['max'] : null,
                "max" => $certification['max'] ? $certification['max'] : null,
                "skills" => $certification['skills'] ? $certification['skills'] : null
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
                'certifications' => $certifications, // Store the array directly
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

            $certifications = json_decode($userCertification->certification, true);

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
                'education_id' => 'required|integer',
                'educations' => 'required|array', // Make sure experience data is passed in the request
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

            // Find the specific experience record by exp_id
            $find_education_id = collect($educations)->firstWhere('education_id', $education_id);

            if (!$find_education_id) {
                return response()->json(['status' => false, 'message' => 'Education not found.'], 404);
            }

            // Find the index of the experience
            $index = collect($educations)->search(function ($education) use ($education_id) {
                return $education['education_id'] === $education_id;
            });

            // Merge the new data with the existing experience data
            $educations[$index] = array_merge($educations[$index], $newEducationData);


            // Save the updated experiences back to the database
            $userEducation->educations = json_encode($educations, JSON_PRETTY_PRINT);
            $userEducation->save();

            return response()->json([
                'status' => true,
                'message' => 'Educations updated successfully!',
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
                'education_id' => 'required',

            ]);

            if ($validator->fails()) {
                return response()->json(['status' => false, 'message' => $validator->errors()], 422);
            }
            $education_id = $request->education_id;

            // Find the user document record
            $userEducation = JobSeekerEducationDetails::select('educations', 'user_id', 'id')->where('user_id', $auth->id)->first();

            if (!$userEducation) {
                return response()->json(['status' => false, 'message' => 'Education not found.'], 404);
            }

            $educations = json_decode($userEducation->educations, true);

            // Ensure that documents is an array and not a string
            if (!is_array($educations)) {
                return response()->json(['status' => false, 'message' => 'education field is not an array.'], 422);
            }

            $educationToDelete = collect($educations)->firstWhere('education_id', $education_id);


            if (!$educationToDelete) {
                return response()->json(['status' => false, 'message' => 'Education ID not found.'], 404);
            }


            // Remove the document from the documents array
            $updatedEducation = collect($educations)->reject(function ($item) use ($education_id) {
                return $item['education_id'] == $education_id;
            })->values()->all();


            // Re-encode the documents array to JSON and save it back to the database
            $userEducation->educations = json_encode($updatedEducation);
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
            'expertise' => 'required|array',
            'extraCurricular'=>'null|array',
            'achievements'=>'null|array'
          

        ], [
            'summary.required' => 'Summary is required.',
            'expertise.required' => 'Skill is required.',
           

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
            $other_details->skills = $request->expertise;
            $other_details->achievement = $request->achievements;
            $other_details->extra_curricular = $request->extraCurricular;
            $other_details->save();
            return response()->json(['status' => true, 'message' => 'Other Details Updated']);
        } else {
            $other_details = new JobSeekerProfessionalDetails();
            $other_details->user_id= $auth->id;
            $other_details->bash_id= Str::uuid();
            $other_details->summary = $request->summary;
            $other_details->skills = $request->expertise;
            $other_details->achievement = $request->achievements;
            $other_details->extra_curricular = $request->extraCurricular;
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

        $contact_data = JobSeekerProfessionalDetails::select('summary', 'skills', 'achievement', 'extra_curricular')

            ->where('user_id', $auth->id)

            ->first();
        return response()->json([
            'status' => true,
            'message' => 'Get Other Information.',
            'data' => $contact_data
        ]);
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

        $personal_data = User::select('users.*', 'job_seeker_contact_details.country', 'job_seeker_contact_details.state', 'job_seeker_contact_details.city', 'job_seeker_contact_details.zipcode', 'job_seeker_contact_details.course', 'job_seeker_contact_details.primary_specialization', 'job_seeker_contact_details.dream_company',
        'job_seeker_contact_details.total_year_exp','job_seeker_contact_details.total_month_exp','job_seeker_contact_details.secondary_mobile','job_seeker_contact_details.secondary_email','job_seeker_contact_details.linkedin_url','job_seeker_contact_details.github_url',
        'jobseeker_education_details.certifications','jobseeker_education_details.publications','jobseeker_education_details.trainings','jobseeker_education_details.educations',
        'jobseeker_professional_details.experience','jobseeker_professional_details.summary','jobseeker_professional_details.skills','jobseeker_professional_details.achievement','jobseeker_professional_details.extra_curricular','jobseeker_professional_details.projects','jobseeker_professional_details.internship')
        ->join('job_seeker_contact_details', 'users.id', '=', 'job_seeker_contact_details.user_id')
        ->join('jobseeker_education_details','users.id','=','jobseeker_education_details.user_id')
        ->join('jobseeker_professional_details','users.id','=','jobseeker_professional_details.user_id')
        ->where('users.id', $auth->id)

        ->first();
        $knownLanguages = json_decode($personal_data->language_known, true);

        $responseData = [
            'personalInformation' => [
                'profilePicture' => $personal_data->profile_picture, // Assuming this field exists
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
                 'totalExpYear' => $personal_data->total_year_exp,
                 'totalExpMonth' => $personal_data->total_month_exp,
            ],
             'certificationDetails' => json_decode($personal_data->certifications),
             'contactDetails' =>[
                'secondaryPhone'=>$personal_data->secondary_mobile,
                'otherEmail'=>$personal_data->secondary_email,
                'linkedInUrl'=>$personal_data->linkedin_url,
                'githubUrl'=>$personal_data->github_url
             ],
             'professionalDetails' => json_decode($personal_data->experience),
             'projectDetails' => json_decode($personal_data->projects),
            'researchPapers' => json_decode($personal_data->publications),
             'trainingDetails' => json_decode($personal_data->trainings),
             'otherDetails' => ['summary'=>$personal_data->summary,
             'expertise'=>json_decode($personal_data->skills),
             'achievements'=>$personal_data->achievement,
             'extraCurricular'=>$personal_data->extra_curricular
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
        echo 'hii';
    }

}
