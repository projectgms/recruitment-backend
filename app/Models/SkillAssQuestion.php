<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SkillAssQuestion extends Model
{
    use HasFactory;
   
            protected $table = 'skill_ass_questions';
            protected $fillable = [
                'id', 
              
                'skill', 
                'skill_level',
                'question',
                'option1',
                'option2',
                'option3',
                'option4',
                'correct_answer',
                'marks',
                'company_id',
                 'job_id'
            ];
}
