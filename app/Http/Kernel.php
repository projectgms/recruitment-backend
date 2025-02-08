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
      
    ];

    // Middleware groups for different middleware stacks
    protected $middlewareGroups = [
        'api' => [
            'auth:api', // Use the JWT auth middleware here
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            'throttle:api', // Throttle requests for the API
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
        'web' => [
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // Other web-specific middleware
        ],
    ];

    protected $routeMiddleware = [
        // Other route-specific middleware...
        'auth' => \App\Http\Middleware\Authenticate::class,
    ];
}
