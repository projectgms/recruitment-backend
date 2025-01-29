<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;


Route::get('/login', function () {
    return response()->json(['error' => 'Unauthorized.'], 401);
})->name('login');



Route::prefix('v1')->group(function () {

    // //Open Routes 
   
Route::get('welcome',[AuthController::class,'welcome']);
    // Admin API routes

  //  Route::post('superadmin/login', [SuperadminAuthController::class, 'login']);
    Route::middleware(['auth:sanctum'])->group(function () {
       // Route::get("superadmin/profile", [SuperadminAuthController::class, "profile"]);
      
    });



    // Recruiter/Comapny API routes
  //  Route::post('oem/login', [OEMAuthController::class, 'login']);
   
    Route::middleware(['auth:sanctum'])->group(function () {
        // Route::get('oem/profile', [OEMAuthController::class, 'profile']);
        
    });



    // Job Seeker API routes
    //Route::post('aggregater/login', [AggregaterAuthController::class, 'login']);
    
    Route::middleware(['auth:sanctum'])->group(function () {

     //   Route::get('aggregater/profile', [AggregaterAuthController::class, 'profile']);
      

    });

  
  
});
