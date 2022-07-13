<?php

namespace PerfectDayLlc\TwilioA2PBundle\Domain;

use Exception;
use Illuminate\Support\Facades\Log;
use PerfectDayLlc\TwilioA2PBundle\Contracts\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrandStarter\AssignCustomerProfileA2PTrustBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrandStarter\CreateEmptyA2PStarterTrustBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrandStarter\EvaluateA2PStarterProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrandStarter\SubmitA2PProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateA2PBrand;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateA2PSmsCampaignUseCase;
use PerfectDayLlc\TwilioA2PBundle\Jobs\MessagingService\AddPhoneNumberToMessagingService;
use PerfectDayLlc\TwilioA2PBundle\Jobs\MessagingService\CreateMessagingService;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\AttachObjectSidToCustomerProfile;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\CreateCustomerProfileAddress;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\CreateCustomerSupportDocs;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\CreateEmptyCustomerProfileStarterBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\CreateEndUserCustomerProfileInfo;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\EvaluateCustomerProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\SubmitCustomerProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory as ClientRegistrationHistoryModel;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Throwable;

class EntityRegistrator
{
    public const SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE = 'submit-customer-profile-bundle';

    public const SUBMIT_A2P_PROFILE_BUNDLE_QUEUE = 'submit-a2p-profile-bundle';

    public const CREATE_A2P_BRAND_JOB_QUEUE = 'create-a2p-brand-job';

    public const CREATE_A2P_SMS_CAMPAIGN_USE_CASE_JOB_QUEUE = 'create-a2p-sms-campaign-use-case-job';

    public const CREATE_MESSAGING_SERVICE_QUEUE = 'create-messaging-service';

    public const LAST_REQUEST_TYPE = 'createA2PMessagingCampaignUseCase';

    protected RegisterService $service;

    public function __construct(RegisterService $service)
    {
        $this->service = $service;
    }

    public function processEntity(ClientRegistrationHistory $entity): void
    {
        try {
            /**
             * @var ClientRegistrationHistoryModel|null $history
             */
            $history = $entity->twilioA2PClientRegistrationHistories()->where('error', false)->latest()->first();

            $client = $entity->getClientData();

            switch ($history->request_type ?? null) {
                case null:
                    dispatch(new CreateEmptyCustomerProfileStarterBundle($this->service, $client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'createEmptyCustomerProfileStarterBundle':
                    dispatch(new CreateEndUserCustomerProfileInfo($this->service, $client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'createEndUserCustomerProfileInfo':
                    dispatch(new CreateCustomerProfileAddress($this->service, $client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'createCustomerProfileAddress':
                    dispatch(new CreateCustomerSupportDocs($this->service, $client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'createCustomerSupportDocs':
                    dispatch(new AttachObjectSidToCustomerProfile($this->service, $client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'attachObjectSidToCustomerProfile':
                    dispatch(new EvaluateCustomerProfileBundle($this->service, $client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'evaluateCustomerProfileBundle':
                    dispatch(new SubmitCustomerProfileBundle($this->service, $client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'submitCustomerProfileBundle':
                    dispatch(new CreateEmptyA2PStarterTrustBundle($this->service, $client))
                        ->onQueue(static::SUBMIT_A2P_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'createEmptyA2PStarterTrustBundle':
                    dispatch(new AssignCustomerProfileA2PTrustBundle($this->service, $client, $history->bundle_sid ?? null))
                        ->onQueue(static::SUBMIT_A2P_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'assignCustomerProfileA2PTrustBundle':
                    dispatch(new EvaluateA2PStarterProfileBundle($this->service, $client))
                        ->onQueue(static::SUBMIT_A2P_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'evaluateA2PStarterProfileBundle':
                    dispatch(new SubmitA2PProfileBundle($this->service, $client))
                        ->onQueue(static::SUBMIT_A2P_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'submitA2PProfileBundle':
                    dispatch(new CreateA2PBrand($this->service, $client))
                        ->onQueue(static::CREATE_A2P_BRAND_JOB_QUEUE);

                    return;
                case 'createA2PBrand':
                    dispatch(new CreateMessagingService($this->service, $client))
                        ->onQueue(static::CREATE_MESSAGING_SERVICE_QUEUE);

                    return;
                case 'createMessagingService':
                    dispatch(new AddPhoneNumberToMessagingService($this->service, $client))
                        ->onQueue(static::CREATE_MESSAGING_SERVICE_QUEUE);

                    return;
                case 'addPhoneNumberToMessagingService':
                    if ($history->created_at->diffInDays() < 1) {
                        return;
                    }

                    dispatch(new CreateA2PSmsCampaignUseCase($this->service, $client))
                        ->onQueue(static::CREATE_A2P_SMS_CAMPAIGN_USE_CASE_JOB_QUEUE);

                    return;
                case self::LAST_REQUEST_TYPE:
                    // Finished the process.
                    return;
                default:
                    throw new Exception("Unknown Request Type: '".($history->request_type ?? 'null')."'");
            }
        } catch (Throwable $exception) {
            Log::error(
                "Error when processing an entity: {$exception->getMessage()}",
                $entity->toArray()
            );
        }
    }

    public function checkBrandRegistrationStatus(ClientRegistrationHistory $entity): void
    {
    }
}
