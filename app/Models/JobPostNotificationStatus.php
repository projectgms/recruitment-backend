<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobPostNotificationStatus extends Model
{
    use HasFactory;
    protected $table = 'job_post_notification_status';
    protected $fillable = [
        'id', 
        'bash_id', 
        'jobseeker_id',
        'job_post_notification_id',
        'is_read',
       
    ];
}
