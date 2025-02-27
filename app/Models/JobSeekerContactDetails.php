<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobSeekerContactDetails extends Model
{
    use HasFactory;
    protected $table = 'job_seeker_contact_details';
    protected $fillable = [
        'id', 
        'bash_id', 
        'user_id',
        'country',
        'state',
        'city',
        'zipcode',
        'course',
        'primary_specialization',
        'dream_company',
        'total_year_exp',
        'total_month_exp',
        'secondary_mobile',
        'secondary_email',
        'linkedin_url',
        'github_url'
    ];
}
