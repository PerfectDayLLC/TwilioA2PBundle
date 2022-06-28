<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\MessagingService;

use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use Twilio\Exceptions\TwilioException;

class CreateMessagingService extends AbstractMainJob
{
    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        $this->registerService->createMessagingService($this->client);
    }
}
