<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase as BaseTestCase;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\ClientRegistrationHistory as ClientRegistrationHistoryFake;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;
use PerfectDayLlc\TwilioA2PBundle\Tests\Traits\InteractsWithTime;
use PerfectDayLlc\TwilioA2PBundle\TwilioA2PBundleServiceProvider;

class TestCase extends BaseTestCase
{
    use InteractsWithTime, RefreshDatabase, WithFaker;

    protected function getPackageProviders($app): array
    {
        return [
            TestServiceProvider::class,
            TwilioA2PBundleServiceProvider::class,
        ];
    }

    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/Database/Migrations');
    }

    protected function createRealClientRegistrationHistoryModel(array $parameters = []): ClientRegistrationHistory
    {
        return factory(ClientRegistrationHistoryFake::class)
                ->create($parameters)
                ->fresh();
    }

    protected function createExpectedService(): RegisterService
    {
        config([
            'services.twilio.sid' => $sid = 'twilio sid 123',
            'services.twilio.token' => $token = 'twilio token 321',
            'services.twilio.primary_customer_profile_sid' => $primaryCustomerSid = 'primary customer sid 555',

            'twilioa2pbundle.entity_model' => Entity::class,
        ]);

        return new RegisterService(
            $sid,
            $token,
            $primaryCustomerSid
        );
    }
}
