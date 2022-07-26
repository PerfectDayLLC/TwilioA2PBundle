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
    public function test_should_process_failing_end_user_customer_profile_info(): void
    {
        $this->setConfigForService();

        /** @var Entity $expectedEntity */
        $expectedEntity = factory(Entity::class)->create();

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $expectedEntity,
            'request_type' => 'createEmptyCustomerProfileStarterBundle',
        ]);

        $this->travel(1)->second();

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

        (new FixCustomerProfileEvaluationProcess($expectedEntity->getClientData()))
            ->handle();

        $spy->shouldHaveReceived('updateEndUserCustomerProfileInfo')
            ->once();
    }
}
