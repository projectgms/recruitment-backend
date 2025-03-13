<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Jobs extends Model
{
    use HasFactory;
   
    protected $table = 'jobs';
    protected $fillable = [
        'id', 
        'bash_id', 
        'user_id', 
        'comapny_id',
        'job_title',
        'job_description',
        'job_type',
        'location',
        'industry',
        'is_hot_job',
        'contact_email',
        'salary_range',
        'skills_required',
        'experience_required',
        'round',
        'expiration_date',
        'expiration_time',
        'responsibilities',
        'status',
        'active'
    ];
}
