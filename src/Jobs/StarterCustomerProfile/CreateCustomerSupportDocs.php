<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Facades\Registrator as RegistratorFacade;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use Twilio\Exceptions\TwilioException;

class CreateCustomerSupportDocs extends AbstractMainJob
{
    private string $addressInstanceSid;

    public function __construct(ClientData $client)
    {
        parent::__construct($client);

        $this->addressInstanceSid = ClientRegistrationHistory::getSidForAllowedStatuses(
            'createCustomerProfileAddress',
            $client->getId(),
            false
        );
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        RegistratorFacade::createCustomerSupportDocs(
            $this->client,
            "{$this->client->getCompanyName()} Document Address",
            'customer_profile_address',
            ['address_sids' => $this->addressInstanceSid]
        );
    }
}
