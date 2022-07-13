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
    public function test_should_process_pending_brand_status(): void
    {
        /** @var Entity $expectedEntity */
        $expectedEntity = factory(Entity::class)->create();

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $expectedEntity,
            'request_type' => 'createA2PBrand',
            'status' => 'pending',
        ]);

        $spy = EntityRegistrator::spy();

        $this->artisan(CheckBrandStatus::class)
            ->assertExitCode(0);

        $spy->shouldHaveReceived('checkBrandRegistrationStatus')
            ->once()
            ->with(
                Mockery::on(fn (Entity $actualEntity) => $actualEntity->is($expectedEntity))
            );
    }

    /**
     * @depends test_should_process_pending_brand_status
     * @dataProvider allHistoryRequestTypesExceptCreateBrandProvider
     */
    public function test_should_not_process_any_entity_whose_history_is_not_pending_brand(?string $requestType): void
    {
        if ($requestType) {
            $this->createRealClientRegistrationHistoryModel(['request_type' => $requestType]);
        }

        $spy = EntityRegistrator::spy();

        $this->artisan(CheckBrandStatus::class)
            ->assertExitCode(0);

        $spy->shouldNotHaveReceived('checkBrandRegistrationStatus');
    }

    public function allHistoryRequestTypesExceptCreateBrandProvider(): array
    {
        return [
            'No history' => [null],
            'Create Empty Customer Profile Starter Bundle' => ['createEmptyCustomerProfileStarterBundle'],
            'Create End User Customer Profile Info' => ['createEndUserCustomerProfileInfo'],
            'Create Customer Profile Address' => ['createCustomerProfileAddress'],
            'Create Customer Support Docs' => ['createCustomerSupportDocs'],
            'Attach Object Sid To Customer Profile' => ['attachObjectSidToCustomerProfile'],
            'Evaluate Customer Profile Bundle' => ['evaluateCustomerProfileBundle'],
            'Submit Customer Profile Bundle' => ['submitCustomerProfileBundle'],
            'Create Empty A2P Starter Trust Bundle' => ['createEmptyA2PStarterTrustBundle'],
            'Assign Customer Profile A2P Trust Bundle' => ['assignCustomerProfileA2PTrustBundle'],
            'Evaluate A2P Starter Profile Bundle' => ['evaluateA2PStarterProfileBundle'],
            'Submit A2P Profile Bundle' => ['submitA2PProfileBundle'],
            'Create Messaging Service' => ['createMessagingService'],
            'Add Phone Number To Messaging Service' => ['addPhoneNumberToMessagingService'],
        ];
    }
}
