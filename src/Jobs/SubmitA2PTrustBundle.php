<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Entities\RegisterClientsMethodsSignatureEnum;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Twilio\Exceptions\TwilioException;

class SubmitA2PTrustBundle extends AbstractMainJob
{
    public ?string $customerProfileBundleSid;

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
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        $this->registerService->createAndSubmitA2PProfile($this->client, $this->customerProfileBundleSid);
    }
}
