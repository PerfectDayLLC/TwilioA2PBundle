<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase as BaseTestCase;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\ClientRegistrationHistory as ClientRegistrationHistoryFake;
use PerfectDayLlc\TwilioA2PBundle\TwilioA2PBundleServiceProvider;

class TestCase extends BaseTestCase
{
    use RefreshDatabase, WithFaker;

    protected function getPackageProviders($app): array
    {
        return [
            TestServiceProvider::class,
            TwilioA2PBundleServiceProvider::class,
        ];
    }

    protected function createRealClientRegistrationHistoryModel(array $parameters = []): ClientRegistrationHistory
    {
        $model = ClientRegistrationHistory::create(
            ClientRegistrationHistoryFake::factory()
                ->make($parameters)
                ->toArray()
        );

        return ClientRegistrationHistory::find($model->id);
    }
}
