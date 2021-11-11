<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Feature\Commands\RegisterClients;

use Illuminate\Support\Facades\Queue;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateA2PSmsCampaignUseCase;
use PerfectDayLlc\TwilioA2PBundle\Jobs\SubmitA2PTrustBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\SubmitCustomerProfileBundle;
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

    public function test_command_should_dispatch_a_customer_profile_creation_job_when_there_is_no_entity_history(): void
    {
        Queue::fake();

        $this->artisan('a2p:client-register')
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
        Queue::assertNotPushed(CreateA2PSmsCampaignUseCase::class);
    }

    public function test_command_should_dispatch_a_submit_a2p_trust_bundle_job_when_specific_request_type_is_found_and_one_day_passed(): void
    {
        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $this->entity,
            'request_type' => 'submitCustomerProfileBundle',
            'status' => $this->faker()->randomElement(Status::getOngoingA2PStatuses())
        ]);

        $this->travel(1)->day();

        Queue::fake();

        $this->artisan('a2p:client-register')
            ->assertExitCode(0);

        Queue::assertPushedOn(
            'submit-a2p-profile-bundle',
            SubmitA2PTrustBundle::class,
            function (SubmitA2PTrustBundle $job) {
                return $job->client == ($clientData = $this->entity->getClientData()) &&
                       $job->registerService == $this->registerService &&
                       $job->customerProfileBundleSid === $clientData->getClientRegistrationHistoryModel()->bundle_sid &&
                       $job->webhookUrl === $clientData->getWebhookUrl() &&
                       $job->fallbackWebhookUrl === $clientData->getFallbackWebhookUrl() &&
                       $job->createA2PBrand === true &&
                       $job->createMessagingService === true;
            }
        );

        Queue::assertNotPushed(SubmitCustomerProfileBundle::class);
        Queue::assertNotPushed(CreateA2PSmsCampaignUseCase::class);
    }

    /**
     * @dataProvider createSmsCampaignAllowedStatusesProvider
     */
    public function test_command_should_dispatch_a_create_a2p_sms_campaign_use_case_job_when_specific_request_type_is_found_and_one_day_passed(string $requestType): void
    {
        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $this->entity,
            'request_type' => $requestType,
            'status' => $this->faker()->randomElement(Status::getOngoingA2PStatuses())
        ]);

        $this->travel(1)->day();

        Queue::fake();

        $this->artisan('a2p:client-register')
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
        Queue::assertNotPushed(SubmitCustomerProfileBundle::class);
    }

    /**
     * @dataProvider clientRegistrationHistoriesNotAllowedStatusesProvider
     */
    public function test_command_should_not_dispatch_any_job_when_the_status_is_not_one_of_the_allowed_ones(string $status): void
    {
        Queue::fake();

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $this->entity,
            'status' => $status
        ]);

        $this->artisan('a2p:client-register')
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    public function createSmsCampaignAllowedStatusesProvider(): array
    {
        return [
            'Create A2P Brand' => ['createA2PBrand'],
            'Create Messaging Service' => ['createMessagingService'],
            'Add Phone Number to Messaging Service' => ['addPhoneNumberToMessagingService'],
        ];
    }

    public function clientRegistrationHistoriesNotAllowedStatusesProvider(): array
    {
        return collect(Status::getConstants())
            ->diff(Status::getOngoingA2PStatuses())
            ->mapWithKeys(fn (string $status, string $key) => [str_headline($key) => [$status]])
            ->toArray();
    }

    private function createExpectedService(): RegisterService
    {
        config([
            'services.twilio.sid' => $sid = 'twilio sid 123',
            'services.twilio.token' => $token = 'twilio token 321',
            'services.twilio.primary_customer_profile_sid' => $primaryCustomerSid = 'primary customer sid 555',
            'services.twilio.customer_profile_policy_sid' => $customerProfileSid = 'customer profile sid 789',

            'twilioa2pbundle.entity_model' => Entity::class,
        ]);

        return new RegisterService(
            $sid,
            $token,
            $primaryCustomerSid,
            $customerProfileSid
        );
    }
}
