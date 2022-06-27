<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrandStarter;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Twilio\Exceptions\TwilioException;

class EvaluateA2PStarterProfileBundle extends AbstractMainJob
{
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
