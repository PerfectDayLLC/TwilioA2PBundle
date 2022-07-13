<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand\Starter;

use PerfectDayLlc\TwilioA2PBundle\Facades\Registrator as RegistratorFacades;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use Twilio\Exceptions\TwilioException;

class CreateEmptyA2PStarterTrustBundle extends AbstractMainJob
{
    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        RegistratorFacades::createEmptyA2PStarterTrustBundle($this->client);
    }
}
