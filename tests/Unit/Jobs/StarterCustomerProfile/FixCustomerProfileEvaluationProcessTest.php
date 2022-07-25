<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Unit\Jobs\StarterCustomerProfile;

use Illuminate\Support\Facades\Queue;
use PerfectDayLlc\TwilioA2PBundle\Facades\Registrator as RegistratorFacade;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\EvaluateCustomerProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\FixCustomerProfileEvaluationProcess;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;

class FixCustomerProfileEvaluationProcessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setConfigForService();
    }

    public function test_should_process_pending_brand_status(): void
    {
        /** @var Entity $expectedEntity */
        $expectedEntity = factory(Entity::class)->create();

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $expectedEntity,
            'request_type' => 'createEmptyCustomerProfileStarterBundle',
        ]);

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $expectedEntity,
            'request_type' => 'createEndUserCustomerProfileInfo',
        ]);

        $this->travel(1)->second();

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $expectedEntity,
            'request_type' => 'evaluateCustomerProfileBundle',
            'status' => 'noncompliant',
            'error' => 1,
        ]);

        $spy = RegistratorFacade::spy();

        Queue::fake();

        (new FixCustomerProfileEvaluationProcess($expectedEntity->getClientData()))
            ->handle();

        $spy->shouldHaveReceived('updateEndUserCustomerProfileInfo')
            ->once();

        Queue::assertPushed(EvaluateCustomerProfileBundle::class, 1);

        Queue::assertPushedOn(
            'submit-customer-profile-bundle',
            EvaluateCustomerProfileBundle::class,
            fn (EvaluateCustomerProfileBundle $job) => $job->client == $expectedEntity->getClientData()
        );
    }
}
