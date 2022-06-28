<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrandStarter;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Twilio\Exceptions\TwilioException;

class EvaluateA2PStarterProfileBundle extends AbstractMainJob
{
    public string $trustProductsInstanceSid;

    public function __construct(RegisterService $registerService, ClientData $client)
    {
        parent::__construct($registerService, $client);

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
        $this->registerService->evaluateA2PStarterProfileBundle(
            $this->client,
            $this->trustProductsInstanceSid
        );
    }
}
