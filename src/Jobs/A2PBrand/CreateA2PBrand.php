<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Twilio\Exceptions\TwilioException;

class CreateA2PBrand extends AbstractMainJob
{
    public ?string $customerProfileBundleSid;

    public ?string $a2PProfileBundleSid;

    public function __construct(RegisterService $registerService, ClientData $client)
    {
        parent::__construct($registerService, $client);

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
        $this->registerService->createA2PBrand(
            $this->client,
            $this->a2PProfileBundleSid,
            $this->customerProfileBundleSid
        );
    }
}
