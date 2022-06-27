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
     * @see https://www.twilio.com/docs/trust-hub/trusthub-rest-api/console-create-a-primary-customer-profile
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
     * Create an empty Starter Customer Profile Bundle.
     *
     * @throws TwilioException
     *
     * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api#12-create-an-empty-starter-customer-profile-bundle
     */
    public function createEmptyCustomerProfileStarterBundle(ClientData $client): CustomerProfilesInstance
    {
        /**
         * Rate-limiting request.
         *
         * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
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
     * Create end-user object of type: customer_profile_information.
     *
     * @throws TwilioException
     *
     * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api#12-create-an-empty-starter-customer-profile-bundle
     */
    public function createEndUserCustomerProfileInfo(ClientData $client): EndUserInstance
    {
        /**
         * Rate-limiting request.
         *
         * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
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
     * Create supporting document: customer_profile_address.
     *
     * @throws TwilioException
     *
     * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api#14-create-supporting-document-customer_profile_address
     */
    public function createCustomerProfileAddress(ClientData $client): AddressInstance
    {
        /**
         * Rate-limiting request.
         *
         * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
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
     * Create Customer Support Docs.
     *
     * @throws TwilioException
     *
     * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api#14-create-supporting-document-customer_profile_address
     */
    public function createCustomerSupportDocs(
        ClientData $client,
        string $documentName,
        string $documentType,
        array $attributes
    ): SupportingDocumentInstance {
        /**
         * Rate-limiting request.
         *
         * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
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
     * Assign end-user, supporting document, and primary customer profile to the empty starter customer profile that
     * you created.
     *
     * @throws TwilioException
     *
     * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api#15-assign-end-user-supporting-document-and-primary-customer-profile-to-the-empty-starter-customer-profile-that-you-created
     */
    public function attachObjectSidToCustomerProfile(
        ClientData $client,
        string $customerProfileBundleSid,
        string $objectSid
    ): void {
        /**
         * Rate-limiting request.
         *
         * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
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
     * Evaluate the Customer Profile.
     *
     * @throws TwilioException
     *
     * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api#16-evaluate-the-starter-customer-profile
     */
    public function evaluateCustomerProfileBundle(
        ClientData $client,
        string $customerProfileBundleSid
    ): CustomerProfilesEvaluationsInstance {
        /**
         * Rate-limiting request.
         *
         * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
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
     * Submit the Customer Profile for review.
     *
     * @throws TwilioException
     *
     * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api#17-submit-the-starter-customer-profile-for-review
     */
    public function submitCustomerProfileBundle(
        ClientData $client,
        string $customerProfileBundleSid
    ): CustomerProfilesInstance {
        /**
         * Rate-limiting request.
         *
         * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
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
     * Create an empty A2P Starter Trust Bundle.
     *
     * @throws TwilioException
     *
     * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api#22-create-an-empty-a2p-starter-trust-bundle
     */
    public function createEmptyA2PStarterTrustBundle(ClientData $client): TrustProductsInstance
    {
        /**
         * Rate-limiting request.
         *
         * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
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
     * Assign the Starter Customer Profile bundle to the A2P Starter trust bundle.
     *
     * @throws TwilioException
     *
     * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api#23-assign-the-starter-customer-profile-bundle-to-the-a2p-starter-trust-bundle
     */
    public function assignCustomerProfileA2PTrustBundle(
        ClientData $client,
        string $trustBundleSid,
        string $customerProfileSid
    ): void {
        /**
         * Rate-limiting request.
         *
         * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
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
     * Evaluate the A2P Starter Profile Bundle.
     *
     * @throws TwilioException
     *
     * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api#24-evaluate-the-a2p-starter-profile-bundle
     */
    public function evaluateA2PStarterProfileBundle(
        ClientData $client,
        string $trustBundleSid
    ): TrustProductsEvaluationsInstance {
        /**
         * Rate-limiting request.
         *
         * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
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
     * Submit the A2P Starter Profile bundle for review.
     *
     * @throws TwilioException
     *
     * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api#25-submit-the-a2p-starter-profile-bundle-for-review
     */
    public function submitA2PProfileBundle(ClientData $client, string $trustBundleSid): TrustProductsInstance
    {
        /**
         * Rate-limiting request.
         *
         * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
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
     * Create an A2P Brand.
     *
     * @throws TwilioException
     *
     * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api#3-create-an-a2p-brand
     */
    public function createA2PBrand(
        ClientData $client,
        string $a2PProfileBundleSid,
        string $customerProfileBundleSid
    ): BrandRegistrationInstance {
        /**
         * Rate-limiting request.
         *
         * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
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
     * Create a Messaging Service.
     *
     * @throws TwilioException
     *
     * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api#4-create-a-messaging-service
     */
    public function createMessagingService(ClientData $client): ServiceInstance
    {
        /**
         * Rate-limiting request.
         *
         * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
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
     * Add a Phone Number to a Messaging Service.
     *
     * @throws TwilioException
     *
     * @see https://www.twilio.com/docs/messaging/services/api/phonenumber-resource#create-a-phonenumber-resource-add-a-phone-number-to-a-messaging-service
     */
    public function addPhoneNumberToMessagingService(ClientData $client, string $messageServiceSid): PhoneNumberInstance
    {
        /**
         * Rate-limiting request.
         *
         * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
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
     * Create an A2P Messaging Campaign use case.
     *
     * @throws TwilioException
     *
     * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api#51-create-an-a2p-messaging-campaign-use-case
     */
    public function createA2PMessagingCampaignUseCase(
        ClientData $client,
        string $a2PBrandSid,
        string $messagingServiceSid
    ): UsAppToPersonInstance {
        /**
         * Rate-limiting request.
         *
         * @see https://www.twilio.com/docs/sms/a2p-10dlc/isv-starter-api
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

        $regex = '/[^a-zA-Z\d ]/';

        if ($removeSpaces && $removeLetters) {
            $regex = '/\D/';
        } elseif ($removeSpaces) {
            $regex = '/[^a-zA-Z\d]/';
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
