<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Twilio\Exceptions\TwilioException;

class CreateA2PBrand extends AbstractMainJob
{
    private string $customerProfileBundleSid;

    private string $a2PProfileBundleSid;

    public function __construct(
        RegisterService $registerService,
        ClientData $client,
        string $a2PProfileBundleSid = '',
        string $customerProfileBundleSid = ''
    ) {
        parent::__construct($registerService, $client);

        $this->a2PProfileBundleSid = $a2PProfileBundleSid ?:
            ClientRegistrationHistory::getBundleSidForAllowedStatuses(
                'submitA2PProfileBundle',
                $this->client->getId()
            );

        $this->customerProfileBundleSid = $customerProfileBundleSid ?:
            ClientRegistrationHistory::getBundleSidForAllowedStatuses(
                'submitCustomerProfileBundle',
                $this->client->getId()
            );
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        $this->registerService->createA2PBrand(
            $this->client,
            $this->a2PProfileBundleSid,
            $this->customerProfileBundleSid
        );
    }
}
