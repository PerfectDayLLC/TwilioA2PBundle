<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs;

use Twilio\Exceptions\TwilioException;

class CreateMessagingService extends AbstractMainJob
{
    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        $this->registerService->createMessageServiceWithPhoneNumber($this->client);
    }
}
