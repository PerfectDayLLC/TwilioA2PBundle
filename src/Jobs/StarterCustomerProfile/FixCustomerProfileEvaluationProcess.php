<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Facades\Registrator as RegistratorFacade;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use Twilio\Exceptions\TwilioException;

class FixCustomerProfileEvaluationProcess extends AbstractMainJob
{
    public ClientRegistrationHistory $endUserCustomerProfileInfo;

    public function __construct(ClientData $client)
    {
        parent::__construct($client);

        $this->endUserCustomerProfileInfo = ClientRegistrationHistory::getHistory(
            'createEndUserCustomerProfileInfo',
            $client->getId()
        );
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        RegistratorFacade::updateEndUserCustomerProfileInfo($this->client, $this->endUserCustomerProfileInfo);
    }
}
