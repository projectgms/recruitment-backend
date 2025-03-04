<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

Route::get('/login', function () {
    return response()->json(['error' => 'Unauthorized.'], 401);
})->name('login');




// Include route files
Route::prefix('v1')->group(function () {
    // Include routes for admin
    require base_path('routes/api/admin.php');

    // Include routes for recruiter
    require base_path('routes/api/recruiter.php');

    // Include routes for jobseeker
    require base_path('routes/api/jobseeker.php');
});

Route::prefix('v1')->group(function () {

Route::get('welcome',[AuthController::class,'welcome']);
  
});
