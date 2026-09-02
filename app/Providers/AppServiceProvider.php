<?php

namespace App\Providers;

use App\Services\WhatsApp\CloudApiGateway;
use App\Services\WhatsApp\LogGateway;
use App\Services\WhatsApp\WhatsAppGateway;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // [SISTEM KUA] Driver WhatsApp dipilih lewat config/whatsapp.php.
        $this->app->singleton(WhatsAppGateway::class, function () {
            return config('whatsapp.driver') === 'cloud'
                ? new CloudApiGateway
                : new LogGateway;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
