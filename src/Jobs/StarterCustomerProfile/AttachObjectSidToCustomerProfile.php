<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Jobs\AbstractMainJob;
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

        /**
         * Get and store createEmptyCustomerProfileStarterBundle's SID (read entity->getId() latest request type = createEmptyCustomerProfileStarterBundle and get SID).
         *
         * Check RegisterService:59
         */
        $this->customerProfilesInstanceSid = '';

        /**
         * Get and store endUserInstance's SID (read entity->getId() latest request type = createEndUserCustomerProfileInfo and get SID).
         *
         * Check RegisterService:62
         */
        $this->endUserInstanceSid = '';

        /**
         * Get and store supportingDocumentInstance's SID (read entity->getId() latest request type = createCustomerSupportDocs and get SID).
         *
         * Check RegisterService:68
         */
        $this->supportingDocumentInstanceSid = '';
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
            $this->primaryCustomerProfileSid // GET FROM CONFIG
        );
    }
}
