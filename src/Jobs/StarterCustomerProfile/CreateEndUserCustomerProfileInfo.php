<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile;

use PerfectDayLlc\TwilioA2PBundle\Facades\Registrator as RegistratorFacade;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use Twilio\Exceptions\TwilioException;

class CreateEndUserCustomerProfileInfo extends AbstractMainJob
{
    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        RegistratorFacade::createEndUserCustomerProfileInfo($this->client);
    }
}
