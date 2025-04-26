<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruiterSubscription extends Model
{
    use HasFactory;
    protected $table = 'recruiter_subscriptions';
    protected $fillable = [
        'id', 
        'bash_id', 
        'company_id', 
        'plan_id',
        'amount',
        'plan_type',
        'plan_name',
        'features',
        'plan_purchase_date',
        'plan_expiry_date',
        'rayzorpay_order_id',
        'rayzorpay_payment_id',
        'status',
        'active'
    ];
}
