<?php

namespace PerfectDayLlc\TwilioA2PBundle;

use Illuminate\Support\ServiceProvider;
use PerfectDayLlc\TwilioA2PBundle\Console\RegisterClients;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;

class TwilioA2PBundleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/twilioa2pbundle.php', 'twilioa2pbundle');

        $this->app->bind(
            RegisterService::class,
            fn () => new RegisterService(
                config('services.twilio.sid'),
                config('services.twilio.token'),
                config('services.twilio.primary_customer_profile_sid')
            )
        );
    }

    public function boot()
    {
        if (app()->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            $this->publishes([
                __DIR__.'/../config/twilioa2pbundle.php' => config_path('perfectdayllc/twilioa2pbundle.php'),
            ], 'twilio-a2p-bundle-config');

            $this->commands([
                RegisterClients::class,
            ]);
        }
    }
}
