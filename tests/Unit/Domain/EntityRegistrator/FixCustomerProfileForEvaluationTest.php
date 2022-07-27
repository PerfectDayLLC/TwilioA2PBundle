<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Unit\Domain\EntityRegistrator;

use Illuminate\Support\Facades\Queue;
use PerfectDayLlc\TwilioA2PBundle\Facades\EntityRegistrator;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\FixCustomerProfileEvaluationProcess;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;

class FixCustomerProfileForEvaluationTest extends TestCase
{
    public function test_should_dispatch_job_to_check_entity(): void
    {
        $this->setConfigForService();

        Queue::fake();

        /** @var Entity $entity */
        $entity = factory(Entity::class)->create();

        $expectedHistory = $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'createEndUserCustomerProfileInfo',
        ]);

        $this->travel(1)->second();

        EntityRegistrator::fixCustomerProfileForEvaluation($expectedHistory);

        Queue::assertPushed(FixCustomerProfileEvaluationProcess::class, 1);

        Queue::assertPushedOn(
            'submit-customer-profile-bundle',
            FixCustomerProfileEvaluationProcess::class,
            function (FixCustomerProfileEvaluationProcess $job) use ($entity, $expectedHistory) {
                return $job->client == $entity->getClientData() &&
                       $job->endUserCustomerProfileInfo->is($expectedHistory);
            }
        );
    }
}
