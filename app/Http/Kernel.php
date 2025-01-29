<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    // Global middleware runs on every request
    protected $middleware = [
        // Other global middleware...
        
    ];
    protected $commands = [
        \App\Console\Commands\MigrateOems::class,
    ];
    // Middleware groups for different middleware stacks
    protected $middlewareGroups = [
        'api' => [
        
            'auth:sanctum',
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
          
        ],
        'web' => [
     
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        // Other middleware
    ],
    ];
    protected $routeMiddleware = [
        // Other route-specific middleware...
       
        'auth' => \App\Http\Middleware\Authenticate::class,
       
        'auth:sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,  // Sanctum middleware
        'auth:superadmin' => \App\Http\Middleware\SuperadminMiddleware::class,
    ];
    
   
}
