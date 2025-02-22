<?php

namespace App\Http\Controllers\JobSeeker;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Password;


use Illuminate\Support\Facades\Validator;
use App\Notifications\JobSeeker\ResetPasswordNotification;

class JobSeekerController extends Controller
{
    //

    public function forgot_password(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required',    
        ], [
            'email.required' => 'Email is required.',    
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
                
            ], 422);
        }

        $user_s = User::where('email', $request->email)->where('role', 'job_seeker')->where('active', '1')->first();
        if ($user_s) 
        {
            $token = Password::getRepository()->create($user_s);
          
            // Send the custom notification
            $user_s->notify(new ResetPasswordNotification($token));
           
            $data=array(
                'reset_pass_token'=>$token,
                'email'=>$request->email
                );
            return response()->json(['status' => true, 'message' => 'Password reset link sent.','data'=>$data], 200);
        }else{
            return response()->json(['status' => false, 'message' => 'Details Wrong.']);

        }
     
    }

    public function reset_password(Request $request)
    {
       
        $validator = Validator::make($request->all(), [
            // 'token'=>'required',
            'email' => 'required|email',
            'password' => 'required',
           
       ], [
           'email.required' => 'Email is required.',
           'password.required' => 'Password is required',
          
       ]);
       if ($validator->fails()) {
           return response()->json([
               'status' => false,
               'message' =>  $validator->errors(),
            
           ], 422);
       }
         // Find the user by email
         $user = User::where('email', $request->email)->where('role','job_seeker')->first();

         // Check if the user exists
         if ($user) {
            // throw ValidationException::withMessages(['email' => 'This email address or Unique Id does not exist.']);
            $user->password = bcrypt($request->password); // Your custom way of hashing or processing
            $user->save();
            return response()->json(['status' => 'Password has been reset successfully.'], 200);

         }else{
            return response()->json(['status' => false, 'message' => 'This email address does not exist.']);
         }
      
 
    }
}
