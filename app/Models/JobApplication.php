<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplication extends Model
{
    use HasFactory;
    protected $table = 'job_applications';
    protected $fillable = [
        'id', 
        'bash_id', 
        'job_id',
        'job_seeker_id',
        'resume_id',
        'resume',
        'resume_json',
        'cover_letter',
        'status',
        'active'
    ];
}
