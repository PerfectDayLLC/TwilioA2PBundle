<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand\Starter;

use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use Twilio\Exceptions\TwilioException;

class CreateEmptyA2PStarterTrustBundle extends AbstractMainJob
{
    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        $this->registerService->createEmptyA2PStarterTrustBundle($this->client);
    }
}
