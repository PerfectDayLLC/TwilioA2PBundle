<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Unit\Models;

use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\ClientRegistrationHistory as ClientRegistrationHistoryFake;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;
use function factory;
use function now;

class ClientRegistrationHistoryTest extends TestCase
{
    /**
     * @dataProvider allowedStatusesProvider
     */
    public function test_get_latest_bundle_sid_for_allowed_statuses(string $status): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create();

        // Old (not recent)
        factory(ClientRegistrationHistoryFake::class)
            ->create([
                'entity_id' => $entity->id,
                'request_type' => 'messageService',
                'status' => $status,
                'created_at' => now()->minutes(-6)
            ]);

        // The one I expect to get back as the latest
        factory(ClientRegistrationHistoryFake::class)
            ->create([
                'entity_id' => $entity->id,
                'request_type' => 'messageService',
                'bundle_sid' => $myBundleSid = 'my_bundle_id_123',
                'status' => $status,
                'created_at' => now()->minutes(-5)
            ]);

        // Latest but not assigned to my entity
        factory(ClientRegistrationHistoryFake::class)
            ->create([
                'request_type' => 'messageService',
                'bundle_sid' => 'my_bundle_id_456',
                'status' => $status,
                'created_at' => now()->minutes(-4)
            ]);

        // Latest but has different request_type
        factory(ClientRegistrationHistoryFake::class)->create([
            'entity_id' => $entity->id,
            'request_type' => 'a2pBundleSubmit',
            'status' => $status,
            'created_at' => now()->minutes(-3)
        ]);

        // Latest but has not allowed status
        factory(ClientRegistrationHistoryFake::class)->create([
            'entity_id' => $entity->id,
            'request_type' => 'messageService',
            'status' => 'compliant',
            'created_at' => now()->minutes(-2)
        ]);

        $latestBundleSid = ClientRegistrationHistoryFake::getSidForAllowedStatuses(
            'messageService',
            $entity->id
        );

        $this->assertEquals($myBundleSid, $latestBundleSid, 'Bundle Sid does not match');
    }

    /**
     * @dataProvider allowedStatusesProvider
     */
    public function test_it_returns_null_when_none_found(string $status): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create();

        // Latest
        factory(ClientRegistrationHistoryFake::class)->create([
            'entity_id' => $entity->id,
            'request_type' => 'messageService',
            'status' => 'compliant',
            'created_at' => now()->minutes(-3)
        ]);

        // Latest but not assigned to my entity
        factory(ClientRegistrationHistoryFake::class)
            ->create([
                'request_type' => 'messageService',
                'status' => $status,
                'created_at' => now()->minutes(-2)
            ]);

        $latestBundleSid = ClientRegistrationHistoryFake::getSidForAllowedStatuses(
            'messageService',
            $entity->id
        );

        $this->assertNull($latestBundleSid, 'Bundle Sid found when it should not');
    }

    public function allowedStatusesProvider(): array
    {
        return [
            'Bundle Pending Review' => [Status::BUNDLES_PENDING_REVIEW],
            'Bundle In Review' => [Status::BUNDLES_IN_REVIEW],
            'Bundle Twilio Approved' => [Status::BUNDLES_TWILIO_APPROVED],
            'Brand Pending' => [Status::BRAND_PENDING],
            'Brand Approved' => [Status::BRAND_APPROVED],
            'Error' => [Status::EXCEPTION_ERROR],
        ];
    }
}
