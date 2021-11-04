<?php

namespace PerfectDayLlc\TwilioA2PBundle\Services;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Entities\ClientRegistrationHistoryResponseData;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Api\V2010\Account\AddressInstance;
use Twilio\Rest\Client;
use Twilio\Rest\Messaging\V1\BrandRegistrationInstance;
use Twilio\Rest\Messaging\V1\Service\PhoneNumberInstance;
use Twilio\Rest\Messaging\V1\Service\UsAppToPersonInstance;
use Twilio\Rest\Messaging\V1\ServiceInstance;
use Twilio\Rest\Trusthub\V1\CustomerProfiles\CustomerProfilesEvaluationsInstance;
use Twilio\Rest\Trusthub\V1\CustomerProfilesInstance;
use Twilio\Rest\Trusthub\V1\EndUserInstance;
use Twilio\Rest\Trusthub\V1\PoliciesInstance;
use Twilio\Rest\Trusthub\V1\SupportingDocumentInstance;
use Twilio\Rest\Trusthub\V1\TrustProducts\TrustProductsEvaluationsInstance;
use Twilio\Rest\Trusthub\V1\TrustProductsInstance;

class RegisterService
{
    protected Client $client;

    protected int $requestDelay;

    protected string $primaryCustomerProfileSid;

    protected string $policySid;

    protected string $a2PProfilePolicySid;

    protected string $profilePolicyType;

