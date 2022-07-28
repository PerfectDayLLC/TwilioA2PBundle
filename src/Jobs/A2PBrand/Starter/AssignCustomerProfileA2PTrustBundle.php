<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand\Starter;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Facades\Registrator as RegistratorFacade;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use Twilio\Exceptions\TwilioException;

class AssignCustomerProfileA2PTrustBundle extends AbstractMainJob
{
    public string $customerProfileBundleSid;

    public string $trustProductsInstanceSid;

    public function __construct(ClientData $client)
    {
        parent::__construct($client);

        $this->customerProfileBundleSid = ClientRegistrationHistory::getSidForAllowedStatuses(
            'submitCustomerProfileBundle',
            $client->getId()
        );

        $this->trustProductsInstanceSid = ClientRegistrationHistory::getSidForAllowedStatuses(
            'createEmptyA2PStarterTrustBundle',
            $client->getId()
        );
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        RegistratorFacade::assignCustomerProfileA2PTrustBundle(
            $this->client,
            $this->trustProductsInstanceSid,
            $this->customerProfileBundleSid
        );
    }
}
