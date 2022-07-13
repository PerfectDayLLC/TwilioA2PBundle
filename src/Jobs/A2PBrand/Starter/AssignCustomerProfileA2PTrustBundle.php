<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand\Starter;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
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
        $this->registerService->assignCustomerProfileA2PTrustBundle(
            $this->client,
            $this->trustProductsInstanceSid,
            $this->customerProfileBundleSid
        );
    }
}