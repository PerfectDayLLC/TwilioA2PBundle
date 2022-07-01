<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests;

use Faker\Generator;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }

    public function boot()
    {
        // Needs to be in here, don't move to register
        $this->app->singleton(Factory::class, function ($app) {
            return Factory::construct(
                $app->make(Generator::class), __DIR__.'/Database/Factories'
            );
        });
    }
}
