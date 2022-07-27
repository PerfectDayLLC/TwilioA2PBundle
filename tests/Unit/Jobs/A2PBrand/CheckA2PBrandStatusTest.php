<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Unit\Jobs\A2PBrand;

use PerfectDayLlc\TwilioA2PBundle\Facades\Registrator as RegistratorFacade;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand\CheckA2PBrandStatus;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;
use Twilio\Rest\Messaging\V1\BrandRegistrationInstance;

class CheckA2PBrandStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setConfigForService();
    }

    /**
     * @dataProvider allStatusesExceptPendingProvider
     */
    public function test_should_process_pending_brand_status(string $status): void
    {
        /** @var Entity $entity */
        $entity = factory(Entity::class)->create();

        $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'submitA2PProfileBundle',
        ]);

        $this->travel(1)->second();

        /** @var ClientRegistrationHistory $history */
        $history = $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'createA2PBrand',
            'status' => 'pending',
        ]);

        $this->travel(1)->second();

        /** @var ClientRegistrationHistory $nonUpdatedHistory */
        $nonUpdatedHistory = $this->createRealClientRegistrationHistoryModel([
            'entity_id' => $entity,
            'request_type' => 'createA2PBrand',
            'status' => $status,
        ])
            ->refresh();

        RegistratorFacade::shouldReceive('checkA2PBrandStatus')
            ->once()
            ->andReturn(
                // TRIED TON OF DIFFERENT MOCK APPROACHES, ONLY THIS ONE WORKS WITHOUT OVER ENGINEERING
                new class extends BrandRegistrationInstance
                {
                    public function __construct()
                    {
                    }

                    public string $status = 'APPROVED';
                }
            );

        (new CheckA2PBrandStatus($entity->getClientData()))
            ->handle();

        $this->assertSame('approved', $history->fresh()->status);
        $this->assertEquals($nonUpdatedHistory, $nonUpdatedHistory->fresh());
    }

    public function allStatusesExceptPendingProvider(): array
    {
        return [
            'Compliant' => ['compliant'],
            'Non Compliant' => ['noncompliant'],
            'Draft' => ['draft'],
            'Pending Review' => ['pending-review'],
            'In Review' => ['in-review'],
            'Twilio Approved' => ['twilio-approved'],
            'Twilio Rejected' => ['twilio-rejected'],
            'Approved' => ['approved'],
            'Failed' => ['failed'],
            'In Progress' => ['in_progress'],
            'Exception Error' => ['exception-error'],
            'Executed' => ['executed'],
        ];
    }
}
