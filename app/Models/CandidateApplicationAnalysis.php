<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateApplicationAnalysis extends Model
{
    use HasFactory;
    protected $table = 'candidate_job_application_analysis';
    protected $fillable = [
        'id', 
        'bash_id', 
        'jobseeker_id', 
        'job_application_id',
        'ai_analysis',
        
    ];
}
