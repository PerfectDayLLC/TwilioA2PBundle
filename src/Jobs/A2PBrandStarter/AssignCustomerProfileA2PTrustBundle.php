<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrandStarter;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Entities\RegisterClientsMethodsSignatureEnum;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Twilio\Exceptions\TwilioException;

class AssignCustomerProfileA2PTrustBundle extends AbstractMainJob
{
    public string $customerProfileBundleSid;

    public string $trustProductsInstanceSid;

    public function __construct(
        RegisterService $registerService,
        ClientData $client,
        ?string $customerProfileBundleSid = null
    ) {
        parent::__construct($registerService, $client);

        $this->customerProfileBundleSid = $customerProfileBundleSid ?:
            ClientRegistrationHistory::getSidForAllowedStatuses(
                RegisterClientsMethodsSignatureEnum::SUBMIT_CUSTOMER_PROFILE_BUNDLE,
                $client->getId()
            );

        /**
         * Get and store SID (read entity->getId() latest request type = createEmptyA2PStarterTrustBundle and get SID)
         *
         * Check RegisterService:111
         */
        $this->trustProductsInstanceSid = '';
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        $this->registerService->assignCustomerProfileA2PTrustBundle(
            $this->client,
            $this->trustProductsInstanceSid,
            $this->customerProfileBundleSid
        );
    }
}
