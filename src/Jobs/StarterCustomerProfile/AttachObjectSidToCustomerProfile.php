<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Twilio\Exceptions\TwilioException;

class AttachObjectSidToCustomerProfile extends AbstractMainJob
{
    private string $endUserInstanceSid;

    private string $supportingDocumentInstanceSid;

    private string $customerProfilesInstanceSid;

    public function __construct(RegisterService $registerService, ClientData $client)
    {
        parent::__construct($registerService, $client);

        $this->customerProfilesInstanceSid = ClientRegistrationHistory::getSidForAllowedStatuses(
            'createEmptyCustomerProfileStarterBundle',
            $client->getId()
        );

        $this->endUserInstanceSid = ClientRegistrationHistory::getSidForAllowedStatuses(
            'createEndUserCustomerProfileInfo',
            $client->getId(),
            false
        );

        $this->supportingDocumentInstanceSid = ClientRegistrationHistory::getSidForAllowedStatuses(
            'createCustomerSupportDocs',
            $client->getId(),
            false
        );
    }

    /**
     * @throws TwilioException
     */
    public function handle(): void
    {
        // Assign end-user to the empty customer profile that you created
        $this->registerService->attachObjectSidToCustomerProfile(
            $this->client,
            $this->customerProfilesInstanceSid,
            $this->endUserInstanceSid
        );

        // Assign supporting document to the empty customer profile that you created
        $this->registerService->attachObjectSidToCustomerProfile(
            $this->client,
            $this->customerProfilesInstanceSid,
            $this->supportingDocumentInstanceSid
        );

        // Assign primary customer profile to the empty customer profile that you created
        $this->registerService->attachObjectSidToCustomerProfile(
            $this->client,
            $this->customerProfilesInstanceSid,
            config('services.twilio.primary_customer_profile_sid')
        );
    }
}
