<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSubscription extends Model
{
    use HasFactory;
    protected $table = 'email_subscriptions';
    protected $fillable = [
        'id', 
      'email',
       
    ];
}

