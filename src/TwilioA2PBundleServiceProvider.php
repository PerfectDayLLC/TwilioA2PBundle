<?php

namespace PerfectDayLlc\TwilioA2PBundle;

use Illuminate\Support\ServiceProvider;
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
                config('services.twilio.primary_customer_profile_sid'), // Look for this SID on Twilio's Unotifi User
                config('services.twilio.customer_profile_policy_sid'),
                config('services.twilio.a2p_profile_policy_sid'),
                config('services.twilio.profile_policy_type')
            )
        );
    }

    public function boot()
    {
        if (app()->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

            $this->publishes([
                __DIR__.'/../config/twilioa2pbundle.php' => config_path('perfectdayllc/twilioa2pbundle.php')
            ], 'twilio-a2p-bundle-config');

            $version = '8.x+';
            if (version_compare($this->app->version(), '8', '<')) {
                $version = '7.x-';
            }

            $this->publishes([
                __DIR__."/../database/factories/$version/ClientRegistrationHistoryFactory.php" =>
                    database_path('factories/ClientRegistrationHistoryFactory.php')
            ], 'twilio-a2p-bundle-factories');

            $this->commands([
                Commands\RegisterClients::class
            ]);
        }
    }
}
