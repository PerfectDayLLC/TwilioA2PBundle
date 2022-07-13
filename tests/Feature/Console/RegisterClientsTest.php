<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Feature\Console;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Mockery;
use PerfectDayLlc\TwilioA2PBundle\Console\RegisterClients;
use PerfectDayLlc\TwilioA2PBundle\Facades\EntityRegistrator;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;

class RegisterClientsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setConfigForService();
    }

    protected function tearDown(): void
    {
        Entity::$customQuery = null;

        parent::tearDown();
    }

    public function test_command_is_correctly_setup(): void
    {
        $command = new RegisterClients;

        $this->assertSame('a2p:client-register', $command->getName(), 'Wrong signature.');

        $definition = new InputDefinition;
        $definition->addArgument(new InputArgument('entity', InputArgument::OPTIONAL));

        $this->assertEquals($definition, $command->getDefinition());

        $this->assertSame(
            'Twilio - Register all companies for the A2P 10DLC US carrier standard compliance',
            $command->getDescription(),
            'Wrong description.'
        );
    }

    /**
     * @depends test_command_is_correctly_setup
     */
    public function test_it_should_use_the_right_entities_when_they_have_no_history(): void
    {
        /** @var Entity $expectedEntity1 */
        $expectedEntity1 = factory(Entity::class)->create();
        /** @var Entity $expectedEntity2 */
        $expectedEntity2 = factory(Entity::class)->create();

        $spy = EntityRegistrator::spy();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $spy->shouldHaveReceived('processEntity')
            ->twice();

        $spy->shouldHaveReceived('processEntity')
            ->with(
                Mockery::on(fn (Entity $actualEntity) => $actualEntity->is($expectedEntity1)),
            )
            ->once();

        $spy->shouldHaveReceived('processEntity')
            ->with(
                Mockery::on(fn (Entity $actualEntity) => $actualEntity->is($expectedEntity2)),
            )
            ->once();
    }

    /**
     * @depends test_it_should_use_the_right_entities_when_they_have_no_history
     * @dataProvider clientRegistrationHistoriesWithStatusesProvider
     */
    public function test_it_should_use_the_right_entity_when_history_has_expected_status(
        string $status,
        bool $error
    ): void {
        /** @var Entity $expectedEntity */
        $expectedEntity = factory(Entity::class)->create();

        if (! $error) {
            $this->createRealClientRegistrationHistoryModel([
                'entity_id' => $expectedEntity,
                'status' => $status,
                'error' => true,
            ]);

            $this->travel(1)->second();
        }

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $expectedEntity,
            'status' => $status,
            'error' => $error,
        ]);

        $spyRegistrator = EntityRegistrator::spy();
        $spyLogger = Log::spy();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        if (! $error) {
            $spyRegistrator->shouldHaveReceived('processEntity')
                ->once()
                ->with(
                    Mockery::on(fn (Entity $actualEntity) => $actualEntity->is($expectedEntity))
                );
        } else {
            $spyRegistrator->shouldNotHaveReceived('processEntity');
        }

        $spyLogger->shouldNotHaveReceived('error');
    }

    /**
     * @depends test_it_should_use_the_right_entity_when_history_has_expected_status
     */
    public function test_should_not_use_an_entity_when_the_latest_history_has_an_error(): void
    {
        /** @var Entity $ignoredEntity */
        $ignoredEntity = factory(Entity::class)->create();

        $this->createRealClientRegistrationHistoryModel(['entity_id' => $ignoredEntity]);

        $this->travel(1)->second();

        $this->createRealClientRegistrationHistoryModel(['entity_id' => $ignoredEntity, 'error' => true]);

        $spyRegistrator = EntityRegistrator::spy();
        $spyLogger = Log::spy();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $spyRegistrator->shouldNotHaveReceived('processEntity');
        $spyLogger->shouldNotHaveReceived('error');
    }

    /**
     * @depends test_it_should_use_the_right_entities_when_they_have_no_history
     * @testWith ["pending"]
     *           ["in_progress"]
     *           ["verified"]
     */
    public function test_it_should_not_use_a_fully_registered_entity(string $status): void
    {
        /** @var Entity $notExpectedEntity */
        $notExpectedEntity = factory(Entity::class)->create();

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $notExpectedEntity,
            'request_type' => 'createEmptyCustomerProfileStarterBundle',
            'status' => $status,
        ]);

        $this->travel(1)->second();

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $notExpectedEntity,
            'request_type' => 'createA2PMessagingCampaignUseCase',
            'status' => $status,
            'error' => true,
        ]);

        $this->travel(1)->second();

        /** @var Entity $expectedEntity */
        $expectedEntity = factory(Entity::class)->create();

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $expectedEntity,
            'request_type' => 'createEmptyCustomerProfileStarterBundle',
            'status' => $status,
        ]);

        $this->travel(1)->second();

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $expectedEntity,
            'request_type' => 'createA2PMessagingCampaignUseCase',
            'status' => $status,
        ]);

        $spy = EntityRegistrator::spy();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $spy->shouldNotHaveReceived('processEntity');
    }

    /**
     * @depends test_it_should_use_the_right_entities_when_they_have_no_history
     * @testWith [true]
     *           [false]
     */
    public function test_the_right_entity_should_be_get_when_using_a_custom_extra_query(bool $hasHistory): void
    {
        /** @var Entity $expectedEntity */
        $expectedEntity = factory(Entity::class)->create([
            'company_name' => $name = 'desired name',
        ]);

        if ($hasHistory) {
            $this->createRealClientRegistrationHistoryModel(['entity_id' => $expectedEntity->id]);
        }

        $expectedEntity::$customQuery = fn (Builder $query) => $query->where('company_name', $name);

        $spy = EntityRegistrator::spy();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $spy->shouldHaveReceived('processEntity')
            ->once()
            ->with(
                Mockery::on(fn (Entity $entity) => $entity->is($expectedEntity))
            );
    }

    /**
     * @depends test_it_should_use_the_right_entities_when_they_have_no_history
     */
    public function test_passing_an_id_only_runs_that_entity_when_there_is_no_history(): void
    {
        factory(Entity::class)->create();

        /** @var Entity $expectedEntity */
        $expectedEntity = factory(Entity::class)->create();

        $spy = EntityRegistrator::spy();

        $this->artisan(RegisterClients::class, ['entity' => $expectedEntity->getKey()])
            ->assertExitCode(0);

        $spy->shouldHaveReceived('processEntity')
            ->once()
            ->with(
                Mockery::on(fn (Entity $entity) => $entity->is($expectedEntity))
            );
    }

    /**
     * @depends test_passing_an_id_only_runs_that_entity_when_there_is_no_history
     */
    public function test_passing_an_id_only_runs_that_entity_when_there_is_history(): void
    {
        $this->createRealClientRegistrationHistoryModel();

        /** @var Entity $expectedEntity */
        $expectedEntity = factory(Entity::class)->create();
        $this->createRealClientRegistrationHistoryModel(['entity_id' => $expectedEntity]);

        $spy = EntityRegistrator::spy();

        $this->artisan(RegisterClients::class, ['entity' => $expectedEntity->getKey()])
            ->assertExitCode(0);

        $spy->shouldHaveReceived('processEntity')
            ->once()
            ->with(
                Mockery::on(fn (Entity $entity) => $entity->is($expectedEntity))
            );
    }

    /**
     * @depends test_it_should_use_the_right_entities_when_they_have_no_history
     */
    public function test_the_current_loop_is_skipped_when_an_exception_is_thrown(): void
    {
        factory(Entity::class, 2)->create();

        EntityRegistrator::shouldReceive('processEntity')
            ->andReturnUsing(
                function () {
                    throw new Exception('Testing exception');
                },
                fn () => null
            );

        $spyLog = Log::spy();

        $this->artisan(RegisterClients::class)
            ->assertExitCode(0);

        $spyLog->shouldHaveReceived('error')
            ->once()
            ->with('Testing exception');
    }

    public function clientRegistrationHistoriesWithStatusesProvider(): array
    {
        return [
            'Draft' => ['draft', false],
            'Pending Review' => ['pending-review', false],
            'In Review' => ['in-review', false],
            'Twilio Approved' => ['twilio-approved', false],
            'Pending' => ['pending', false],
            'Executed' => ['executed', false],
            'Compliant' => ['compliant', true],
            'Non compliant' => ['noncompliant', true],
            'Twilio Rejected' => ['twilio-rejected', true],
            'Approved' => ['approved', true],
            'Failed' => ['failed', true],
            'In Progress' => ['in_progress', true],
            'Exception Error' => ['exception-error', true],
        ];
    }
}
