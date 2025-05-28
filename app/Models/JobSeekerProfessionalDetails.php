<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobSeekerProfessionalDetails extends Model
{
    use HasFactory;
    protected $table = 'jobseeker_professional_details';
    protected $fillable = [
        'id', 
        'bash_id', 
        'user_id',
        'experience',
        'internship',
        'projects',
        'summery',
        'skills',
        'auto_apply_job',
        'auto_apply_resume_id',
        'soft_skills',
        'achievement',
        'awards',
        'hobbies'
     
    ];
}
