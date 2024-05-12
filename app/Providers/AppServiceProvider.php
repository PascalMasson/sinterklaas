<?php

namespace App\Providers;

use Filament\Http\Responses\Auth\LoginResponse;
use Filament\Navigation\NavigationManager;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->singleton(
            LoginResponse::class,
            \App\Http\Responses\LoginResponse::class
        );

        $this->app->singleton(
            NavigationManager::class,
            \App\Navigation\NavigationManager::class
        );
    }
}
