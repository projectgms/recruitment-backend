<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIAnalysisResume extends Model
{
    use HasFactory;
    protected $table = 'ai_analysis_resumes';
    protected $fillable = [
        'id', 
        'bash_id', 
        'jobseeker_id', 
        'resume_generate_id',
        'ai_analysis',
        
    ];
}
