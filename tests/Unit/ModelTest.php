<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Unit;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Entities\ClientOwnerData;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\ClientRegistrationHistory as ClientRegistrationHistoryFake;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;

class ModelTest extends TestCase
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

        $latestBundleSid = ClientRegistrationHistoryFake::getBundleSidForAllowedStatuses(
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

        $latestBundleSid = ClientRegistrationHistoryFake::getBundleSidForAllowedStatuses(
            'messageService',
            $entity->id
        );

        $this->assertEmpty($latestBundleSid, 'Bundle Sid found when it should not');
    }

    /**
     * @testWith [true]
     *           [false]
     */
    public function test_get_client_data_returns_expected_data(bool $hasHistory): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create([
            'company_name' => $companyName = 'test company',
            'address' => $address = 'Address 123 A',
            'city' => $city = 'Tampa',
            'state' => $state = 'FL',
            'zip' => $zip = '33603',
            'country' => $country = 'US',
            'phone_number' => $twilioPhoneNumber = '+11234567789',
            'twilio_phone_number_sid' => $phoneNumberSid = 'PN5Y2SFD389D6123AK',
            'website' => $website = 'https://fake.url.com',
            'contact_first_name' => $firstName = 'John',
            'contact_last_name' => $lastName = 'Doe',
            'contact_email' => $email = 'john.doe@gmail.net',
            'contact_phone' => $contactPhoneNumber = '+11234567777',
            'webhook_url' => $webhookUrl = 'https://fake.webhook.com',
            'fallback_webhook_url' => $fallbackWebhookUrl = 'https://fake.fallback.webhook',
        ]);

        /** @var ClientRegistrationHistory|null $clientRegistrationHistoryModel */
        $clientRegistrationHistoryModel = null;
        if ($hasHistory) {
            $clientRegistrationHistoryModel = $this->createRealClientRegistrationHistoryModel(['entity_id' => $entity]);
        }

        $client = new ClientData(
            $entity->id,
            $companyName,
            $address,
            $city,
            $state,
            $zip,
            $country,
            $twilioPhoneNumber,
            $phoneNumberSid,
            $website,
            $firstName,
            $lastName,
            $email,
            $contactPhoneNumber,
            $webhookUrl,
            $fallbackWebhookUrl,
            new ClientOwnerData(
                $firstName,
                $lastName,
                $email
            ),
            $clientRegistrationHistoryModel
        );

        $this->assertEquals($client, $entity->getClientData(), "The ClientData's data does not match");
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
