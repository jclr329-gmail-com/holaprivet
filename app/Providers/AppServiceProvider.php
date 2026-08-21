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
        // El hosting sirve detras de proxy y a veces no anuncia el esquema:
        // si la app vive en https (APP_URL), TODAS las URLs generadas van
        // en https, sea beta o produccion. Sin esto, los formularios salen
        // apuntando a http:// y Edge marca la pagina como «No seguro».
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
