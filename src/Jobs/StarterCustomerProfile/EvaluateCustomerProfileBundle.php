<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Twilio\Exceptions\TwilioException;

class EvaluateCustomerProfileBundle extends AbstractMainJob
{
    private string $customerProfileBundleSid;

    public function __construct(RegisterService $registerService, ClientData $client)
    {
        parent::__construct($registerService, $client);

        /**
         * Get and store SID (read entity->getId() latest request type = createEmptyCustomerProfileStarterBundle and get SID)
         *
         * Check RegisterService:59
         */
        $this->customerProfileBundleSid = '';
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        $this->registerService->evaluateCustomerProfileBundle(
            $this->client,
            $this->customerProfileBundleSid
        );
    }
}
