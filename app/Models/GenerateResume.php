<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GenerateResume extends Model
{
    use HasFactory;
    protected $table = 'generate_resumes';
    protected $fillable = [
        'id', 
        'bash_id', 
        'user_id',
        'resume_name',
        'resume',
        'resume_json',
        'status',
        'active'
    ];
}
