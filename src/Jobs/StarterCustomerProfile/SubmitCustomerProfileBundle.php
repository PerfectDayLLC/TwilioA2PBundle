<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Facades\Registrator as RegistratorFacade;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use Twilio\Exceptions\TwilioException;

class SubmitCustomerProfileBundle extends AbstractMainJob
{
    public string $customerProfilesEvaluationsInstanceStatus;

    public string $customerProfilesInstanceSid;

    public function __construct(ClientData $client)
    {
        parent::__construct($client);

        $this->customerProfilesInstanceSid = ClientRegistrationHistory::getSidForAllowedStatuses(
            'createEmptyCustomerProfileStarterBundle',
            $client->getId()
        );

        $this->customerProfilesEvaluationsInstanceStatus = ClientRegistrationHistory::getStatusForAllowedStatuses(
            'evaluateCustomerProfileBundle',
            $client->getId()
        );
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        if ($this->customerProfilesEvaluationsInstanceStatus !== Status::BUNDLES_COMPLIANT) {
            return;
        }

        RegistratorFacade::submitCustomerProfileBundle($this->client, $this->customerProfilesInstanceSid);
    }
}
