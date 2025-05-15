<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruiterPrepareJob extends Model
{
    use HasFactory;
     protected $table = 'recruiter_prepare_jobs';
    protected $fillable = [
        'id', 
        'bash_id', 
        'company_id',
        'job_application_id',
        'job_id',
        'qa_output',
      
        'active',
     
    ];
}
