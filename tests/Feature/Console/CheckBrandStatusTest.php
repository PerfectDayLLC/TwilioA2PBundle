<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Feature\Console;

use Mockery;
use PerfectDayLlc\TwilioA2PBundle\Console\CheckBrandStatus;
use PerfectDayLlc\TwilioA2PBundle\Facades\EntityRegistrator;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;

class CheckBrandStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setConfigForService();
    }

    public function test_command_is_correctly_setup(): void
    {
        $command = new CheckBrandStatus;

        $this->assertSame('a2p:check-brand-status', $command->getName(), 'Wrong signature.');

        $definition = new InputDefinition;
        $definition->addArgument(new InputArgument('entity', InputArgument::OPTIONAL));

        $this->assertEquals($definition, $command->getDefinition());

        $this->assertSame(
            'Twilio - Check Brand Registration Status',
            $command->getDescription(),
            'Wrong description.'
        );
    }

    /**
     * @depends test_command_is_correctly_setup
     */
    public function test_should_process_history_request_type_create_a2p_brand_with_pending_status(): void
    {
        /** @var Entity $expectedEntity */
        $expectedEntity = factory(Entity::class)->create();

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $expectedEntity,
            'request_type' => 'createA2PBrand',
            'status' => 'pending',
        ]);

        $spy = EntityRegistrator::spy();

        $this->artisan(CheckBrandStatus::class)->assertExitCode(0);

        $spy->shouldHaveReceived('checkBrandRegistrationStatus')->once()->with(
                Mockery::on(fn (Entity $actualEntity) => $actualEntity->is($expectedEntity))
            );
    }

    /**
     * @depends test_should_process_history_request_type_create_a2p_brand_with_pending_status
     * @dataProvider allHistoryRequestTypesExceptCreateBrandProvider
     */
    public function test_should_not_process_any_entity_whose_history_request_type_is_create_a2p_brand_and_status_pending(
        ?string $requestType,
        ?string $status
    ): void {
        if ($requestType) {
            $this->createRealClientRegistrationHistoryModel(['request_type' => $requestType, 'status' => $status]);
        }

        $spy = EntityRegistrator::spy();

        $this->artisan(CheckBrandStatus::class)
            ->assertExitCode(0);

        $spy->shouldNotHaveReceived('checkBrandRegistrationStatus');
    }

    public function allHistoryRequestTypesExceptCreateBrandProvider(): array
    {
        return [
            'No history' => [null, null],

            'Create Empty Customer Profile Starter Bundle - Compliant' => [
                'createEmptyCustomerProfileStarterBundle',
                'compliant',
            ],
            'Create Empty Customer Profile Starter Bundle - Non Compliant' => [
                'createEmptyCustomerProfileStarterBundle',
                'noncompliant',
            ],
            'Create Empty Customer Profile Starter Bundle - Draft' => [
                'createEmptyCustomerProfileStarterBundle',
                'draft',
            ],
            'Create Empty Customer Profile Starter Bundle - Pending Review' => [
                'createEmptyCustomerProfileStarterBundle',
                'pending-review',
            ],
            'Create Empty Customer Profile Starter Bundle - In Review' => [
                'createEmptyCustomerProfileStarterBundle',
                'in-review',
            ],
            'Create Empty Customer Profile Starter Bundle - Twilio Approved' => [
                'createEmptyCustomerProfileStarterBundle',
                'twilio-approved',
            ],
            'Create Empty Customer Profile Starter Bundle - Twilio Rejected' => [
                'createEmptyCustomerProfileStarterBundle',
                'twilio-rejected',
            ],
            'Create Empty Customer Profile Starter Bundle - Pending' => [
                'createEmptyCustomerProfileStarterBundle',
                'pending',
            ],
            'Create Empty Customer Profile Starter Bundle - Approved' => [
                'createEmptyCustomerProfileStarterBundle',
                'approved',
            ],
            'Create Empty Customer Profile Starter Bundle - Failed' => [
                'createEmptyCustomerProfileStarterBundle',
                'failed',
            ],
            'Create Empty Customer Profile Starter Bundle - In Progress' => [
                'createEmptyCustomerProfileStarterBundle',
                'in_progress',
            ],
            'Create Empty Customer Profile Starter Bundle - Exception Error' => [
                'createEmptyCustomerProfileStarterBundle',
                'exception-error',
            ],
            'Create Empty Customer Profile Starter Bundle - Executed' => [
                'createEmptyCustomerProfileStarterBundle',
                'executed',
            ],

            'Create End User Customer Profile Info - Compliant' => ['createEndUserCustomerProfileInfo', 'compliant'],
            'Create End User Customer Profile Info - Non Compliant' => [
                'createEndUserCustomerProfileInfo',
                'noncompliant',
            ],
            'Create End User Customer Profile Info - Draft' => ['createEndUserCustomerProfileInfo', 'draft'],
            'Create End User Customer Profile Info - Pending Review' => [
                'createEndUserCustomerProfileInfo',
                'pending-review',
            ],
            'Create End User Customer Profile Info - In Review' => ['createEndUserCustomerProfileInfo', 'in-review'],
            'Create End User Customer Profile Info - Twilio Approved' => [
                'createEndUserCustomerProfileInfo',
                'twilio-approved',
            ],
            'Create End User Customer Profile Info - Twilio Rejected' => [
                'createEndUserCustomerProfileInfo',
                'twilio-rejected',
            ],
            'Create End User Customer Profile Info - Pending' => ['createEndUserCustomerProfileInfo', 'pending'],
            'Create End User Customer Profile Info - Approved' => ['createEndUserCustomerProfileInfo', 'approved'],
            'Create End User Customer Profile Info - Failed' => ['createEndUserCustomerProfileInfo', 'failed'],
            'Create End User Customer Profile Info - In Progress' => [
                'createEndUserCustomerProfileInfo',
                'in_progress',
            ],
            'Create End User Customer Profile Info - Exception Error' => [
                'createEndUserCustomerProfileInfo',
                'exception-error',
            ],
            'Create End User Customer Profile Info - Executed' => ['createEndUserCustomerProfileInfo', 'executed'],

            'Create Customer Profile Address - Compliant' => ['createCustomerProfileAddress', 'compliant'],
            'Create Customer Profile Address - Non Compliant' => ['createCustomerProfileAddress', 'noncompliant'],
            'Create Customer Profile Address - Draft' => ['createCustomerProfileAddress', 'draft'],
            'Create Customer Profile Address - Pending Review' => ['createCustomerProfileAddress', 'pending-review'],
            'Create Customer Profile Address - In Review' => ['createCustomerProfileAddress', 'in-review'],
            'Create Customer Profile Address - Twilio Approved' => ['createCustomerProfileAddress', 'twilio-approved'],
            'Create Customer Profile Address - Twilio Rejected' => ['createCustomerProfileAddress', 'twilio-rejected'],
            'Create Customer Profile Address - Pending' => ['createCustomerProfileAddress', 'pending'],
            'Create Customer Profile Address - Approved' => ['createCustomerProfileAddress', 'approved'],
            'Create Customer Profile Address - Failed' => ['createCustomerProfileAddress', 'failed'],
            'Create Customer Profile Address - In Progress' => ['createCustomerProfileAddress', 'in_progress'],
            'Create Customer Profile Address - Exception Error' => ['createCustomerProfileAddress', 'exception-error'],
            'Create Customer Profile Address - Executed' => ['createCustomerProfileAddress', 'executed'],

            'Create Customer Support Docs - Compliant' => ['createCustomerSupportDocs', 'compliant'],
            'Create Customer Support Docs - Non Compliant' => ['createCustomerSupportDocs', 'noncompliant'],
            'Create Customer Support Docs - Draft' => ['createCustomerSupportDocs', 'draft'],
            'Create Customer Support Docs - Pending Review' => ['createCustomerSupportDocs', 'pending-review'],
            'Create Customer Support Docs - In Review' => ['createCustomerSupportDocs', 'in-review'],
            'Create Customer Support Docs - Twilio Approved' => ['createCustomerSupportDocs', 'twilio-approved'],
            'Create Customer Support Docs - Twilio Rejected' => ['createCustomerSupportDocs', 'twilio-rejected'],
            'Create Customer Support Docs - Pending' => ['createCustomerSupportDocs', 'pending'],
            'Create Customer Support Docs - Approved' => ['createCustomerSupportDocs', 'approved'],
            'Create Customer Support Docs - Failed' => ['createCustomerSupportDocs', 'failed'],
            'Create Customer Support Docs - In Progress' => ['createCustomerSupportDocs', 'in_progress'],
            'Create Customer Support Docs - Exception Error' => ['createCustomerSupportDocs', 'exception-error'],
            'Create Customer Support Docs - Executed' => ['createCustomerSupportDocs', 'executed'],

            'Attach Object Sid To Customer Profile - Compliant' => ['attachObjectSidToCustomerProfile', 'compliant'],
            'Attach Object Sid To Customer Profile - Non Compliant' => [
                'attachObjectSidToCustomerProfile',
                'noncompliant',
            ],
            'Attach Object Sid To Customer Profile - Draft' => ['attachObjectSidToCustomerProfile', 'draft'],
            'Attach Object Sid To Customer Profile - Pending Review' => [
                'attachObjectSidToCustomerProfile',
                'pending-review',
            ],
            'Attach Object Sid To Customer Profile - In Review' => ['attachObjectSidToCustomerProfile', 'in-review'],
            'Attach Object Sid To Customer Profile - Twilio Approved' => [
                'attachObjectSidToCustomerProfile',
                'twilio-approved',
            ],
            'Attach Object Sid To Customer Profile - Twilio Rejected' => [
                'attachObjectSidToCustomerProfile',
                'twilio-rejected',
            ],
            'Attach Object Sid To Customer Profile - Pending' => ['attachObjectSidToCustomerProfile', 'pending'],
            'Attach Object Sid To Customer Profile - Approved' => ['attachObjectSidToCustomerProfile', 'approved'],
            'Attach Object Sid To Customer Profile - Failed' => ['attachObjectSidToCustomerProfile', 'failed'],
            'Attach Object Sid To Customer Profile - In Progress' => [
                'attachObjectSidToCustomerProfile',
                'in_progress',
            ],
            'Attach Object Sid To Customer Profile - Exception Error' => [
                'attachObjectSidToCustomerProfile',
                'exception-error',
            ],
            'Attach Object Sid To Customer Profile - Executed' => ['attachObjectSidToCustomerProfile', 'executed'],

            'Evaluate Customer Profile Bundle - Compliant' => ['evaluateCustomerProfileBundle', 'compliant'],
            'Evaluate Customer Profile Bundle - Non Compliant' => ['evaluateCustomerProfileBundle', 'noncompliant'],
            'Evaluate Customer Profile Bundle - Draft' => ['evaluateCustomerProfileBundle', 'draft'],
            'Evaluate Customer Profile Bundle - Pending Review' => ['evaluateCustomerProfileBundle', 'pending-review'],
            'Evaluate Customer Profile Bundle - In Review' => ['evaluateCustomerProfileBundle', 'in-review'],
            'Evaluate Customer Profile Bundle - Twilio Approved' => [
                'evaluateCustomerProfileBundle',
                'twilio-approved',
            ],
            'Evaluate Customer Profile Bundle - Twilio Rejected' => [
                'evaluateCustomerProfileBundle',
                'twilio-rejected',
            ],
            'Evaluate Customer Profile Bundle - Pending' => ['evaluateCustomerProfileBundle', 'pending'],
            'Evaluate Customer Profile Bundle - Approved' => ['evaluateCustomerProfileBundle', 'approved'],
            'Evaluate Customer Profile Bundle - Failed' => ['evaluateCustomerProfileBundle', 'failed'],
            'Evaluate Customer Profile Bundle - In Progress' => ['evaluateCustomerProfileBundle', 'in_progress'],
            'Evaluate Customer Profile Bundle - Exception Error' => [
                'evaluateCustomerProfileBundle',
                'exception-error',
            ],
            'Evaluate Customer Profile Bundle - Executed' => ['evaluateCustomerProfileBundle', 'executed'],

            'Submit Customer Profile Bundle - Compliant' => ['submitCustomerProfileBundle', 'compliant'],
            'Submit Customer Profile Bundle - Non Compliant' => ['submitCustomerProfileBundle', 'noncompliant'],
            'Submit Customer Profile Bundle - Draft' => ['submitCustomerProfileBundle', 'draft'],
            'Submit Customer Profile Bundle - Pending Review' => ['submitCustomerProfileBundle', 'pending-review'],
            'Submit Customer Profile Bundle - In Review' => ['submitCustomerProfileBundle', 'in-review'],
            'Submit Customer Profile Bundle - Twilio Approved' => ['submitCustomerProfileBundle', 'twilio-approved'],
            'Submit Customer Profile Bundle - Twilio Rejected' => ['submitCustomerProfileBundle', 'twilio-rejected'],
            'Submit Customer Profile Bundle - Pending' => ['submitCustomerProfileBundle', 'pending'],
            'Submit Customer Profile Bundle - Approved' => ['submitCustomerProfileBundle', 'approved'],
            'Submit Customer Profile Bundle - Failed' => ['submitCustomerProfileBundle', 'failed'],
            'Submit Customer Profile Bundle - In Progress' => ['submitCustomerProfileBundle', 'in_progress'],
            'Submit Customer Profile Bundle - Exception Error' => ['submitCustomerProfileBundle', 'exception-error'],
            'Submit Customer Profile Bundle - Executed' => ['submitCustomerProfileBundle', 'executed'],

            'Create Empty A2P Starter Trust Bundle - Compliant' => ['createEmptyA2PStarterTrustBundle', 'compliant'],
            'Create Empty A2P Starter Trust Bundle - Non Compliant' => [
                'createEmptyA2PStarterTrustBundle',
                'noncompliant',
            ],
            'Create Empty A2P Starter Trust Bundle - Draft' => ['createEmptyA2PStarterTrustBundle', 'draft'],
            'Create Empty A2P Starter Trust Bundle - Pending Review' => [
                'createEmptyA2PStarterTrustBundle',
                'pending-review',
            ],
            'Create Empty A2P Starter Trust Bundle - In Review' => ['createEmptyA2PStarterTrustBundle', 'in-review'],
            'Create Empty A2P Starter Trust Bundle - Twilio Approved' => [
                'createEmptyA2PStarterTrustBundle',
                'twilio-approved',
            ],
            'Create Empty A2P Starter Trust Bundle - Twilio Rejected' => [
                'createEmptyA2PStarterTrustBundle',
                'twilio-rejected',
            ],
            'Create Empty A2P Starter Trust Bundle - Pending' => ['createEmptyA2PStarterTrustBundle', 'pending'],
            'Create Empty A2P Starter Trust Bundle - Approved' => ['createEmptyA2PStarterTrustBundle', 'approved'],
            'Create Empty A2P Starter Trust Bundle - Failed' => ['createEmptyA2PStarterTrustBundle', 'failed'],
            'Create Empty A2P Starter Trust Bundle - In Progress' => [
                'createEmptyA2PStarterTrustBundle',
                'in_progress',
            ],
            'Create Empty A2P Starter Trust Bundle - Exception Error' => [
                'createEmptyA2PStarterTrustBundle',
                'exception-error',
            ],
            'Create Empty A2P Starter Trust Bundle - Executed' => ['createEmptyA2PStarterTrustBundle', 'executed'],

            'Assign Customer Profile A2P Trust Bundle - Compliant' => [
                'assignCustomerProfileA2PTrustBundle',
                'compliant',
            ],
            'Assign Customer Profile A2P Trust Bundle - Non Compliant' => [
                'assignCustomerProfileA2PTrustBundle',
                'noncompliant',
            ],
            'Assign Customer Profile A2P Trust Bundle - Draft' => ['assignCustomerProfileA2PTrustBundle', 'draft'],
            'Assign Customer Profile A2P Trust Bundle - Pending Review' => [
                'assignCustomerProfileA2PTrustBundle',
                'pending-review',
            ],
            'Assign Customer Profile A2P Trust Bundle - In Review' => [
                'assignCustomerProfileA2PTrustBundle',
                'in-review',
            ],
            'Assign Customer Profile A2P Trust Bundle - Twilio Approved' => [
                'assignCustomerProfileA2PTrustBundle',
                'twilio-approved',
            ],
            'Assign Customer Profile A2P Trust Bundle - Twilio Rejected' => [
                'assignCustomerProfileA2PTrustBundle',
                'twilio-rejected',
            ],
            'Assign Customer Profile A2P Trust Bundle - Pending' => ['assignCustomerProfileA2PTrustBundle', 'pending'],
            'Assign Customer Profile A2P Trust Bundle - Approved' => [
                'assignCustomerProfileA2PTrustBundle',
                'approved',
            ],
            'Assign Customer Profile A2P Trust Bundle - Failed' => ['assignCustomerProfileA2PTrustBundle', 'failed'],
            'Assign Customer Profile A2P Trust Bundle - In Progress' => [
                'assignCustomerProfileA2PTrustBundle',
                'in_progress',
            ],
            'Assign Customer Profile A2P Trust Bundle - Exception Error' => [
                'assignCustomerProfileA2PTrustBundle',
                'exception-error',
            ],
            'Assign Customer Profile A2P Trust Bundle - Executed' => [
                'assignCustomerProfileA2PTrustBundle',
                'executed',
            ],

            'Evaluate A2P Starter Profile Bundle - Compliant' => ['evaluateA2PStarterProfileBundle', 'compliant'],
            'Evaluate A2P Starter Profile Bundle - Non Compliant' => [
                'evaluateA2PStarterProfileBundle',
                'noncompliant',
            ],
            'Evaluate A2P Starter Profile Bundle - Draft' => ['evaluateA2PStarterProfileBundle', 'draft'],
            'Evaluate A2P Starter Profile Bundle - Pending Review' => [
                'evaluateA2PStarterProfileBundle',
                'pending-review',
            ],
            'Evaluate A2P Starter Profile Bundle - In Review' => ['evaluateA2PStarterProfileBundle', 'in-review'],
            'Evaluate A2P Starter Profile Bundle - Twilio Approved' => [
                'evaluateA2PStarterProfileBundle',
                'twilio-approved',
            ],
            'Evaluate A2P Starter Profile Bundle - Twilio Rejected' => [
                'evaluateA2PStarterProfileBundle',
                'twilio-rejected',
            ],
            'Evaluate A2P Starter Profile Bundle - Pending' => ['evaluateA2PStarterProfileBundle', 'pending'],
            'Evaluate A2P Starter Profile Bundle - Approved' => ['evaluateA2PStarterProfileBundle', 'approved'],
            'Evaluate A2P Starter Profile Bundle - Failed' => ['evaluateA2PStarterProfileBundle', 'failed'],
            'Evaluate A2P Starter Profile Bundle - In Progress' => ['evaluateA2PStarterProfileBundle', 'in_progress'],
            'Evaluate A2P Starter Profile Bundle - Exception Error' => [
                'evaluateA2PStarterProfileBundle',
                'exception-error',
            ],
            'Evaluate A2P Starter Profile Bundle - Executed' => ['evaluateA2PStarterProfileBundle', 'executed'],

            'Submit A2P Profile Bundle - Compliant' => ['submitA2PProfileBundle', 'compliant'],
            'Submit A2P Profile Bundle - Non Compliant' => ['submitA2PProfileBundle', 'noncompliant'],
            'Submit A2P Profile Bundle - Draft' => ['submitA2PProfileBundle', 'draft'],
            'Submit A2P Profile Bundle - Pending Review' => ['submitA2PProfileBundle', 'pending-review'],
            'Submit A2P Profile Bundle - In Review' => ['submitA2PProfileBundle', 'in-review'],
            'Submit A2P Profile Bundle - Twilio Approved' => ['submitA2PProfileBundle', 'twilio-approved'],
            'Submit A2P Profile Bundle - Twilio Rejected' => ['submitA2PProfileBundle', 'twilio-rejected'],
            'Submit A2P Profile Bundle - Pending' => ['submitA2PProfileBundle', 'pending'],
            'Submit A2P Profile Bundle - Approved' => ['submitA2PProfileBundle', 'approved'],
            'Submit A2P Profile Bundle - Failed' => ['submitA2PProfileBundle', 'failed'],
            'Submit A2P Profile Bundle - In Progress' => ['submitA2PProfileBundle', 'in_progress'],
            'Submit A2P Profile Bundle - Exception Error' => ['submitA2PProfileBundle', 'exception-error'],
            'Submit A2P Profile Bundle - Executed' => ['submitA2PProfileBundle', 'executed'],

            'Create A 2 Pbrand - Compliant' => ['createA2PBrand', 'compliant'],
            'Create A 2 Pbrand - Non Compliant' => ['createA2PBrand', 'noncompliant'],
            'Create A 2 Pbrand - Draft' => ['createA2PBrand', 'draft'],
            'Create A 2 Pbrand - Pending Review' => ['createA2PBrand', 'pending-review'],
            'Create A 2 Pbrand - In Review' => ['createA2PBrand', 'in-review'],
            'Create A 2 Pbrand - Twilio Approved' => ['createA2PBrand', 'twilio-approved'],
            'Create A 2 Pbrand - Twilio Rejected' => ['createA2PBrand', 'twilio-rejected'],
            'Create A 2 Pbrand - Approved' => ['createA2PBrand', 'approved'],
            'Create A 2 Pbrand - Failed' => ['createA2PBrand', 'failed'],
            'Create A 2 Pbrand - In Progress' => ['createA2PBrand', 'in_progress'],
            'Create A 2 Pbrand - Exception Error' => ['createA2PBrand', 'exception-error'],
            'Create A 2 Pbrand - Executed' => ['createA2PBrand', 'executed'],

            'Create Messaging Service - Compliant' => ['createMessagingService', 'compliant'],
            'Create Messaging Service - Non Compliant' => ['createMessagingService', 'noncompliant'],
            'Create Messaging Service - Draft' => ['createMessagingService', 'draft'],
            'Create Messaging Service - Pending Review' => ['createMessagingService', 'pending-review'],
            'Create Messaging Service - In Review' => ['createMessagingService', 'in-review'],
            'Create Messaging Service - Twilio Approved' => ['createMessagingService', 'twilio-approved'],
            'Create Messaging Service - Twilio Rejected' => ['createMessagingService', 'twilio-rejected'],
            'Create Messaging Service - Pending' => ['createMessagingService', 'pending'],
            'Create Messaging Service - Approved' => ['createMessagingService', 'approved'],
            'Create Messaging Service - Failed' => ['createMessagingService', 'failed'],
            'Create Messaging Service - In Progress' => ['createMessagingService', 'in_progress'],
            'Create Messaging Service - Exception Error' => ['createMessagingService', 'exception-error'],
            'Create Messaging Service - Executed' => ['createMessagingService', 'executed'],

            'Add Phone Number To Messaging Service - Compliant' => ['addPhoneNumberToMessagingService', 'compliant'],
            'Add Phone Number To Messaging Service - Non Compliant' => [
                'addPhoneNumberToMessagingService',
                'noncompliant',
            ],
            'Add Phone Number To Messaging Service - Draft' => ['addPhoneNumberToMessagingService', 'draft'],
            'Add Phone Number To Messaging Service - Pending Review' => [
                'addPhoneNumberToMessagingService',
                'pending-review',
            ],
            'Add Phone Number To Messaging Service - In Review' => ['addPhoneNumberToMessagingService', 'in-review'],
            'Add Phone Number To Messaging Service - Twilio Approved' => [
                'addPhoneNumberToMessagingService',
                'twilio-approved',
            ],
            'Add Phone Number To Messaging Service - Twilio Rejected' => [
                'addPhoneNumberToMessagingService',
                'twilio-rejected',
            ],
            'Add Phone Number To Messaging Service - Pending' => ['addPhoneNumberToMessagingService', 'pending'],
            'Add Phone Number To Messaging Service - Approved' => ['addPhoneNumberToMessagingService', 'approved'],
            'Add Phone Number To Messaging Service - Failed' => ['addPhoneNumberToMessagingService', 'failed'],
            'Add Phone Number To Messaging Service - In Progress' => [
                'addPhoneNumberToMessagingService',
                'in_progress',
            ],
            'Add Phone Number To Messaging Service - Exception Error' => [
                'addPhoneNumberToMessagingService',
                'exception-error',
            ],
            'Add Phone Number To Messaging Service - Executed' => ['addPhoneNumberToMessagingService', 'executed'],
        ];
    }
}
