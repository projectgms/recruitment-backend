<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;
    protected $table = 'companies';
    protected $fillable = [
        'id', 
        'bash_id', 
        'user_id', 
        'name',
        'website',
        'industry',
        'company_size',
        'company_description',
        'locations',
        'company_logo',
        'social_profiles',
         'facebook_url',
        'instagram_url',
        'linkedin_url',
        'twitter_url',
        'status',
        'active'
    ];

}
