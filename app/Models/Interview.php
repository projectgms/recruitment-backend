<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Interview extends Model
{
    use HasFactory;
    protected $table = 'interviews';
    protected $fillable = [
      
        'id', 
        'bash_id', 
        'job_id',
        'job_application_id',
        'jobseeker_id',
        'recruiter_id',
        'company_id',
        'round_id',
        'score',
        'total',
        'interview_date',
        'interview_mode',
        'interview_link',
        'feedback'
    ];
}
