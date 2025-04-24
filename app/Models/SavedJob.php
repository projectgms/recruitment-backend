<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SavedJob extends Model
{
    use HasFactory;
    protected $table = 'candidate_saved_jobs';
    protected $fillable = [
        'id', 
        'bash_id', 
        'jobseeker_id', 
        'job_id',
        'status',
        'active'
    ];
}
