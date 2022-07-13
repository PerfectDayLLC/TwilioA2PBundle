<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand\Starter;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Facades\Registrator as RegistratorFacade;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use Twilio\Exceptions\TwilioException;

class SubmitA2PProfileBundle extends AbstractMainJob
{
    public string $trustProductsInstanceStatus;

    public string $trustProductsInstanceSid;

    public function __construct(ClientData $client)
    {
        parent::__construct($client);

        $this->trustProductsInstanceSid = ClientRegistrationHistory::getSidForAllowedStatuses(
            'createEmptyA2PStarterTrustBundle',
            $client->getId()
        );

        $this->trustProductsInstanceStatus = ClientRegistrationHistory::getStatusForAllowedStatuses(
            'evaluateA2PStarterProfileBundle',
            $client->getId()
        );
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        if ($this->trustProductsInstanceStatus !== Status::BUNDLES_COMPLIANT) {
            return;
        }

        RegistratorFacade::submitA2PProfileBundle($this->client, $this->trustProductsInstanceSid);
    }
}
