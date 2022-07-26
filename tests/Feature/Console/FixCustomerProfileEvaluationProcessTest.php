<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Feature\Console;

use Exception;
use Illuminate\Support\Facades\Log;
use Mockery;
use PerfectDayLlc\TwilioA2PBundle\Console\FixCustomerProfileEvaluationProcess;
use PerfectDayLlc\TwilioA2PBundle\Facades\EntityRegistrator;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;

class FixCustomerProfileEvaluationProcessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setConfigForService();
    }

    public function test_command_is_correctly_setup(): void
    {
        $command = new FixCustomerProfileEvaluationProcess;

        $this->assertSame('a2p:fix-customer-profile-evaluation-process', $command->getName(), 'Wrong signature.');

        $definition = new InputDefinition;
        $definition->addArgument(new InputArgument('entity', InputArgument::OPTIONAL));

        $this->assertEquals($definition, $command->getDefinition());

        $this->assertSame(
            'Twilio - Fix the customer profile evaluation process',
            $command->getDescription(),
            'Wrong description.'
        );
    }

    /**
     * @depends test_command_is_correctly_setup
     */
    public function test_should_process_history_request_type_evaluate_customer_profile_bundle_with_pending_status(): void
    {
        /** @var Entity $expectedEntity */
        $expectedEntity = factory(Entity::class)->create();

        $expectedHistory = $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $expectedEntity,
            'request_type' => 'evaluateCustomerProfileBundle',
            'status' => 'noncompliant',
            'error' => 1,
        ]);

        $spy = EntityRegistrator::spy();

        $this->artisan(FixCustomerProfileEvaluationProcess::class)
            ->assertExitCode(0);

        $spy->shouldHaveReceived('fixCustomerProfileForEvaluation')
            ->once()
            ->with(
                Mockery::on(fn (ClientRegistrationHistory $actualHistory) => $actualHistory->is($expectedHistory))
            );
    }

    /**
     * @depends test_should_process_history_request_type_evaluate_customer_profile_bundle_with_pending_status
     */
    public function test_passing_an_id_only_runs_that_entity_when_there_is_no_history(): void
    {
        $this->createRealClientRegistrationHistoryModel([
            'request_type' => 'evaluateCustomerProfileBundle',
            'status' => 'noncompliant',
            'error' => 1,
        ]);

        /** @var Entity $expectedEntity */
        $expectedEntity = factory(Entity::class)->create();

        $expectedHistory = $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $expectedEntity,
            'request_type' => 'evaluateCustomerProfileBundle',
            'status' => 'noncompliant',
            'error' => 1,
        ]);

        $spy = EntityRegistrator::spy();

        $this->artisan(FixCustomerProfileEvaluationProcess::class, ['entity' => $expectedEntity->getKey()])
            ->assertExitCode(0);

        $spy->shouldHaveReceived('fixCustomerProfileForEvaluation')
            ->once()
            ->with(
                Mockery::on(fn (ClientRegistrationHistory $history) => $history->is($expectedHistory))
            );
    }

    /**
     * @depends test_should_process_history_request_type_evaluate_customer_profile_bundle_with_pending_status
     */
    public function test_the_current_loop_is_skipped_when_an_exception_is_thrown(): void
    {
        $this->createRealClientRegistrationHistoryModel([
            'request_type' => 'evaluateCustomerProfileBundle',
            'status' => 'noncompliant',
            'error' => 1,
        ]);

        $this->createRealClientRegistrationHistoryModel([
            'request_type' => 'evaluateCustomerProfileBundle',
            'status' => 'noncompliant',
            'error' => 1,
        ]);

        EntityRegistrator::shouldReceive('fixCustomerProfileForEvaluation')
            ->andReturnUsing(
                function () {
                    throw new Exception('Testing exception');
                },
                fn () => null
            );

        $spyLog = Log::spy();

        $this->artisan(FixCustomerProfileEvaluationProcess::class)
            ->assertExitCode(0);

        $spyLog->shouldHaveReceived('error')
            ->once()
            ->with('Testing exception');
    }
}
