<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\MessagingService;

use PerfectDayLlc\TwilioA2PBundle\Facades\Registrator as RegistratorFacade;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use Twilio\Exceptions\TwilioException;

class CreateMessagingService extends AbstractMainJob
{
    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        RegistratorFacade::createMessagingService($this->client);
    }
}
