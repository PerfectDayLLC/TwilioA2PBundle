<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;

class CheckA2PBrandStatus extends AbstractMainJob
{
    public ?string $brandObjectSid;

    public function __construct(RegisterService $registerService, ClientData $client)
    {
        parent::__construct($registerService, $client);

        $this->brandObjectSid = ClientRegistrationHistory::getSid(
            'createA2PBrand',
            $this->client->getId(),
            false,
            'pending'
        );
    }

    public function handle(): void
    {

    }
}
