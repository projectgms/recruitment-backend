<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateSkillTest extends Model
{
    use HasFactory;
    protected $table = 'candidate_skill_tests';
    protected $fillable = [
        'id', 
        'bash_id', 
        'jobseeker_id', 
        'skill',
        'score',
        'total',
        
    ];
}
