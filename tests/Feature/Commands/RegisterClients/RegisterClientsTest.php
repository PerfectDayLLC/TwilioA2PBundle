<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Feature\Commands\RegisterClients;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Queue;
use PerfectDayLlc\TwilioA2PBundle\Console\RegisterClients;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrandStarter\AssignCustomerProfileA2PTrustBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrandStarter\CreateEmptyA2PStarterTrustBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrandStarter\EvaluateA2PStarterProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrandStarter\SubmitA2PProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateA2PBrand;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateA2PSmsCampaignUseCase;
use PerfectDayLlc\TwilioA2PBundle\Jobs\MessagingService\AddPhoneNumberToMessagingService;
use PerfectDayLlc\TwilioA2PBundle\Jobs\MessagingService\CreateMessagingService;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\AttachObjectSidToCustomerProfile;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\CreateCustomerProfileAddress;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\CreateCustomerSupportDocs;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\CreateEmptyCustomerProfileStarterBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\CreateEndUserCustomerProfileInfo;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\EvaluateCustomerProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\SubmitCustomerProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;

class RegisterClientsTest extends TestCase
{
    private const ENTITY_DATA = [
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
    ];

    private RegisterService $registerService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerService = $this->createExpectedService();
    }

    protected function tearDown(): void
    {
        Entity::$customQuery = null;

        parent::tearDown();
    }

    public function test_command_has_correct_data(): void
    {
        $command = new RegisterClients;

        $this->assertSame($command->getName(), 'a2p:client-register', 'Wrong signature.');

        $this->assertSame(
            'Twilio - Register all companies for the A2P 10DLC US carrier standard compliance',
            $command->getDescription(),
            'Wrong description'
        );
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_create_empty_customer_profile_starter_bundle_job_when_there_is_no_entity_history(): void
    {
        Queue::fake();

        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(self::ENTITY_DATA);

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $this->assertOnlyPushedOn(
            'submit-customer-profile-bundle',
            CreateEmptyCustomerProfileStarterBundle::class,
            function (CreateEmptyCustomerProfileStarterBundle $job) use ($entity) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService;
            }
        );
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_a_create_end_user_customer_profile_info_job_when_there_is_history(): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(self::ENTITY_DATA);

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'createEmptyCustomerProfileStarterBundle',
        ]);

        $this->travel(1)->second();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $this->assertOnlyPushedOn(
            'submit-customer-profile-bundle',
            CreateEndUserCustomerProfileInfo::class,
            function (CreateEndUserCustomerProfileInfo $job) use ($entity) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService;
            }
        );
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_a_create_customer_profile_address_job_when_there_is_history(): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(self::ENTITY_DATA);

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'createEndUserCustomerProfileInfo',
        ]);

        $this->travel(1)->second();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $this->assertOnlyPushedOn(
            'submit-customer-profile-bundle',
            CreateCustomerProfileAddress::class,
            function (CreateCustomerProfileAddress $job) use ($entity) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService;
            }
        );
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_a_create_customer_support_docs_job_when_there_is_history(): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(self::ENTITY_DATA);

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'createCustomerProfileAddress',
        ]);

        $this->travel(1)->second();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $this->assertOnlyPushedOn(
            'submit-customer-profile-bundle',
            CreateCustomerSupportDocs::class,
            function (CreateCustomerSupportDocs $job) use ($entity) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService;
            }
        );
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_a_attach_object_sid_to_customer_profile_job_when_there_is_history(): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(self::ENTITY_DATA);

        $requiredHistoryRequestTypes = [
            'createEmptyCustomerProfileStarterBundle' => 'bundle_sid',
            'createEndUserCustomerProfileInfo' => 'object_sid',
        ];

        foreach ($requiredHistoryRequestTypes as $requestType => $type) {
            $this->travel(1)->second();

            $requiredHistoryRequestTypes[$requestType] = $this->createRealClientRegistrationHistoryModel([
                'entity_id' => $entity,
                'request_type' => $requestType,
            ])->{$type};
        }

        $this->travel(1)->second();

        $requiredHistoryRequestTypes['createCustomerSupportDocs'] = $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'createCustomerSupportDocs',
        ])->object_sid;

        $this->travel(1)->second();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $this->assertOnlyPushedOn(
            'submit-customer-profile-bundle',
            AttachObjectSidToCustomerProfile::class,
            function (AttachObjectSidToCustomerProfile $job) use ($entity, $requiredHistoryRequestTypes) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService &&
                       $job->customerProfilesInstanceSid === $requiredHistoryRequestTypes['createEmptyCustomerProfileStarterBundle'] &&
                       $job->endUserInstanceSid === $requiredHistoryRequestTypes['createEndUserCustomerProfileInfo'] &&
                       $job->supportingDocumentInstanceSid === $requiredHistoryRequestTypes['createCustomerSupportDocs'];
            }
        );
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_a_evaluate_customer_profile_bundle_job_when_there_is_history(): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(self::ENTITY_DATA);

        $this->travel(1)->second();

        $requiredHistoryRequestType = $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'createEmptyCustomerProfileStarterBundle',
        ])->bundle_sid;

        $this->travel(1)->second();

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'attachObjectSidToCustomerProfile',
        ]);

        $this->travel(1)->second();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $this->assertOnlyPushedOn(
            'submit-customer-profile-bundle',
            EvaluateCustomerProfileBundle::class,
            function (EvaluateCustomerProfileBundle $job) use ($entity, $requiredHistoryRequestType) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService &&
                       $job->customerProfileBundleSid === $requiredHistoryRequestType;
            }
        );
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_a_submit_customer_profile_bundle_job_when_there_is_history(): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(self::ENTITY_DATA);

        $requiredHistoryRequestTypes = [
            'createEmptyCustomerProfileStarterBundle' => 'bundle_sid',
        ];

        foreach ($requiredHistoryRequestTypes as $requestType => $type) {
            $this->travel(1)->second();

            $requiredHistoryRequestTypes[$requestType] = $this->createRealClientRegistrationHistoryModel([
                'entity_id' => $entity,
                'request_type' => $requestType,
            ])->{$type};
        }

        $this->travel(1)->second();

        $requiredHistoryRequestTypes['evaluateCustomerProfileBundle'] = $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'evaluateCustomerProfileBundle',
        ])->status;

        $this->travel(1)->second();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $this->assertOnlyPushedOn(
            'submit-customer-profile-bundle',
            SubmitCustomerProfileBundle::class,
            function (SubmitCustomerProfileBundle $job) use ($entity, $requiredHistoryRequestTypes) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService &&
                       $job->customerProfilesInstanceSid === $requiredHistoryRequestTypes['createEmptyCustomerProfileStarterBundle'] &&
                       $job->customerProfilesEvaluationsInstanceStatus === $requiredHistoryRequestTypes['evaluateCustomerProfileBundle'];
            }
        );
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_a_create_empty_a2p_starter_trust_bundle_job_when_specific_request_type_is_found(): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(self::ENTITY_DATA);

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'submitCustomerProfileBundle',
        ]);

        $this->travel(1)->second();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $this->assertOnlyPushedOn(
            'submit-a2p-profile-bundle',
            CreateEmptyA2PStarterTrustBundle::class,
            function (CreateEmptyA2PStarterTrustBundle $job) use ($entity) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService;
            }
        );
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_a_assign_customer_profile_a2p_trust_bundle_job_when_specific_request_type_is_found(): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(self::ENTITY_DATA);

        $history = $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'createEmptyA2PStarterTrustBundle',
        ]);

        $this->travel(1)->second();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $this->assertOnlyPushedOn(
            'submit-a2p-profile-bundle',
            AssignCustomerProfileA2PTrustBundle::class,
            function (AssignCustomerProfileA2PTrustBundle $job) use ($entity, $history) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService &&
                       $job->customerProfileBundleSid === $history->bundle_sid;
            }
        );
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_a_evaluate_a2p_starter_profile_bundle_job_when_specific_request_type_is_found(): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(self::ENTITY_DATA);

        $requiredHistoryRequestType = $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'createEmptyA2PStarterTrustBundle',
        ])->bundle_sid;

        $this->travel(1)->second();

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'assignCustomerProfileA2PTrustBundle',
        ]);

        $this->travel(1)->second();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $this->assertOnlyPushedOn(
            'submit-a2p-profile-bundle',
            EvaluateA2PStarterProfileBundle::class,
            function (EvaluateA2PStarterProfileBundle $job) use ($entity, $requiredHistoryRequestType) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService &&
                       $job->trustProductsInstanceSid === $requiredHistoryRequestType;
            }
        );
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_a_submit_a2p_profile_bundle_job_when_specific_request_type_is_found(): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(self::ENTITY_DATA);

        $requiredHistoryRequestTypes = [
            'createEmptyA2PStarterTrustBundle' => 'bundle_sid',
            'evaluateA2PStarterProfileBundle' => 'status',
        ];

        foreach ($requiredHistoryRequestTypes as $requestType => $type) {
            $this->travel(1)->second();

            $requiredHistoryRequestTypes[$requestType] = $this->createRealClientRegistrationHistoryModel([
                'entity_id' => $entity,
                'request_type' => $requestType,
            ])->{$type};
        }

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'evaluateA2PStarterProfileBundle',
        ]);

        $this->travel(1)->second();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $this->assertOnlyPushedOn(
            'submit-a2p-profile-bundle',
            SubmitA2PProfileBundle::class,
            function (SubmitA2PProfileBundle $job) use ($entity, $requiredHistoryRequestTypes) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService &&
                       $job->trustProductsInstanceSid === $requiredHistoryRequestTypes['createEmptyA2PStarterTrustBundle'] &&
                       $job->trustProductsInstanceStatus === $requiredHistoryRequestTypes['evaluateA2PStarterProfileBundle'];
            }
        );
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_create_a2p_brand_job_when_specific_request_type_is_found(): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(self::ENTITY_DATA);

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'submitA2PProfileBundle',
        ]);

        $this->travel(1)->second();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $this->assertOnlyPushedOn(
            'create-a2p-brand-job',
            CreateA2PBrand::class,
            function (CreateA2pBrand $job) use ($entity) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService;
            }
        );
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_a_create_messaging_service_job_when_specific_request_type_is_found(): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(self::ENTITY_DATA);

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'createA2PBrand',
        ]);

        $this->travel(1)->second();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $this->assertOnlyPushedOn(
            'create-messaging-service',
            CreateMessagingService::class,
            function (CreateMessagingService $job) use ($entity) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService;
            }
        );
    }

    /**
     * @depends test_command_has_correct_data
     */
    public function test_command_should_dispatch_a_add_phone_number_to_messaging_service_job_when_specific_request_type_is_found(): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(self::ENTITY_DATA);

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'createMessagingService',
        ]);

        $this->travel(1)->second();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $this->assertOnlyPushedOn(
            'create-messaging-service',
            AddPhoneNumberToMessagingService::class,
            function (AddPhoneNumberToMessagingService $job) use ($entity) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService;
            }
        );
    }

    /**
     * @depends test_command_has_correct_data
     * @dataProvider createSmsCampaignAllowedStatusesProvider
     */
    public function test_command_should_dispatch_create_a2p_sms_campaign_use_case_job_when_specific_request_type_is_found_and_one_day_passed(
        string $originalRequestType,
        array $requiredHistoryRequestTypes
    ): void {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(self::ENTITY_DATA);

        foreach ($requiredHistoryRequestTypes as $requestType) {
            $this->travel(1)->second();

            $this->createRealClientRegistrationHistoryModel([
                'entity_id' => $entity,
                'request_type' => $requestType,
                'status' => $this->faker()->randomElement(ClientRegistrationHistory::ALLOWED_STATUSES_TYPES),
            ]);
        }

        $this->travel(1)->second();

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => $originalRequestType,
            'status' => $this->faker()->randomElement(ClientRegistrationHistory::ALLOWED_STATUSES_TYPES),
        ]);

        $this->travel(1)->day();

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $this->assertOnlyPushedOn(
            'create-a2p-sms-campaign-use-case-job',
            CreateA2PSmsCampaignUseCase::class,
            function (CreateA2PSmsCampaignUseCase $job) use ($entity) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService;
            }
        );
    }

    /**
     * @depends test_command_should_dispatch_a_submit_customer_profile_bundle_job_when_there_is_history
     */
    public function test_command_should_dispatch_a_job_for_a_desired_entity_using_a_custom_extra_query(): void
    {
        Queue::fake();

        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(
            array_merge(self::ENTITY_DATA, ['company_name' => $name = 'desired name'])
        );

        $entity::$customQuery = function (Builder $query) use ($name) {
            return $query->where('company_name', $name);
        };

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $this->assertOnlyPushedOn(
            'submit-customer-profile-bundle',
            CreateEmptyCustomerProfileStarterBundle::class,
            function (CreateEmptyCustomerProfileStarterBundle $job) use ($entity) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService;
            }
        );
    }

    /**
     * @depends test_command_has_correct_data
     * @dataProvider clientRegistrationHistoriesNotAllowedStatusesProvider
     */
    public function test_command_should_not_dispatch_any_job_when_the_status_is_not_one_of_the_allowed_ones(
        string $status
    ): void {
        Queue::fake();

        /** @var Entity $entity */
        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => factory(Entity::class)->create(self::ENTITY_DATA),
            'status' => $status,
        ]);

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    /**
     * @depends test_command_should_not_dispatch_any_job_when_the_status_is_not_one_of_the_allowed_ones
     */
    public function test_the_current_loop_is_skipped_when_an_exception_is_thrown(): void
    {
        Queue::fake();

        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(self::ENTITY_DATA);

        // Faking empty property, so it throws an exception when used on the ClientData entity
        factory(Entity::class)->create(
            array_merge(self::ENTITY_DATA, ['contact_first_name' => null])
        );

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $this->assertOnlyPushedOn(
            'submit-customer-profile-bundle',
            CreateEmptyCustomerProfileStarterBundle::class,
            function (CreateEmptyCustomerProfileStarterBundle $job) use ($entity) {
                return $job->client == $entity->getClientData() &&
                       $job->registerService == $this->registerService;
            }
        );
    }

    /**
     * @depends test_the_current_loop_is_skipped_when_an_exception_is_thrown
     */
    public function test_command_should_not_crash_when_unknown_request_type_is_used(): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create(self::ENTITY_DATA);

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => $this->faker()->word,
        ]);

        Queue::fake();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        Queue::assertNothingPushed();
    }

    private function assertOnlyPushedOn(string $queue, string $job, callable $callable): void
    {
        Queue::assertPushed($job, 1);

        Queue::assertPushedOn($queue, $job, $callable);

        // List all jobs and remove the only receiving job
        collect([
            CreateEmptyCustomerProfileStarterBundle::class,
            CreateEndUserCustomerProfileInfo::class,
            CreateCustomerProfileAddress::class,
            CreateCustomerSupportDocs::class,
            AttachObjectSidToCustomerProfile::class,
            EvaluateCustomerProfileBundle::class,
            SubmitCustomerProfileBundle::class,
            CreateEmptyA2PStarterTrustBundle::class,
            AssignCustomerProfileA2PTrustBundle::class,
            EvaluateA2PStarterProfileBundle::class,
            SubmitA2PProfileBundle::class,
            CreateA2PBrand::class,
            CreateMessagingService::class,
            AddPhoneNumberToMessagingService::class,
            CreateA2PSmsCampaignUseCase::class,
        ])
            ->filter(fn (string $class) => $class !== $job)
            ->each(function (string $job) {
                Queue::assertNotPushed($job);
            });
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
