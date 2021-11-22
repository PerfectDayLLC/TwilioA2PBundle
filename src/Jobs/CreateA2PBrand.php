<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Twilio\Exceptions\TwilioException;

class CreateA2PBrand extends AbstractMainJob
{
    public string $customerProfileBundleSid;

    public string $a2PProfileBundleSid;

    public bool $createMessagingService;

    public function __construct(
        RegisterService $registerService,
        ClientData $client,
        bool $createMessagingService = false,
        string $a2PProfileBundleSid = '',
        string $customerProfileBundleSid = ''
    ) {
        parent::__construct($registerService, $client);

        $this->createMessagingService = $createMessagingService;

        $this->a2PProfileBundleSid = $a2PProfileBundleSid ?:
            ClientRegistrationHistory::getBundleSidForAllowedStatuses(
                'submitA2PProfileBundle',
                $this->client->getId()
            );

        $this->customerProfileBundleSid = $customerProfileBundleSid ?:
            ClientRegistrationHistory::getBundleSidForAllowedStatuses(
                'submitCustomerProfileBundle',
                $this->client->getId()
            );
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        $brandRegistrationInstance = $this->registerService->createA2PBrand(
            $this->client,
            $this->a2PProfileBundleSid,
            $this->customerProfileBundleSid
        );

        if ($brandRegistrationInstance->sid && $this->createMessagingService) {
            dispatch(
                new CreateMessagingService(
                    $this->registerService,
                    $this->client,
                    true
                )
            )
                ->onQueue('create-messaging-service');
        }
    }
}
