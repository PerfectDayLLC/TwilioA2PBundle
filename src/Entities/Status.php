<?php

namespace PerfectDayLlc\TwilioA2PBundle\Entities;

use ReflectionClass;

class Status
{
    // Create Customer and A2P Bundles
    public const BUNDLES_COMPLIANT = 'compliant';
    public const BUNDLES_NONCOMPLIANT = 'noncompliant';
    public const BUNDLES_DRAFT = 'draft';
    public const BUNDLES_PENDING_REVIEW = 'pending-review';
    public const BUNDLES_IN_REVIEW = 'in-review';
    public const BUNDLES_TWILIO_REJECTED = 'twilio-rejected';
    public const BUNDLES_TWILIO_APPROVED = 'twilio-approved';

    // Create an A2P Brand
    public const BRAND_PENDING = 'pending';
    public const BRAND_APPROVED = 'approved';
    public const BRAND_FAILED = 'failed';
    public const BRAND_IN_PROGRESS = 'in_progress';

    // Utility statuses
    public const EXCEPTION_ERROR = 'exception-error';
    public const EXECUTED = 'executed';

    public static function getConstants(): array
    {
        return (new ReflectionClass(static::class))->getConstants();
    }
}
