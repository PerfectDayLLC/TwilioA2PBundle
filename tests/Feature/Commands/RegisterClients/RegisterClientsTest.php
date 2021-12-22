<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Feature\Commands\RegisterClients;

use Illuminate\Support\Facades\Queue;
use PerfectDayLlc\TwilioA2PBundle\Commands\RegisterClients;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateA2PBrand;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateA2PSmsCampaignUseCase;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateMessagingService;
use PerfectDayLlc\TwilioA2PBundle\Jobs\SubmitA2PTrustBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\SubmitCustomerProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;

class RegisterClientsTest extends TestCase
{
    private RegisterService $registerService;

    private Entity $entity;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerService = $this->createExpectedService();

        /** @var Entity $entity */
        $entity = factory(Entity::class)->create([
            'company_name' => 'test company',
            'address' => 'Address 123 A',
            'city' => 'Tampa',
            'state' => 'FL',
            'zip' => '33603',
            'country' => 'US',
            'phone_number' => '+11234567789',
            'twilio_phone_number_sid' => 'PN5Y2SFD389D6123AK',
            'website' => 'https://fake.url.com',
            'contact_first_name' => 'John',
            'contact_last_name' => 'Doe',
            'contact_email' => 'john.doe@gmail.net',
            'contact_phone' => '+11234567777',
            'webhook_url' => 'https://webhook.url/123/abc',
            'fallback_webhook_url' => 'https://fallbackwebhook.url/abc/123',
        ]);
        $this->entity = $entity;
    }

    public function test_command_has_correct_data(): void
    {
        $command = new RegisterClients;

        $this->assertSame($command->getName(), 'a2p:client-register', 'Wrong signature.');
        $this->assertSame(
            'Twilio - Register all companies for the A2P 10DLC US carrier standard compliance',
            $command->getDescription(),
            'Wrong description');
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_a_customer_profile_creation_job_when_there_is_no_entity_history(): void
    {
        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        Queue::assertPushedOn(
            'submit-customer-profile-bundle',
            SubmitCustomerProfileBundle::class,
            function (SubmitCustomerProfileBundle $job) {
                return $job->client == $this->entity->getClientData() &&
                       $job->registerService == $this->registerService;
            }
        );

        Queue::assertNotPushed(SubmitA2PTrustBundle::class);
        Queue::assertNotPushed(CreateA2PBrand::class);
        Queue::assertNotPushed(CreateMessagingService::class);
        Queue::assertNotPushed(CreateA2PSmsCampaignUseCase::class);
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_a_submit_a2p_trust_bundle_job_when_specific_request_type_is_found(): void
    {
        $history = $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $this->entity,
            'request_type' => 'submitCustomerProfileBundle',
            'status' => $this->faker()->randomElement(Status::getOngoingA2PStatuses())
        ]);

        $this->travel(1)->day();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        Queue::assertPushedOn(
            'submit-a2p-profile-bundle',
            SubmitA2PTrustBundle::class,
            function (SubmitA2PTrustBundle $job) use ($history) {
                return $job->client == $this->entity->getClientData() &&
                       $job->registerService == $this->registerService &&
                       $job->customerProfileBundleSid === $history->bundle_sid;
            }
        );

        Queue::assertNotPushed(SubmitCustomerProfileBundle::class);
        Queue::assertNotPushed(CreateA2PBrand::class);
        Queue::assertNotPushed(CreateMessagingService::class);
        Queue::assertNotPushed(CreateA2PSmsCampaignUseCase::class);
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_a_create_a2p_brand_job_when_specific_request_type_is_found(): void
    {
        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $this->entity,
            'request_type' => 'submitA2PProfileBundle',
            'status' => $this->faker()->randomElement(Status::getOngoingA2PStatuses())
        ]);

        $this->travel(1)->day();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        Queue::assertPushedOn(
            'create-a2p-brand-job',
            CreateA2PBrand::class,
            function (CreateA2pBrand $job) {
                return $job->client == ($this->entity->getClientData()) &&
                       $job->registerService == $this->registerService;
            }
        );

        Queue::assertNotPushed(SubmitCustomerProfileBundle::class);
        Queue::assertNotPushed(SubmitA2pTrustBundle::class);
        Queue::assertNotPushed(CreateMessagingService::class);
        Queue::assertNotPushed(CreateA2PSmsCampaignUseCase::class);
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_a_create_messaging_service_job_when_specific_request_type_is_found(): void
    {
        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $this->entity,
            'request_type' => 'createA2PBrand',
            'status' => $this->faker()->randomElement(Status::getOngoingA2PStatuses())
        ]);

        $this->travel(1)->day();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        Queue::assertPushedOn(
            'create-messaging-service',
            CreateMessagingService::class,
            function (CreateMessagingService $job) {
                return $job->client == ($this->entity->getClientData()) &&
                       $job->registerService == $this->registerService;
            }
        );

        Queue::assertNotPushed(SubmitCustomerProfileBundle::class);
        Queue::assertNotPushed(SubmitA2pTrustBundle::class);
        Queue::assertNotPushed(CreateA2pBrand::class);
        Queue::assertNotPushed(CreateA2PSmsCampaignUseCase::class);
    }

    /**
     * @depends test_command_has_correct_data
     * @dataProvider createSmsCampaignAllowedStatusesProvider
     */
    public function test_command_should_dispatch_a_create_a2p_sms_campaign_use_case_job_when_specific_request_type_is_found_and_one_day_passed(
        string $originalRequestType,
        array  $requiredHistoryRequestTypes
    ): void {
        foreach ($requiredHistoryRequestTypes as $requestType) {
            $this->travel(1)->second();

            $this->createRealClientRegistrationHistoryModel([
                'entity_id' => $this->entity,
                'request_type' => $requestType,
                'status' => $this->faker()->randomElement(ClientRegistrationHistory::ALLOWED_STATUSES_TYPES),
            ]);
        }

        $this->travel(1)->second();

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $this->entity,
            'request_type' => $originalRequestType,
            'status' => $this->faker()->randomElement(ClientRegistrationHistory::ALLOWED_STATUSES_TYPES),
        ]);

        $this->travel(1)->day();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        Queue::assertPushedOn(
            'create-a2p-sms-campaign-use-case-job',
            CreateA2PSmsCampaignUseCase::class,
            function (CreateA2PSmsCampaignUseCase $job) {
                return $job->client == $this->entity->getClientData() &&
                       $job->registerService == $this->registerService;
            }
        );

        Queue::assertNotPushed(SubmitA2PTrustBundle::class);
        Queue::assertNotPushed(CreateA2PBrand::class);
        Queue::assertNotPushed(CreateMessagingService::class);
        Queue::assertNotPushed(SubmitCustomerProfileBundle::class);
    }

    /**
     * @depends test_command_has_correct_data
     * @dataProvider clientRegistrationHistoriesNotAllowedStatusesProvider
     */
    public function test_command_should_not_dispatch_any_job_when_the_status_is_not_one_of_the_allowed_ones(string $status): void
    {
        Queue::fake();

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $this->entity,
            'status' => $status
        ]);

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function createSmsCampaignAllowedStatusesProvider(): array
    {
        return [
            'Add Phone Number to Messaging Service' => [
                'addPhoneNumberToMessagingService',
                ['createA2PBrand', 'createMessagingService'],
            ],
        ];
    }

    public function clientRegistrationHistoriesNotAllowedStatusesProvider(): array
    {
        return collect(Status::getConstants())
            ->diff(ClientRegistrationHistory::ALLOWED_STATUSES_TYPES)
            ->mapWithKeys(fn (string $status, string $key) => [str_headline($key) => [$status]])
            ->toArray();
    }
}
