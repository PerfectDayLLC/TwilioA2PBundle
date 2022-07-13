<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\MessagingService;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Facades\Registrator as RegistratorFacade;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use Twilio\Exceptions\TwilioException;

class AddPhoneNumberToMessagingService extends AbstractMainJob
{
    private ?string $serviceInstanceSid;

    public function __construct(ClientData $client)
    {
        parent::__construct($client);

        $this->serviceInstanceSid = ClientRegistrationHistory::getSidForAllowedStatuses(
            'createMessagingService',
            $client->getId(),
            false
        );
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        if (! $this->serviceInstanceSid) {
            return;
        }

        RegistratorFacade::addPhoneNumberToMessagingService($this->client, $this->serviceInstanceSid);
    }
}
