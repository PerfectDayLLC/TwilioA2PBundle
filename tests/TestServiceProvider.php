<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests;

use Faker\Generator;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

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

        // Add headline method present in newer Laravel versions into old ones not having it
        if (! method_exists(Str::class, 'headline')) {
            Str::macro('headline', function (string $value) {
                $parts = explode('_', Str::replace(' ', '_', $value));

                if (count($parts) > 1) {
                    $parts = array_map([Str::class, 'title'], $parts);
                }

                $studly = Str::studly(implode($parts));

                $words = preg_split('/(?=[A-Z])/', $studly, -1, PREG_SPLIT_NO_EMPTY);

                return implode(' ', $words);
            });
        }
    }
}
