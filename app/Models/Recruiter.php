<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Recruiter extends Model
{
    use HasFactory;
   
    protected $table = 'recruiters';
    protected $fillable = [
        'id', 
        'bash_id', 
        'user_id', 
        'comapny_id',
        'role',
        'status',
        'active'
    ];
}
