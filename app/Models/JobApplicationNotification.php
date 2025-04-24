<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobApplicationNotification extends Model
{
    use HasFactory;
    protected $table = 'job_application_notifications';
    protected $fillable = [
        'id', 
        'bash_id', 
        'job_id',
        'job_application_id',
        'jobseeker_id',
        'company_id',
        'type',
        'message',
        'is_read'
       
    ];
}
