<?php

// app/Providers/SanctumServiceProvider.php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use App\Models\PersonalAccessToken;

class SanctumServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
//Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
    }
}

