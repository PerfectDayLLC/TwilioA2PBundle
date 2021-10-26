<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests;

use Illuminate\Support\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }
}
