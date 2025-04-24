<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruiterPlan extends Model
{
    use HasFactory;
    protected $table = 'recruiter_plans';
    protected $fillable = [
        'id', 
        'bash_id', 
        'plan_name', 
        'plan_type',
        'amount',
        'features',
        'status',
        'active'
    ];
}
