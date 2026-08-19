<?php

namespace App\Providers;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // El hosting sirve detras de proxy: forzamos HTTPS en produccion.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
