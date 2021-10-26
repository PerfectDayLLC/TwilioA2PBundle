<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs;

use Twilio\Exceptions\TwilioException;

class SubmitCustomerProfileBundle extends AbstractMainJob
{
    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        $this->registerService->createAndSubmitCustomerProfile($this->client);
    }
}
