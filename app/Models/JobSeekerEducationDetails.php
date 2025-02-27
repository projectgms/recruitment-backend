<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobSeekerEducationDetails extends Model
{
    use HasFactory;
    protected $table = 'jobseeker_education_details';
    protected $fillable = [
        'id', 
        'bash_id', 
        'user_id',
        'educations',
        'documents',
        'publications',
        'trainings',
        'certifications',
     
    ];

}
