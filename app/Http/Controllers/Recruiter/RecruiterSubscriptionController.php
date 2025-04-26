<?php

namespace App\Http\Controllers\Recruiter;
use Carbon\Carbon;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Validator;
use App\Models\RecruiterPlan;
use App\Models\RecruiterSubscription;
use Illuminate\Support\Str;


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
    
    public function create_subscription_order(Request $request)
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
            'plan_id'=>'required',
            'company_id'=>'required',
          
        ], [
           
            'plan_id.required' => 'Plan Id is required.',
             'company_id.required'=>'company Id is required',
        
           
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        }
         $plan = RecruiterPlan::find($request->plan_id);  // Assuming plan_id is passed

        if (!$plan) {
            return response()->json(['status' => 'error', 'message' => 'Plan not found'], 404);
        }

        // Set Razorpay API keys
        $api = new Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_KEY_SECRET'));

        // Create Razorpay order
        $orderData = [
            'receipt'         => 'rcptid_' . time(),
            'amount'          => $plan->amount*100, // Razorpay accepts amount in paise, so multiply by 100
            'currency'        => 'INR',
            'payment_capture' => 1 // Automatic payment capture
        ];

        try {
            $razorpayOrder = $api->order->create($orderData);  // Create the order with Razorpay
            
                  $order = RecruiterSubscription::create([
            'bash_id'=>Str::uuid(),
            'company_id'=>$request->company_id,
            'plan_id' => $plan->id,
            'amount'  => $plan->amount,
            'plan_name'=>$plan->plan_name,
            'plan_type'=>$plan->plan_type,
            'features'=>$plan->features,
            'status'=>'Pending',
            'plan_purchase_date'=>date('Y-m-d'),
            'plan_expiry_date'=>date('Y-m-d'),
            'rayzorpay_order_id' => $razorpayOrder->id,
          
        ]);

        // Send the Razorpay order ID to the frontend for further payment processing
        return response()->json([
            'status' => true,
            'razorpay_order_id' => $razorpayOrder->id,
            'amount' => $plan->amount,
            'currency' => 'INR',
        ]);
         
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => 'Error creating Razorpay order', 'error' => $e->getMessage()], 500);
        }
        
  
    }
    
    public function verify_payment(Request $request)
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
            'payment_id'=>'required',
            'order_id'=>'required',
            'signature'=>'required'
          
        ], [
           
            'payment_id.required' => 'Payment Id is required.',
            'order_id.required'=>'Order Id is required.',
            'signature.required'=>'Signature is required.'
           
           
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),

            ], 422);
        } 
        
         $paymentId = $request->input('payment_id');
        $orderId = $request->input('order_id');
        $signature = $request->input('signature');

        // Set Razorpay API keys
        $api = new Api(env('RAZORPAY_KEY_ID'), env('RAZORPAY_KEY_SECRET'));

        try {
            $attributes = [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature
            ];

            // Verify the payment signature
            $api->utility->verifyPaymentSignature($attributes);

            // Payment is successful, update the order status
            $order = RecruiterSubscription::where('rayzorpay_order_id', $orderId)->first();
          if ($order) {
               
                // Determine the expiry date based on the plan type
                if ($order->plan_type == 'Month') {
                    // If plan type is "Month", add 1 month to the current date
                    $order->plan_expiry_date = Carbon::now()->addMonth();
                } elseif ($order->plan_type == 'Year') {
                    // If plan type is "Year", add 1 year to the current date
                    $order->plan_expiry_date = Carbon::now()->addYear();
                }

                // Update the order status and payment ID
                $order->status = 'Paid';
                $order->rayzorpay_payment_id = $paymentId;
                  $order->plan_purchase_date =date('Y-m-d');
                 $order->plan_expiry_date = $order->plan_expiry_date;
                $order->save();

                return response()->json(['status' => 'true', 'message' => 'Payment verified successfully']);
            } else {
                return response()->json(['status' => 'false', 'message' => 'Order not found'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['status' => 'false', 'message' => 'Payment verification failed', 'error' => $e->getMessage()], 500);
        }
    }
}
