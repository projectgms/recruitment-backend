<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateReview extends Model
{
    use HasFactory;
       protected $table = 'candidate_reviews';
    protected $fillable = [
        'id', 
        'bash_id', 
        'jobseeker_id', 
        'review',
        'rating',
        'status',
        
    ];
}
