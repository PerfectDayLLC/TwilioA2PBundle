<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrandStarter;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Twilio\Exceptions\TwilioException;

class SubmitA2PProfileBundle extends AbstractMainJob
{
    public string $trustProductsInstanceStatus;

    public string $trustProductsInstanceSid;

    public function __construct(RegisterService $registerService, ClientData $client)
    {
        parent::__construct($registerService, $client);

        /**
         * Get and store SID (read entity->getId() latest request type = createEmptyA2PStarterTrustBundle and get SID)
         *
         * Check RegisterService:111
         */
        $this->trustProductsInstanceSid = '';

        /**
         * Get and store SID (read entity->getId() latest request type = evaluateA2PStarterProfileBundle and get SID)
         *
         * Check RegisterService:121
         */
        $this->trustProductsInstanceStatus = '';
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
