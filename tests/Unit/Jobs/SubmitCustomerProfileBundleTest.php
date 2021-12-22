<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Unit\Jobs;

use PerfectDayLlc\TwilioA2PBundle\Jobs\SubmitCustomerProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;

class SubmitCustomerProfileBundleTest extends TestCase
{
    public function test_command_dispatches_submit_customer_profile_bundle_job(): void
    {
        $this->markTestIncomplete();
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create();

        (new SubmitCustomerProfileBundle(resolve(RegisterService::class), $entity->getClientData()))->handle();
    }
}
