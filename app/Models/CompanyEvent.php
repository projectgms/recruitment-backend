<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyEvent extends Model
{
    use HasFactory;
      protected $table = 'company_events';
    protected $fillable = [
        'id', 
        'bash_id', 
        'company_id', 
        'title',
        'description',
        'event_images',
        'status',
        'active'
    ];
    
}
