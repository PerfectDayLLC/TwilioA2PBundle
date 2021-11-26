<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Twilio\Exceptions\TwilioException;

class CreateA2PSmsCampaignUseCase extends AbstractMainJob
{
    private string $messagingServiceSid;

    private string $a2PBrandSid;

    public function __construct(
        RegisterService $registerService,
        ClientData $client,
        string $a2PBrandSid = '',
        string $messagingServiceSid = ''
    ) {
        parent::__construct($registerService, $client);

        $this->a2PBrandSid = $a2PBrandSid ?:
            ClientRegistrationHistory::getBundleSidForAllowedStatuses('createA2PBrand', $client->getId());

        $this->messagingServiceSid = $messagingServiceSid ?:
            ClientRegistrationHistory::getBundleSidForAllowedStatuses('createMessagingService', $client->getId());
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        /**
         * TODO: This will only work once A2P Brand was Status::BRAND_APPROVED else an error will be returned.
         * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api#51-create-an-a2p-messaging-campaign-use-case
         */
        $this->registerService->createA2PMessagingCampaignUseCase(
            $this->client,
            $this->a2PBrandSid,
            $this->messagingServiceSid
        );
    }
}
