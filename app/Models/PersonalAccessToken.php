<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasFactory;
    protected $fillable = [
        'name',
        'token',
        'abilities',
        'created_at',
        'updated_at',
    ];
    // public static function onConnection($connection = null)
    // {
    //     return (new static)->setConnection($connection);
    // }
}
