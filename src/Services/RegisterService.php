<?php

namespace PerfectDayLlc\TwilioA2PBundle\Services;

use Illuminate\Support\Str;
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
use Twilio\Rest\Trusthub\V1\SupportingDocumentInstance;
use Twilio\Rest\Trusthub\V1\TrustProducts\TrustProductsEvaluationsInstance;
use Twilio\Rest\Trusthub\V1\TrustProductsInstance;

class RegisterService
{
    private const STARTER_CUSTOMER_PROFILE_BUNDLE_POLICY_SID = 'RN806dd6cd175f314e1f96a9727ee271f4';

    private const STARTER_TRUST_BUNDLE_POLICY_SID = 'RN670d5d2e282a6130ae063b234b6019c8';

    protected Client $client;

    protected int $requestDelay;

    /**
     * @link https://www.twilio.com/docs/trust-hub/trusthub-rest-api/console-create-a-primary-customer-profile
     */
    protected string $primaryCustomerProfileSid;

    /**
     * @throws ConfigurationException
     */
    public function __construct(string $accountSid, string $token, string $primaryCustomerProfileSid)
    {
        $this->client = new Client($accountSid, $token);

        $this->primaryCustomerProfileSid = $primaryCustomerProfileSid;

        $this->requestDelay = 1;
    }

    /**
     * @deprecated
     * @throws TwilioException
     */
    public function createAndSubmitCustomerProfile(ClientData $client): ?CustomerProfilesInstance
    {
        // DONE
        $customerProfilesInstance = $this->createEmptyCustomerProfileStarterBundle($client);

        // DONE
        $endUserInstance = $this->createEndUserCustomerProfileInfo($client);

        // DONE
        $addressInstance = $this->createCustomerProfileAddress($client);

        // DONE - NEED TO GET $addressInstance ON THE JOB
        $supportingDocumentInstance = $this->createCustomerSupportDocs(
            $client,
            "{$client->getCompanyName()} Document Address",
            'customer_profile_address',
            ['address_sids' => $addressInstance->sid]
        );

        /**
         * Assign end-user, supporting document, and primary customer profile to the empty customer profile that
         * you created
         *
         * DONE - NEED TO GET $endUserInstance AND $supportingDocumentInstance ON THE JOB
         */
        $this->attachObjectSidToCustomerProfile($client, $customerProfilesInstance->sid, $endUserInstance->sid);
        $this->attachObjectSidToCustomerProfile(
            $client,
            $customerProfilesInstance->sid,
            $supportingDocumentInstance->sid
        );
        $this->attachObjectSidToCustomerProfile(
            $client,
            $customerProfilesInstance->sid,
            $this->primaryCustomerProfileSid
        );

        // DONE - NEED TO GET $customerProfilesInstance ON THE JOB
        $customerProfilesEvaluationsInstance = $this->evaluateCustomerProfileBundle(
            $client,
            $customerProfilesInstance->sid
        );

        // DONE - NEED TO GET $customerProfilesEvaluationsInstance AND $customerProfilesInstance ON THE JOB
        return $customerProfilesEvaluationsInstance->status === Status::BUNDLES_COMPLIANT
            ? $this->submitCustomerProfileBundle($client, $customerProfilesInstance->sid)
            : null;
    }

    /**
     * @throws TwilioException
     */
    public function createAndSubmitA2PProfile(ClientData $client, string $customerProfileSid): ?TrustProductsInstance
    {
        // DONE
        $trustProductsInstance = $this->createEmptyA2PStarterTrustBundle($client);

        // DONE
        $this->assignCustomerProfileA2PTrustBundle(
            $client,
            $trustProductsInstance->sid,
            $customerProfileSid
        );

        // DONE
        $trustProductsEvaluationsInstance = $this->evaluateA2PStarterProfileBundle(
            $client,
            $trustProductsInstance->sid
        );

        // DONE
        return $trustProductsEvaluationsInstance->status === Status::BUNDLES_COMPLIANT
            ? $this->submitA2PProfileBundle($client, $trustProductsInstance->sid)
            : null;
    }

