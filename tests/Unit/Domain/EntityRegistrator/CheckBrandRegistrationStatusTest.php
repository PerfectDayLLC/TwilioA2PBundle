<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Unit\Domain\EntityRegistrator;

use Illuminate\Support\Facades\Queue;
use PerfectDayLlc\TwilioA2PBundle\Facades\EntityRegistrator;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand\CheckA2PBrandStatus;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;

class CheckBrandRegistrationStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setConfigForService();

        Queue::fake();
    }

    public function test_should_dispatch_job_to_check_entity(): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create();

        $objectSid = $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'createA2PBrand',
            'status' => 'pending',
        ])
            ->object_sid;

        $this->travel(1)->second();

        EntityRegistrator::checkBrandRegistrationStatus($entity);

        Queue::assertPushed(CheckA2PBrandStatus::class, 1);

        Queue::assertPushedOn(
            'create-a2p-brand-job',
            CheckA2PBrandStatus::class,
            function (CheckA2PBrandStatus $job) use ($entity, $objectSid) {
                return $job->client == $entity->getClientData() &&
                       $job->brandObjectSid === $objectSid;
            }
        );
    }
}
