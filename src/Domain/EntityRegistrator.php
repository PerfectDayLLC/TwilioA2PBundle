<?php

namespace PerfectDayLlc\TwilioA2PBundle\Domain;

use Exception;
use Illuminate\Support\Facades\Log;
use PerfectDayLlc\TwilioA2PBundle\Contracts\ClientRegistrationHistory as ClientRegistrationHistoryContract;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand\CheckA2PBrandStatus;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand\CreateA2PBrand;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand\Starter\AssignCustomerProfileA2PTrustBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand\Starter\CreateEmptyA2PStarterTrustBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand\Starter\EvaluateA2PStarterProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrand\Starter\SubmitA2PProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateA2PSmsCampaignUseCase;
use PerfectDayLlc\TwilioA2PBundle\Jobs\MessagingService\AddPhoneNumberToMessagingService;
use PerfectDayLlc\TwilioA2PBundle\Jobs\MessagingService\CreateMessagingService;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\AttachObjectSidToCustomerProfile;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\CreateCustomerProfileAddress;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\CreateCustomerSupportDocs;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\CreateEmptyCustomerProfileStarterBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\CreateEndUserCustomerProfileInfo;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\EvaluateCustomerProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\FixCustomerProfileEvaluationProcess;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\SubmitCustomerProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory as ClientRegistrationHistoryModel;
use Throwable;

class EntityRegistrator
{
    public const SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE = 'submit-customer-profile-bundle';

    public const SUBMIT_A2P_PROFILE_BUNDLE_QUEUE = 'submit-a2p-profile-bundle';

    public const CREATE_A2P_BRAND_JOB_QUEUE = 'create-a2p-brand-job';

    public const CREATE_A2P_SMS_CAMPAIGN_USE_CASE_JOB_QUEUE = 'create-a2p-sms-campaign-use-case-job';

    public const CREATE_MESSAGING_SERVICE_QUEUE = 'create-messaging-service';

    public const LAST_REQUEST_TYPE = 'createA2PMessagingCampaignUseCase';

    public function processEntity(ClientRegistrationHistoryContract $entity): void
    {
        try {
            /**
             * @var ClientRegistrationHistoryModel|null $history
             */
            $history = $entity->twilioA2PClientRegistrationHistories()->where('error', false)->latest()->first();

            $client = $entity->getClientData();

            switch ($history->request_type ?? null) {
                case null:
                    dispatch(new CreateEmptyCustomerProfileStarterBundle($client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'createEmptyCustomerProfileStarterBundle':
                    dispatch(new CreateEndUserCustomerProfileInfo($client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'createEndUserCustomerProfileInfo':
                    dispatch(new CreateCustomerProfileAddress($client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'createCustomerProfileAddress':
                    dispatch(new CreateCustomerSupportDocs($client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'createCustomerSupportDocs':
                    dispatch(new AttachObjectSidToCustomerProfile($client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'attachObjectSidToCustomerProfile':
                    dispatch(new EvaluateCustomerProfileBundle($client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'evaluateCustomerProfileBundle':
                    dispatch(new SubmitCustomerProfileBundle($client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'submitCustomerProfileBundle':
                    dispatch(new CreateEmptyA2PStarterTrustBundle($client))
                        ->onQueue(static::SUBMIT_A2P_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'createEmptyA2PStarterTrustBundle':
                    dispatch(new AssignCustomerProfileA2PTrustBundle($client, $history->bundle_sid ?? null))
                        ->onQueue(static::SUBMIT_A2P_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'assignCustomerProfileA2PTrustBundle':
                    dispatch(new EvaluateA2PStarterProfileBundle($client))
                        ->onQueue(static::SUBMIT_A2P_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'evaluateA2PStarterProfileBundle':
                    dispatch(new SubmitA2PProfileBundle($client))
                        ->onQueue(static::SUBMIT_A2P_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'submitA2PProfileBundle':
                    dispatch(new CreateA2PBrand($client))
                        ->onQueue(static::CREATE_A2P_BRAND_JOB_QUEUE);

                    return;
                case 'createA2PBrand':
                    dispatch(new CreateMessagingService($client))
                        ->onQueue(static::CREATE_MESSAGING_SERVICE_QUEUE);

                    return;
                case 'createMessagingService':
                    dispatch(new AddPhoneNumberToMessagingService($client))
                        ->onQueue(static::CREATE_MESSAGING_SERVICE_QUEUE);

                    return;
                case 'addPhoneNumberToMessagingService':
                    if ($history->created_at->diffInDays() < 1) {
                        return;
                    }

                    dispatch(new CreateA2PSmsCampaignUseCase($client))
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

    public function checkBrandRegistrationStatus(ClientRegistrationHistoryContract $entity): void
    {
        dispatch(new CheckA2PBrandStatus($entity->getClientData()))
            ->onQueue(static::CREATE_A2P_BRAND_JOB_QUEUE);
    }

    public function fixCustomerProfileForEvaluation(ClientRegistrationHistory $history): void
    {
        dispatch(new FixCustomerProfileEvaluationProcess($history->entity->getClientData()))
            ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);
    }
}
