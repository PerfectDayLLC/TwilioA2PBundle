<?php

namespace PerfectDayLlc\TwilioA2PBundle;

use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class TwilioA2PBundleServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/twilioa2pbundle.php', 'twilioa2pbundle');
    }

    public function boot()
    {
        if (app()->runningInConsole()) {
            $this->registerMigrations();

            $this->publishes([
                __DIR__.
                '/../database/migrations/2021_10_25_205900_create_twilio_customer_registration_history_table.php' =>
                    database_path('migrations/'.date('Y_m_d_His').'_create_twilio_customer_registration_history_table.php'),
            ], 'twilio-a2p-bundle-migrations');

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

            $this->publishes([
                __DIR__.'/../src/Models/ClientRegistrationHistory.php' =>
                    is_dir(app_path('Models')) ? app_path('Models/ClientRegistrationHistory.php') : app_path('ClientRegistrationHistory.php')
            ]);

            $this->commands([
                Commands\RegisterClients::class
            ]);
        }
    }

    protected function registerMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
