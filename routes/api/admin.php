
<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\Admin\AdminAuthController;


Route::post('admin/login', [AdminAuthController::class, 'login']);
    Route::middleware(['auth:api'])->group(function () {
       // Route::get("superadmin/profile", [SuperadminAuthController::class, "profile"]);
      
    });
