<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Unit\Entity\EntityRegistrator;

use Illuminate\Support\Facades\Queue;
use PerfectDayLlc\TwilioA2PBundle\Facades\EntityRegistratorFacade;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;

class CheckBrandRegistrationStatusTest extends TestCase
{
    public function test_should_process_pending_brand_status(): void
    {
        $this->markTestSkipped('skip for the moment');

        /** @var Entity $entity */
        $entity = factory(Entity::class)->create();

        Queue::fake();

        EntityRegistratorFacade::checkBrandRegistrationStatus($entity);

        Queue::assertPushedOn(
            'asd',
            fn ($job) => $job->entity->is($entity)
        );
    }
}
