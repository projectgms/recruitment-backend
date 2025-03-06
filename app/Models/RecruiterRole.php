<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruiterRole extends Model
{
    use HasFactory;
    protected $table = 'recruiter_roles';
    protected $fillable = [
        'id', 
        'bash_id', 
        'role', 
        'parent_id',
        'active'
    ];
}
