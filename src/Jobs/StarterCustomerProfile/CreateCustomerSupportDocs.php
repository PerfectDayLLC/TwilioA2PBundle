<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Twilio\Exceptions\TwilioException;

class CreateCustomerSupportDocs extends AbstractMainJob
{
    private string $sid;

    public function __construct(RegisterService $registerService, ClientData $client)
    {
        parent::__construct($registerService, $client);

        // Get and store SID (read entity->getId() latest request type = createCustomerProfileAddress and get SID)
        // Check RegisterService:65
        $this->sid = '';
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        $this->registerService->createCustomerSupportDocs(
            $this->client,
            "{$this->client->getCompanyName()} Document Address",
            'customer_profile_address',
            ['address_sids' => $this->sid]
        );
    }
}
