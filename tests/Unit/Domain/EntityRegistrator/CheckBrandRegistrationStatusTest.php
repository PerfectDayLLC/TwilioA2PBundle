<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Unit\Domain\EntityRegistrator;

use Illuminate\Support\Facades\Queue;
use PerfectDayLlc\TwilioA2PBundle\Facades\EntityRegistratorFacade;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand\CheckA2PBrandStatus;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;

class CheckBrandRegistrationStatusTest extends TestCase
{
    private RegisterService $registerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerService = $this->createExpectedService();

        Queue::fake();
    }

    public function test_should_process_pending_brand_status(): void
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

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'createA2PBrand',
            'status' => 'exception-error',
        ]);

        Queue::fake();

        EntityRegistratorFacade::checkBrandRegistrationStatus($entity);

        Queue::assertPushedOn(
            'create-a2p-brand-job',
            CheckA2PBrandStatus::class,
            function (CheckA2PBrandStatus $job) use ($entity, $objectSid) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService &&
                       $job->brandObjectSid === $objectSid;
            }
        );
    }
}
