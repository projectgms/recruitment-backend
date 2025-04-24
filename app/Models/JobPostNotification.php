<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPostNotification extends Model
{
    use HasFactory;
    protected $table = 'job_post_notifications';
    protected $fillable = [
        'id', 
        'bash_id', 
        'job_id',
        'company_id',
        'type',
        'message'
       
    ];
}
