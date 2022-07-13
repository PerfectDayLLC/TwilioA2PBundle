<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Facades\Registrator as RegistratorFacade;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use Twilio\Exceptions\TwilioException;

class CreateA2PBrand extends AbstractMainJob
{
    public ?string $customerProfileBundleSid;

    public ?string $a2PProfileBundleSid;

    public function __construct(ClientData $client)
    {
        parent::__construct($client);

        $this->a2PProfileBundleSid = ClientRegistrationHistory::getSidForAllowedStatuses(
            'submitA2PProfileBundle',
            $this->client->getId()
        );

        $this->customerProfileBundleSid = ClientRegistrationHistory::getSidForAllowedStatuses(
            'submitCustomerProfileBundle',
            $this->client->getId()
        );
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        RegistratorFacade::createA2PBrand(
            $this->client,
            $this->a2PProfileBundleSid,
            $this->customerProfileBundleSid
        );
    }
}
