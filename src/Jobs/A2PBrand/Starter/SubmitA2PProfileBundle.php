<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand\Starter;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Twilio\Exceptions\TwilioException;

class SubmitA2PProfileBundle extends AbstractMainJob
{
    public string $trustProductsInstanceStatus;

    public string $trustProductsInstanceSid;

    public function __construct(RegisterService $registerService, ClientData $client)
    {
        parent::__construct($registerService, $client);

        $this->trustProductsInstanceSid = ClientRegistrationHistory::getSidForAllowedStatuses(
            'createEmptyA2PStarterTrustBundle',
            $client->getId()
        );

        $this->trustProductsInstanceStatus = ClientRegistrationHistory::getStatusForAllowedStatuses(
            'evaluateA2PStarterProfileBundle',
            $client->getId()
        );
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        if ($this->trustProductsInstanceStatus !== Status::BUNDLES_COMPLIANT) {
            return;
        }

        $this->registerService->submitA2PProfileBundle($this->client, $this->trustProductsInstanceSid);
    }
}