    public function __construct(
        string $accountSid,
        string $token,
        string $primaryCustomerProfileSid,
        string $customerProfilePolicySid,
        string $a2PProfilePolicySid,
        string $profilePolicyType
    ) {
        try {
            $this->client = new Client($accountSid, $token);

            $this->primaryCustomerProfileSid = $primaryCustomerProfileSid;
            $this->policySid = $customerProfilePolicySid;
            $this->a2PProfilePolicySid = $a2PProfilePolicySid;
            $this->profilePolicyType = $profilePolicyType;

            $this->requestDelay = 1;
        } catch (ConfigurationException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => 0,
                    'request_type' => __FUNCTION__,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );
        }
    }

    /**
     * @throws TwilioException
     */
    public function createAndSubmitCustomerProfile(ClientData $client): ?CustomerProfilesInstance
    {
        $customerProfilesInstance = $this->createEmptyCustomerProfileBundle($client, $this->policySid);

        // Create end-user object of type: customer_profile_information
        $endUserInstance = $this->createEndUserCustomerProfileInfo($client, $this->profilePolicyType);

        // Create supporting document: customer_profile_address, then create customer_profile_address document
        $addressInstance = $this->createCustomerProfileAddress($client);

        // Create Customer Support Docs
        $supportingDocumentInstance = $this->createCustomerSupportDocs(
            $client->getCompanyName(),
            'customer_profile_address',
            ['address_sids' => $addressInstance->sid]
        );

        /**
         * Assign end-user, supporting document, and primary customer profile to the empty customer profile that
         * you created
         */
        $this->attachObjectSidToCustomerProfile($customerProfilesInstance->sid, $endUserInstance->sid);
        $this->attachObjectSidToCustomerProfile($customerProfilesInstance->sid, $supportingDocumentInstance->sid);
        $this->attachObjectSidToCustomerProfile($customerProfilesInstance->sid, $this->primaryCustomerProfileSid);

        // Evaluate the Customer Profile
        $customerProfilesEvaluationsInstance = $this->evaluateCustomerProfileBundle(
            $customerProfilesInstance->sid,
            $this->policySid
        );

        // Submit the Customer Profile for review
        return $customerProfilesEvaluationsInstance->status === Status::BUNDLES_COMPLIANT
            ? $this->submitCustomerProfileBundle($customerProfilesInstance->sid)
            : null;
    }

    /**
     * @throws TwilioException
     */
    public function createAndSubmitA2PProfile(ClientData $client, string $customerProfileSid): ?TrustProductsInstance
    {
        $trustProductsInstance = $this->createEmptyA2PTrustBundle($client, $this->a2PProfilePolicySid);

        $this->assignCustomerProfileA2PTrustBundle($trustProductsInstance->sid, $customerProfileSid);

        $trustProductsEvaluationsInstance = $this->evaluateA2PProfileBundle(
            $trustProductsInstance->sid,
            $this->a2PProfilePolicySid
        );

        return $trustProductsEvaluationsInstance->status === Status::BUNDLES_COMPLIANT
            ? $this->submitA2PProfileBundle($trustProductsInstance->sid)
            : null;
    }

    /**
     * @throws TwilioException
     */
    public function createMessageServiceWithPhoneNumber(
        ClientData $client,
        string $webhookUrl,
        string $fallbackWebhookUrl
    ): ?PhoneNumberInstance {
        $serviceInstance = $this->createMessagingService($client,
            $webhookUrl,
            $fallbackWebhookUrl
        );

        // Add Phone Number to Messaging Service
        return $serviceInstance->sid
            ? $this->addPhoneNumberToMessagingService($client, $serviceInstance->sid)
            : null;
    }

    /**
     * @throws TwilioException
     */
    private function createEmptyCustomerProfileBundle(
        ClientData $client,
        string $policySid
    ): CustomerProfilesInstance {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $customerProfilesInstance = $this->client->trusthub->v1->customerProfiles
                ->create(
                    $this->friendlyName($client->getCompanyName()),
                    $client->getContactEmail(),
                    $policySid
                );

            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $customerProfilesInstance->sid,
                    'object_sid' => $customerProfilesInstance->sid,
                    'status' => $customerProfilesInstance->status,
                    'response' => $customerProfilesInstance->toArray(),
                ])
            );

            return $customerProfilesInstance;
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );

            throw $exception;
        }
    }

    /**
     * @throws TwilioException
     */
    private function createEndUserCustomerProfileInfo(ClientData $client, string $profilePolicyType): EndUserInstance
    {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $endUserInstance = $this->client->trusthub->v1->endUsers
                ->create(
                    $this->friendlyName($client->getCompanyName()).' Contact Info',
                    $profilePolicyType."_customer_profile_information",
                    [
                        'attributes' => [
                            'first_name' => $client->getContactFirstname(),
                            'last_name' => $client->getContactLastname(),
                            'email' => $client->getContactEmail(),
                            'phone_number' => $this->formatPhoneNumber($client->getContactPhone()),
                        ],
                    ]
                );

            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'object_sid' => $endUserInstance->sid,
                    'status' => Status::EXECUTED,
                    'response' => $endUserInstance->toArray(),
                ])
            );

            return $endUserInstance;
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );

            throw $exception;
        }
    }

    /**
     * @throws TwilioException
     */
    private function createCustomerProfileAddress(ClientData $client): AddressInstance
    {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $addressInstance = $this->client->addresses
                ->create(
                    $client->getCompanyName(),
                    $client->getAddress(),
                    $client->getCity(),
                    $client->getState(),
                    $client->getZip(),
                    $client->getCountry()
                );

            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'object_sid' => $addressInstance->sid,
                    'status' => Status::EXECUTED,
                    'response' => $addressInstance->toArray(),
                ])
            );

            return $addressInstance;
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );

            throw $exception;
        }
    }

    /**
     * @throws TwilioException
     */
    private function createCustomerSupportDocs(
        string $documentName,
        string $documentType,
        array $attributes
    ): SupportingDocumentInstance {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $supportingDocumentInstance = $this->client->trusthub->v1->supportingDocuments
                ->create(
                    $this->friendlyName($documentName),
                    $documentType,
                    ['attributes' => $attributes]
                );

            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'request_type' => __FUNCTION__,
                    'object_sid' => $supportingDocumentInstance->sid,
                    'status' => $supportingDocumentInstance->status,
                    'response' => $supportingDocumentInstance->toArray(),
                ])
            );

            return $supportingDocumentInstance;
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'request_type' => __FUNCTION__,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );

            throw $exception;
        }
    }

    /**
     * @throws TwilioException
     */
    private function attachObjectSidToCustomerProfile(string $customerProfileBundleSid, string $objectSid): void
    {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $customerProfilesEntityAssignmentsInstance = $this->client->trusthub->v1
                ->customerProfiles($customerProfileBundleSid)
                ->customerProfilesEntityAssignments
                ->create($objectSid);

            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $customerProfileBundleSid,
                    'object_sid' => $customerProfilesEntityAssignmentsInstance->sid,
                    'status' => Status::EXECUTED,
                    'response' => $customerProfilesEntityAssignmentsInstance->toArray(),
                ])
            );
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $customerProfileBundleSid,
                    'object_sid' => $objectSid,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );

            throw $exception;
        }
    }

    /**
     * @throws TwilioException
     */
    private function evaluateCustomerProfileBundle(
        string $customerProfileBundleSid,
        string $policySid
    ): CustomerProfilesEvaluationsInstance {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $customerProfilesEvaluationsInstance = $this->client->trusthub->v1
                ->customerProfiles($customerProfileBundleSid)
                ->customerProfilesEvaluations
                ->create($policySid);

            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $customerProfileBundleSid,
                    'object_sid' => $customerProfilesEvaluationsInstance->sid,
                    'status' => $customerProfilesEvaluationsInstance->status,
                    'response' => $customerProfilesEvaluationsInstance->toArray(),
                ])
            );

            return $customerProfilesEvaluationsInstance;
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $customerProfileBundleSid,
                    'object_sid' => $policySid,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );

            throw $exception;
        }
    }

    /**
     * @throws TwilioException
     */
    private function submitCustomerProfileBundle(string $customerProfileBundleSid): CustomerProfilesInstance
    {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $customerProfilesInstance = $this->client->trusthub->v1->customerProfiles($customerProfileBundleSid)
                ->update(['status' => 'pending-review']);

            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $customerProfileBundleSid,
                    'object_sid' => $customerProfilesInstance->sid,
                    'status' => $customerProfilesInstance->status,
                    'response' => $customerProfilesInstance->toArray(),
                ])
            );

            return $customerProfilesInstance;
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $customerProfileBundleSid,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );

            throw $exception;
        }
    }

    /**
     * @throws TwilioException
     */
    private function createEmptyA2PTrustBundle(ClientData $client, string $policySid): TrustProductsInstance
    {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $trustProductsInstance = $this->client->trusthub->v1->trustProducts
                ->create(
                    $this->friendlyName($client->getCompanyName()),
                    $client->getContactEmail(),
                    $policySid
                );

            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $trustProductsInstance->sid,
                    'object_sid' => $trustProductsInstance->sid,
                    'status' => $trustProductsInstance->status,
                    'response' => $trustProductsInstance->toArray(),
                ])
            );

            return $trustProductsInstance;
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );

            throw $exception;
        }
    }

    /**
     * @throws TwilioException
     */
    private function assignCustomerProfileA2PTrustBundle(
        string $trustBundleSid,
        string $customerProfileSid
    ): void {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $trustProductsEntityAssignmentsInstance = $this->client->trusthub->v1->trustProducts($trustBundleSid)
                ->trustProductsEntityAssignments
                ->create($customerProfileSid);

            //Insert request to history log
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $trustBundleSid,
                    'object_sid' => $trustProductsEntityAssignmentsInstance->sid,
                    'status' => Status::EXECUTED,
                    'response' => $trustProductsEntityAssignmentsInstance->toArray(),
                ])
            );
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $trustBundleSid,
                    'object_sid' => $customerProfileSid,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );

            throw $exception;
        }
    }

    /**
     * @throws TwilioException
     */
    private function evaluateA2PProfileBundle(
        string $trustBundleSid,
        string $policySid
    ): TrustProductsEvaluationsInstance {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $trustProductsEvaluationsInstance = $this->client->trusthub->v1->trustProducts($trustBundleSid)
                ->trustProductsEvaluations
                ->create($policySid);

            //Insert request to history log
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $trustBundleSid,
                    'object_sid' => $trustProductsEvaluationsInstance->sid,
                    'status' => $trustProductsEvaluationsInstance->status,
                    'response' => $trustProductsEvaluationsInstance->toArray(),
                ])
            );

            return $trustProductsEvaluationsInstance;
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $trustBundleSid,
                    'object_sid' => $policySid,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );

            throw $exception;
        }
    }

    /**
     * @throws TwilioException
     */
    private function submitA2PProfileBundle(string $trustBundleSid): TrustProductsInstance
    {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $trustProductsInstance = $this->client->trusthub->v1->trustProducts($trustBundleSid)
                ->update(['status' => 'pending-review']);

            //Insert request to history log
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $trustBundleSid,
                    'object_sid' => $trustBundleSid,
                    'status' => $trustProductsInstance->status,
                    'response' => $trustProductsInstance->toArray(),
                ])
            );

            return $trustProductsInstance;
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $trustBundleSid,
                    'object_sid' => $trustBundleSid,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );

            throw $exception;
        }
    }

    /**
     * @throws TwilioException
     */
    public function createA2PBrand(
        ClientData $client,
        string $a2PProfileBundleSid,
        string $customerProfileBundleSid,
        string $profilePolicyType = ''
    ): BrandRegistrationInstance {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $brandRegistrationInstance = $this->client->messaging->v1->brandRegistrations
                ->create(
                    $customerProfileBundleSid,
                    $a2PProfileBundleSid,
                    ['brandType' => $profilePolicyType ?: strtoupper($this->profilePolicyType)]
                );

            //Insert request to history log
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $brandRegistrationInstance->a2PProfileBundleSid,
                    'object_sid' => $brandRegistrationInstance->sid,
                    'status' => $brandRegistrationInstance->status,
                    'response' => $brandRegistrationInstance->toArray(),
                ])
            );

            return $brandRegistrationInstance;
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $a2PProfileBundleSid,
                    'object_sid' => $customerProfileBundleSid,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );

            throw $exception;
        }
    }

    /**
     * @throws TwilioException
     */
    public function createMessagingService(
        ClientData $client,
        string $webhookUrl,
        string $fallbackWebhookUrl
    ): ServiceInstance {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $serviceInstance = $this->client->messaging->v1->services
                ->create(
                    $this->friendlyName($client->getCompanyName()).' messaging service',
                    [
                        'inboundRequestUrl' => $webhookUrl,
                        'fallbackUrl' => $fallbackWebhookUrl,
                    ]
                );

            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'object_sid' => $serviceInstance->sid,
                    'status' => Status::EXECUTED,
                    'response' => $serviceInstance->toArray(),
                ])
            );

            return $serviceInstance;
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );

            throw $exception;
        }
    }

    /**
     * @throws TwilioException
     */
    private function addPhoneNumberToMessagingService(
        ClientData $client,
        string $messageServiceSid
    ): PhoneNumberInstance {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $phoneNumberInstance = $this->client->messaging->v1->services($messageServiceSid)
                ->phoneNumbers
                ->create($client->getPhoneSid());

            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'object_sid' => $phoneNumberInstance->sid,
                    'status' => Status::EXECUTED,
                    'response' => $phoneNumberInstance->toArray(),
                ])
            );

            return $phoneNumberInstance;
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );

            throw $exception;
        }
    }

    /**
     * @throws TwilioException
     */
    public function createA2PMessagingCampaignUseCase(
        ClientData $client,
        string $a2PBrandSid,
        string $messagingServiceSid,
        string $profilePolicyType = ''
    ): UsAppToPersonInstance {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $usAppToPersonInstance = $this->client->messaging->v1->services($messagingServiceSid)
                ->usAppToPerson
                ->create(
                    $a2PBrandSid,
                    'Send marketing messages about sales and offers',
                    ['Twilio draw the OWL event is ON'],
                    $profilePolicyType ?: strtoupper($this->profilePolicyType),
                    true,
                    true
                );

            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $usAppToPersonInstance->sid,
                    'object_sid' => $usAppToPersonInstance->sid,
                    'status' => $usAppToPersonInstance->campaignStatus,
                    'response' => $usAppToPersonInstance->toArray(),
                ])
            );

            return $usAppToPersonInstance;
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );

            throw $exception;
        }
    }

    /**
     * @throws TwilioException
     */
    public function fetchCustomerProfilePolicy(string $policySid): PoliciesInstance
    {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $policiesInstance = $this->client->trusthub->v1->policies($policySid)->fetch();

            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'request_type' => __FUNCTION__,
                    'object_sid' => $policySid,
                    'response' => $policiesInstance->toArray(),
                ])
            );

            return $policiesInstance;
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'request_type' => __FUNCTION__,
                    'object_sid' => $policySid,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );

            throw $exception;
        }
    }

    private function exceptionToArray($exception): array
    {
        $array = [];

        if (method_exists($exception, 'getMessage')) {
            $array['message'] = $exception->getMessage();
        }

        if (method_exists($exception, 'getCode')) {
            $array['code'] = $exception->getCode();
        }

        if (method_exists($exception, 'getFile')) {
            $array['file'] = $exception->getFile();
        }

        if (method_exists($exception, 'getLine')) {
            $array['line'] = $exception->getLine();
        }

        if (method_exists($exception, 'getTrace')) {
            $array['trace'] = $exception->getTrace();
        }

        return $array;
    }

    private function friendlyName(string $name, bool $removeSpaces = false, bool $removeLetters = false): string
    {
        if (empty($name)) {
            return $name;
        }

        $regex = '/[^a-zA-Z0-9 ]/';

        if ($removeSpaces && $removeLetters) {
            $regex = '/[^0-9]/';
        } elseif ($removeSpaces) {
            $regex = '/[^a-zA-Z0-9]/';
        }

        return preg_replace($regex, '', $name);
    }

    private function formatPhoneNumber($phoneNumber): string
    {
        $modifiedPhoneNumber = $this->friendlyName($phoneNumber, true, true);

        if (substr($modifiedPhoneNumber, 0, 1) === '1') {
            $modifiedPhoneNumber = '+'.$modifiedPhoneNumber;
        } else {
            $modifiedPhoneNumber = '+1'.$modifiedPhoneNumber;
        }

        return $modifiedPhoneNumber;
    }

    private function saveNewClientRegistrationHistory(ClientRegistrationHistoryResponseData $historyData): void
    {
        $history = new ClientRegistrationHistory;

        $history->entity_id = $historyData->getEntityId();
        $history->request_type = $historyData->getRequestType();
        $history->error = $historyData->getError();
        $history->bundle_sid = $historyData->getBundleSid();
        $history->object_sid = $historyData->getObjectSid();
        $history->status = $historyData->getStatus();
        $history->response = $historyData->getResponse();

        $history->save();
    }
}
