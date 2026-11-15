<?php

namespace App\Providers;

use App\Domain\Cadeaus\CadeauPersistence;
use App\Infrastructure\Cadeaus\EloquentCadeauPersistence;
use Filament\Http\Responses\Auth\LoginResponse;
use Filament\Navigation\NavigationManager;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(CadeauPersistence::class, EloquentCadeauPersistence::class);
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

        if (Config::get('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
