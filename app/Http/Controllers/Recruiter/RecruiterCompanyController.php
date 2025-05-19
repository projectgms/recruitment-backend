<?php

namespace App\Http\Controllers\Recruiter;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Company;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

use App\Http\Controllers\Controller;
use App\Models\CompanyEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RecruiterCompanyController extends Controller
{
    //
    public function company_profile(Request $request)
    {
        $permissions = json_encode($request->attributes->get('permissions'));  // Correct way to access permissions

        $auth = JWTAuth::user();
        $disk = env('FILESYSTEM_DISK'); // Default to 'local' if not set in .env

        if (!$auth) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized',
            ], 401);
        }
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
        ], [
            'user_id.required' => 'User Id is required.',

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $company = Company::select('id', 'bash_id', 'name', 'website', 'industry', 'company_size', 'company_description', 'locations', 'company_logo', 'social_profiles', 'facebook_url', 'linkedin_url', 'twitter_url', 'instagram_url')->where('id', $auth->company_id)->first();
        if ($company) {
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
                $company->company_logo = null; // Or use a default image URL
            }
        }
        return response()->json([
            'status' => true,
            'message' => 'Get Company Information.',
            'data' => $company
        ]);
    }


    public function update_company_profile(Request $request)
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

            'user_id' => 'required',
            'name' => 'required',
            'website' => 'required',
            'industry' => 'array|required',
            'locations' => 'array|required',
            'company_size' => 'required',
            'company_description' => 'required',
            'company_logo' => 'required',
            'social_profiles' => '',
            'facebook_url' => '',
            'instagram_url' => '',
            'linkedin_url' => '',
            'twitter_url' => '',

        ], [
            'user_id.required' => 'User Id is required.',

            'name.required' => 'Company Name is required.',
            'website.required' => 'Website is required.',
            'industry.required' => 'Industry is required.',
            'locations.required' => 'Location is required.',
            'company_size.required' => 'Company Size is required.',
            'company_description.required' => 'Company Description is required.',
            'company_logo.required' => 'Company Logo is required.'

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $disk = env('FILESYSTEM_DISK'); // Default to 'local' if not set in .env

        $company = Company::where('id', $auth->company_id)->where('active', '1')->first();
        if ($company) {
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
            $company->locations = $request->locations;
            $company->social_profiles = $request->social_profiles;
            $company->facebook_url = $request->facebook_url;
            $company->instagram_url = $request->instagram_url;
            $company->linkedin_url = $request->linkedin_url;
            $company->twitter_url = $request->twitter_url;
            $company->save();
            return response()->json(['status' => true, 'message' => 'Company Information Updated.'], 200);
        } else {
            return response()->json(['status' => false, 'message' => 'Permission denied.']);
        }
    }

    public function add_company_event(Request $request)
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

            'title' => 'required',
            'description' => 'required',
            'event_images' => 'array|required',


        ], [
            'title.required' => 'Event Title is required.',

            'event_images.required' => 'Event Images is required.',
            'description.required' => 'Description is required.',

        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
        $disk = env('FILESYSTEM_DISK'); // Default to 'local' if not set in .env

        $company = CompanyEvent::where('company_id', $auth->company_id)->where('title', $request->title)->where('active', '1')->first();
        if ($company) {
            return response()->json(['status' => false, 'message' => 'Event Title already added.']);
        } else {
            $company = new CompanyEvent();
            if ($request->hasFile("event_images")) {

                $imagePaths = []; // To store paths of all uploaded files

                foreach ($request->file('event_images') as $file) {

                    $extension = $file->getClientOriginalExtension();
                    $filename = time() . '_' . uniqid() . '.' . $extension;

                    if ($disk == 'local') {
                        $path = $file->storeAs('company_events', $filename, 'public');
                    } elseif ($disk == 's3') {
                        $path = $file->storeAs('company_events', $filename, 's3');
                    }

                    $imagePaths[] = $path;
                }

                // Store as JSON or however you prefer
                $company->event_images = json_encode($imagePaths);
            }
            $company->bash_id = Str::uuid();
            $company->title = $request->title;
            $company->company_id = $auth->company_id;
            $company->description = $request->description;

            $company->save();
            return response()->json(['status' => false, 'message' => 'Event Added.']);
        }
    }

    public function view_company_event()
    {
        $auth = JWTAuth::user();
        if (!$auth) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
        }
        $company = CompanyEvent::select('id', 'bash_id', 'title', 'event_images', 'description')->where('company_id', $auth->company_id)->where('active', '1')->orderBy('id','desc')->get();
        if ($company) {
            $company->transform(function ($company) {
                $disk = env('FILESYSTEM_DISK'); // 'local' or 's3'

                if ($company->event_images) {
                    $images = json_decode($company->event_images, true); // Decode JSON array
                    $imageUrls = [];

                    foreach ($images as $image) {
                        if ($disk === 's3') {
                            $imageUrls[] = Storage::disk('s3')->url($image);
                        } else {
                            $imageUrls[] = env('APP_URL') . Storage::url('app/public/' . $image);
                        }
                    }

                    $company->event_images = $imageUrls; // Assign the array of full URLs
                } else {
                    $company->event_images = [];
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

    public function delete_company_event(Request $request)
    {

    $auth = JWTAuth::user();
    if (!$auth) {
        return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
    }
       $validator = Validator::make($request->all(), [

            'id' => 'required',
            'bash_id' => 'required',
           
        ], [
            'id.required' => 'Id is required.',

            'bash_id.required' => 'Bash Id is required.',
           
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }

    $event = CompanyEvent::where('id', $request->id)
        ->where('company_id', $auth->company_id)
        ->where('bash_id',$request->bash_id)
        ->first();

    if (!$event) {
        return response()->json(['status' => false, 'message' => 'Event not found.'], 404);
    }

    $images = json_decode($event->event_images, true);

    if (!empty($images)) {
        $disk = env('FILESYSTEM_DISK', 'public');

        foreach ($images as $image) {
            if ($disk === 's3') {
                Storage::disk('s3')->delete($image);
            } else {
                Storage::disk('public')->delete($image);
            }
        }
    }

   $event->delete();

    return response()->json([
        'status' => true,
        'message' => 'All event images deleted successfully.',
       
    ]);
    }
}
