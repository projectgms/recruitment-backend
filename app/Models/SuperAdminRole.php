<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SuperAdminRole extends Model
{
    use HasFactory;
    protected $table = 'superadmin_roles';
    protected $fillable = [
        'id', 
        'bash_id', 
        'role', 
        'parent_id',
        'active'
    ];
}
