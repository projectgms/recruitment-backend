<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobseekerPrepareJob extends Model
{
    use HasFactory;
     protected $table = 'jobseeker_prepare_jobs';
    protected $fillable = [
        'id', 
        'bash_id', 
        'jobseeker_id',
        'job_application_id',
        'generate_resume_id',
        'qa_output',
        'title',
        'active',
     
    ];
}
