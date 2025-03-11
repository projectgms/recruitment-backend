<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterviewRound extends Model
{
    use HasFactory;
    protected $table = 'interview_rounds';
    protected $fillable = [
        'id', 
        'bash_id',
        'round_name',
        'active'
    ];
}
