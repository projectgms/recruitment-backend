<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\MenuList;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct()
    {
     
    }
  
    public function welcome()
    {
        $uuid = Str::uuid();
        return response()->json(["status"=>true,
                                "message"=>"Welcome to Recruitment APP"]);
    }
    
    
}