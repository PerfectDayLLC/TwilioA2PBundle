<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Twilio\Exceptions\TwilioException;

class CreateMessagingService extends AbstractMainJob
{
    private string $webhookUrl;

    private string $fallbackWebhookUrl;

    private bool $addPhoneNumber;

    public function __construct(
        RegisterService $registerService,
        ClientData $client,
        string $webhookUrl = '',
        string $fallbackWebhookUrl = '',
        bool $addPhoneNumber = false
    ) {
        parent::__construct($registerService, $client);

        $this->webhookUrl = $webhookUrl;
        $this->fallbackWebhookUrl = $fallbackWebhookUrl;
        $this->addPhoneNumber = $addPhoneNumber;
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        if ($this->addPhoneNumber) {
            // Create Messaging Service with Phone
            $this->registerService->createMessageServiceWithPhoneNumber(
                $this->client,
                $this->webhookUrl,
                $this->fallbackWebhookUrl
            );
        } else {
            // Create Messaging Service
            $this->registerService->createMessagingService(
                $this->client,
                $this->webhookUrl,
                $this->fallbackWebhookUrl
            );
        }
    }
}
