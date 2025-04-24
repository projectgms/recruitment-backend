<?php

namespace App\Http\Controllers\Recruiter;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use App\Models\RecruiterPlan;
class RecruiterSubscriptionController extends Controller
{
    //
    public function get_plans()
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
        
         $plans = RecruiterPlan::get();

        // // Group plans by plan_name
        $groupedPlans = $plans->groupBy('plan_type');

        // Separate monthly and yearly plans
        $monthlyPlans = [];
        $yearlyPlans = [];

        // // Loop through the grouped plans and separate monthly and yearly
        foreach ($groupedPlans as $planName => $planGroup) {
            foreach ($planGroup as $plan) {
                // Decode the features from JSON
                $plan->features = json_decode($plan->features);

                // Separate into monthly and yearly
                if ($plan->plan_type == 'Month') {
                    $monthlyPlans[] = $plan;
                } else if ($plan->plan_type == 'Year') {
                    $yearlyPlans[] = $plan;
                }
            }
        }

        // Return response with monthly and yearly plans
        return response()->json([
            'status' => 'true',
            'Month' => $monthlyPlans,
            'Year' => $yearlyPlans
        ]);
    }
}
