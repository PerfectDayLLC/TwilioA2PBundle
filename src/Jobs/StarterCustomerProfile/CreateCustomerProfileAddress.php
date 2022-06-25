<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile;

use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use Twilio\Exceptions\TwilioException;

class CreateCustomerProfileAddress extends AbstractMainJob
{
    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        $this->registerService->createCustomerProfileAddress($this->client);
    }
}
