<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Unit\Entity;

use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;

class StatusTest extends TestCase
{
    public function test_statuses_constants_are_the_expected_ones(): void
    {
        $this->assertEquals('compliant', Status::BUNDLES_COMPLIANT);
        $this->assertEquals('noncompliant', Status::BUNDLES_NONCOMPLIANT);
        $this->assertEquals('draft', Status::BUNDLES_DRAFT);
        $this->assertEquals('pending-review', Status::BUNDLES_PENDING_REVIEW);
        $this->assertEquals('in-review', Status::BUNDLES_IN_REVIEW);
        $this->assertEquals('twilio-rejected', Status::BUNDLES_TWILIO_REJECTED);
        $this->assertEquals('twilio-approved', Status::BUNDLES_TWILIO_APPROVED);

        $this->assertEquals('pending', Status::BRAND_PENDING);
        $this->assertEquals('approved', Status::BRAND_APPROVED);
        $this->assertEquals('failed', Status::BRAND_FAILED);
        $this->assertEquals('in_progress', Status::BRAND_IN_PROGRESS);

        $this->assertEquals('exception-error', Status::EXCEPTION_ERROR);
        $this->assertEquals('executed', Status::EXECUTED);
    }

    public function test_expected_constant_is_returned(): void
    {
        $this->assertEmpty(
            array_diff(
                Status::getOngoingA2PStatuses(),
                [
                    Status::BUNDLES_DRAFT,
                    Status::BUNDLES_PENDING_REVIEW,
                    Status::BUNDLES_IN_REVIEW,
                    Status::BUNDLES_TWILIO_APPROVED,
                    Status::BRAND_PENDING,
                    Status::EXECUTED,
                ]
            )
        );
    }
}
