<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Unit;

use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;

class ModelTest extends TestCase
{
    /**
     * @dataProvider allowedStatusesProvider
     */
    public function test_get_latest_bundle_sid_for_allowed_statuses(string $status): void
    {
        $entity = Entity::factory()->create();

        // Old (not recent)
        ClientRegistrationHistory::factory()
            ->create([
                'entity_id' => $entity->id,
                'request_type' => 'messageService',
                'status' => $status,
                'created_at' => now()->minutes(-6)
            ]);

        // The one I expect to get back as the latest
        ClientRegistrationHistory::factory()
            ->create([
                'entity_id' => $entity->id,
                'request_type' => 'messageService',
                'bundle_sid' => $myBundleSid = 'my_bundle_id_123',
                'status' => $status,
                'created_at' => now()->minutes(-5)
            ]);

        // Latest but not assigned to my entity
        ClientRegistrationHistory::factory()
            ->create([
                'request_type' => 'messageService',
                'bundle_sid' => 'my_bundle_id_456',
                'status' => $status,
                'created_at' => now()->minutes(-4)
            ]);

        // Latest but has different request_type
        ClientRegistrationHistory::factory()->create([
            'entity_id' => $entity->id,
            'request_type' => 'a2pBundleSubmit',
            'status' => $status,
            'created_at' => now()->minutes(-3)
        ]);

        // Latest but has not allowed status
        ClientRegistrationHistory::factory()->create([
            'entity_id' => $entity->id,
            'request_type' => 'messageService',
            'status' => 'compliant',
            'created_at' => now()->minutes(-2)
        ]);

        $latestBundleSid = ClientRegistrationHistory::getBundleSidForAllowedStatuses(
            'messageService',
            $entity->id
        );

        $this->assertEquals($myBundleSid, $latestBundleSid, 'Bundle Sid does not match');
    }

    /**
     * @dataProvider allowedStatusesProvider
     */
    public function test_it_returns_empty_string_when_none_found(string $status): void
    {
        $entity = Entity::factory()->create();

        // Latest
        ClientRegistrationHistory::factory()->create([
            'entity_id' => $entity->id,
            'request_type' => 'messageService',
            'status' => 'compliant',
            'created_at' => now()->minutes(-3)
        ]);

        // Latest but not assigned to my entity
        ClientRegistrationHistory::factory()
            ->create([
                'request_type' => 'messageService',
                'status' => $status,
                'created_at' => now()->minutes(-2)
            ]);

        $latestBundleSid = ClientRegistrationHistory::getBundleSidForAllowedStatuses(
            'messageService',
            $entity->id
        );

        $this->assertEmpty($latestBundleSid, 'Bundle Sid found when it should not');
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