    /**
     * @throws TwilioException
     */
    public function createMessageServiceWithPhoneNumber(ClientData $client): ?PhoneNumberInstance
    {
        $serviceInstance = $this->createMessagingService($client);

        // Add Phone Number to Messaging Service
        return $serviceInstance->sid
            ? $this->addPhoneNumberToMessagingService($client, $serviceInstance->sid)
            : null;
    }

    /**
     * @throws TwilioException
     */
    public function createEmptyCustomerProfileStarterBundle(ClientData $client): CustomerProfilesInstance
    {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $customerProfilesInstance = $this->client->trusthub->v1
                ->customerProfiles
                ->create(
                    $this->friendlyName($client->getCompanyName()),
                    $client->getContactEmail(),
                    self::STARTER_CUSTOMER_PROFILE_BUNDLE_POLICY_SID
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
     * Create end-user object of type: customer_profile_information
     * @throws TwilioException
     */
    public function createEndUserCustomerProfileInfo(ClientData $client): EndUserInstance
    {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $endUserInstance = $this->client->trusthub->v1
                ->endUsers
                ->create(
                    "{$this->friendlyName($client->getCompanyName())} Contact Info",
                    'starter_customer_profile_information',
                    [
                        'attributes' => [
                            'first_name' => $client->getContactFirstname(),
                            'last_name' => $client->getContactLastname(),
                            'email' => $client->getContactEmail(),
                            'phone_number' => $client->getContactPhone(),
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
     * Create supporting document: customer_profile_address
     *
     * @throws TwilioException
     */
    public function createCustomerProfileAddress(ClientData $client): AddressInstance
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
                    $client->getRegion(),
                    $client->getZip(),
                    $client->getIsoCountry()
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
     * Create Customer Support Docs
     *
     * @throws TwilioException
     */
    public function createCustomerSupportDocs(
        ClientData $client,
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
            $supportingDocumentInstance = $this->client->trusthub->v1
                ->supportingDocuments
                ->create(
                    $this->friendlyName($documentName),
                    $documentType,
                    ['attributes' => $attributes]
                );

            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
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
    public function attachObjectSidToCustomerProfile(
        ClientData $client,
        string $customerProfileBundleSid,
        string $objectSid
    ): void {
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
                    'entity_id' => $client->getId(),
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
                    'entity_id' => $client->getId(),
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
     * Evaluate the Customer Profile
     *
     * @throws TwilioException
     */
    public function evaluateCustomerProfileBundle(
        ClientData $client,
        string $customerProfileBundleSid
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
                ->create(self::STARTER_CUSTOMER_PROFILE_BUNDLE_POLICY_SID);

            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $customerProfileBundleSid,
                    'object_sid' => $customerProfilesEvaluationsInstance->sid,
                    'status' => $customerProfilesEvaluationsInstance->status,
                    'response' => $customerProfilesEvaluationsInstance->toArray(),
                    'error' => $customerProfilesEvaluationsInstance->status === Status::BUNDLES_NONCOMPLIANT,
                ])
            );

            return $customerProfilesEvaluationsInstance;
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $customerProfileBundleSid,
                    'object_sid' => self::STARTER_CUSTOMER_PROFILE_BUNDLE_POLICY_SID,
                    'status' => Status::EXCEPTION_ERROR,
                    'response' => $this->exceptionToArray($exception),
                    'error' => true,
                ])
            );

            throw $exception;
        }
    }

    /**
     * Submit the Customer Profile for review
     *
     * @throws TwilioException
     */
    public function submitCustomerProfileBundle(
        ClientData $client,
        string $customerProfileBundleSid
    ): CustomerProfilesInstance {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $customerProfilesInstance = $this->client->trusthub->v1
                ->customerProfiles($customerProfileBundleSid)
                ->update(['status' => Status::BUNDLES_PENDING_REVIEW]);

            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
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
                    'entity_id' => $client->getId(),
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
    public function createEmptyA2PStarterTrustBundle(ClientData $client): TrustProductsInstance
    {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $trustProductsInstance = $this->client->trusthub->v1
                ->trustProducts
                ->create(
                    "A2P Starter for {$this->friendlyName($client->getCompanyName())}",
                    $client->getContactEmail(),
                    self::STARTER_TRUST_BUNDLE_POLICY_SID
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
    public function assignCustomerProfileA2PTrustBundle(
        ClientData $client,
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
            $trustProductsEntityAssignmentsInstance = $this->client->trusthub->v1
                ->trustProducts($trustBundleSid)
                ->trustProductsEntityAssignments
                ->create($customerProfileSid);

            //Insert request to history log
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
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
                    'entity_id' => $client->getId(),
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
    public function evaluateA2PStarterProfileBundle(
        ClientData $client,
        string $trustBundleSid
    ): TrustProductsEvaluationsInstance {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $trustProductsEvaluationsInstance = $this->client->trusthub->v1
                ->trustProducts($trustBundleSid)
                ->trustProductsEvaluations
                ->create(self::STARTER_TRUST_BUNDLE_POLICY_SID);

            //Insert request to history log
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $trustBundleSid,
                    'object_sid' => $trustProductsEvaluationsInstance->sid,
                    'status' => $trustProductsEvaluationsInstance->status,
                    'response' => $trustProductsEvaluationsInstance->toArray(),
                    'error' => $trustProductsEvaluationsInstance->status === Status::BUNDLES_NONCOMPLIANT,
                ])
            );

            return $trustProductsEvaluationsInstance;
        } catch (TwilioException $exception) {
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
                    'request_type' => __FUNCTION__,
                    'bundle_sid' => $trustBundleSid,
                    'object_sid' => self::STARTER_TRUST_BUNDLE_POLICY_SID,
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
    public function submitA2PProfileBundle(ClientData $client, string $trustBundleSid): TrustProductsInstance
    {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $trustProductsInstance = $this->client->trusthub->v1
                ->trustProducts($trustBundleSid)
                ->update(['status' => Status::BUNDLES_PENDING_REVIEW]);

            //Insert request to history log
            $this->saveNewClientRegistrationHistory(
                ClientRegistrationHistoryResponseData::createFromArray([
                    'entity_id' => $client->getId(),
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
                    'entity_id' => $client->getId(),
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
     * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api#31-get-the-brand-registration-status
     */
    public function createA2PBrand(
        ClientData $client,
        string $a2PProfileBundleSid,
        string $customerProfileBundleSid
    ): BrandRegistrationInstance {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $brandRegistrationInstance = $this->client->messaging->v1
                ->brandRegistrations
                ->create(
                    $customerProfileBundleSid,
                    $a2PProfileBundleSid,
                    ['brandType' => 'STARTER']
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
    public function createMessagingService(ClientData $client): ServiceInstance
    {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $serviceInstance = $this->client->messaging->v1
                ->services
                ->create(
                    $this->friendlyName($client->getCompanyName()).' Messaging Service',
                    [
                        'inboundRequestUrl' => $client->getWebhookUrl(),
                        'fallbackUrl' => $client->getFallbackWebhookUrl(),
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
            $phoneNumberInstance = $this->client->messaging->v1
                ->services($messageServiceSid)
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
        string $messagingServiceSid
    ): UsAppToPersonInstance {
        /**
         * Delay before requests
         *
         * @link https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
         */
        sleep($this->requestDelay);

        try {
            $usAppToPersonInstance = $this->client->messaging->v1
                ->services($messagingServiceSid)
                ->usAppToPerson
                ->create(
                    $a2PBrandSid,
                    'Send marketing messages about sales and offers',
                    ['Twilio draw the OWL event is ON'],
                    'STARTER',
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
                    'bundle_sid' => $a2PBrandSid,
                    'object_sid' => $messagingServiceSid,
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
