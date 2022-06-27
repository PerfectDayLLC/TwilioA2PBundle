<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Twilio\Exceptions\TwilioException;

class SubmitCustomerProfileBundle extends AbstractMainJob
{
    private string $customerProfilesEvaluationsInstanceStatus;

    private string $customerProfilesInstanceSid;

    public function __construct(RegisterService $registerService, ClientData $client)
    {
        parent::__construct($registerService, $client);

        /**
         * Get and store SID (read entity->getId() latest request type = createEmptyCustomerProfileStarterBundle and get SID).
         *
         * Check RegisterService:59
         */
        $this->customerProfilesInstanceSid = '';

        /**
         * Get and store SID (read entity->getId() latest request type = evaluateCustomerProfileBundle and get SID).
         *
         * Check RegisterService:94
         */
        $this->customerProfilesEvaluationsInstanceStatus = '';
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        if ($this->customerProfilesEvaluationsInstanceStatus !== Status::BUNDLES_COMPLIANT) {
            return;
        }

        $this->registerService->submitCustomerProfileBundle($this->client, $this->customerProfilesInstanceSid);
    }
}
