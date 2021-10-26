<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Twilio\Exceptions\TwilioException;

class SubmitA2PTrustBundle extends AbstractMainJob
{
    private bool $createA2PBrand;

    private bool $createMessagingService;

    private string $customerProfileBundleSid;

    private string $fallbackWebhookUrl;

    private string $webhookUrl;

    public function __construct(
        RegisterService $registerService,
        ClientData $client,
        string $customerProfileBundleSid = '',
        string $webhookUrl = '',
        string $fallbackWebhookUrl = '',
        bool $createA2PBrand = false,
        bool $createMessagingService = false
    ) {
        parent::__construct($registerService, $client);

        $this->webhookUrl = $webhookUrl;
        $this->fallbackWebhookUrl = $fallbackWebhookUrl;
        $this->createA2PBrand = $createA2PBrand;
        $this->createMessagingService = $createMessagingService;

        $this->customerProfileBundleSid = $customerProfileBundleSid ?:
            ClientRegistrationHistory::getBundleSidForAllowedStatuses('submitCustomerProfileBundle', $client->getId());
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        $trustProductsInstance = $this->registerService
            ->createAndSubmitA2PProfile($this->client, $this->customerProfileBundleSid);

        if (! $trustProductsInstance) {
            return;
        }

        if ($this->createA2PBrand) {
            dispatch(
                (new CreateA2PBrand($this->registerService, $this->client, $trustProductsInstance->sid))
                    ->onQueue('create-brand')
            );
        }

        if ($this->createMessagingService) {
            dispatch(
                (new CreateMessagingService(
                    $this->registerService,
                    $this->client,
                    $this->webhookUrl,
                    $this->fallbackWebhookUrl,
                    true
                ))
                    ->onQueue('create-messaging-service')
            );
        }
    }
}
