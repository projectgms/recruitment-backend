<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasFactory;
    protected $table = 'role_permissions';
    protected $fillable = [
        'id', 
        'bash_id', 
        'role_id', 
        'company_id',
        'menu',
        'view',
        'add',
        'edit',
        'delete'
    ];
}
