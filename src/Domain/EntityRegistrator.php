<?php

namespace PerfectDayLlc\TwilioA2PBundle\Domain;

use Exception;
use Illuminate\Support\Facades\Log;
use PerfectDayLlc\TwilioA2PBundle\Contracts\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Entities\RegisterClientsMethodsSignatureEnum;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrandStarter\AssignCustomerProfileA2PTrustBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrandStarter\CreateEmptyA2PStarterTrustBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrandStarter\EvaluateA2PStarterProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\A2PBrandStarter\SubmitA2PProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateA2PBrand;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateA2PSmsCampaignUseCase;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateMessagingService;
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
    public const CREATE_A2P_BRAND_JOB_QUEUE = 'create-a2p-brand-job';

    public const CREATE_A2P_SMS_CAMPAIGN_USE_CASE_JOB_QUEUE = 'create-a2p-sms-campaign-use-case-job';

    public const CREATE_MESSAGING_SERVICE_QUEUE = 'create-messaging-service';

    public const SUBMIT_A2P_PROFILE_BUNDLE_QUEUE = 'submit-a2p-profile-bundle';

    public const SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE = 'submit-customer-profile-bundle';

    public static function processEntity(ClientRegistrationHistory $entity): void
    {
        $service = resolve(RegisterService::class);

        try {
            /**
             * @var ClientRegistrationHistoryModel|null $history
             */
            $history = $entity->twilioA2PClientRegistrationHistories()->where('error', false)->latest()->first();

            $client = $entity->getClientData();

            switch ($history->request_type ?? null) {
                case null:
                    dispatch(new CreateEmptyCustomerProfileStarterBundle($service, $client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'createEmptyCustomerProfileStarterBundle':
                    dispatch(new CreateEndUserCustomerProfileInfo($service, $client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'createEndUserCustomerProfileInfo':
                    dispatch(new CreateCustomerProfileAddress($service, $client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'createCustomerProfileAddress':
                    dispatch(new CreateCustomerSupportDocs($service, $client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'createCustomerSupportDocs':
                    dispatch(new AttachObjectSidToCustomerProfile($service, $client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'attachObjectSidToCustomerProfile':
                    dispatch(new EvaluateCustomerProfileBundle($service, $client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'evaluateCustomerProfileBundle':
                    dispatch(new SubmitCustomerProfileBundle($service, $client))
                        ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                    return;
                case RegisterClientsMethodsSignatureEnum::SUBMIT_CUSTOMER_PROFILE_BUNDLE:
                    dispatch(new CreateEmptyA2PStarterTrustBundle($service, $client))
                        ->onQueue(static::SUBMIT_A2P_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'createEmptyA2PStarterTrustBundle':
                    dispatch(new AssignCustomerProfileA2PTrustBundle($service, $client, $history->bundle_sid ?? null))
                        ->onQueue(static::SUBMIT_A2P_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'assignCustomerProfileA2PTrustBundle':
                    dispatch(new EvaluateA2PStarterProfileBundle($service, $client))
                        ->onQueue(static::SUBMIT_A2P_PROFILE_BUNDLE_QUEUE);

                    return;
                case 'evaluateA2PStarterProfileBundle':
                    dispatch(new SubmitA2PProfileBundle($service, $client))
                        ->onQueue(static::SUBMIT_A2P_PROFILE_BUNDLE_QUEUE);

                    return;
                case RegisterClientsMethodsSignatureEnum::SUBMIT_A2P_PROFILE_BUNDLE:
                    dispatch(new CreateA2PBrand($service, $client))
                        ->onQueue(static::CREATE_A2P_BRAND_JOB_QUEUE);

                    return;
                case RegisterClientsMethodsSignatureEnum::CREATE_A2P_BRAND:
                    dispatch(new CreateMessagingService($service, $client))
                        ->onQueue(static::CREATE_MESSAGING_SERVICE_QUEUE);

                    return;
                case RegisterClientsMethodsSignatureEnum::ADD_PHONE_NUMBER_TO_MESSAGING_SERVICE:
                    if ($history->created_at->diffInDays() < 1) {
                        return;
                    }

                    dispatch(new CreateA2PSmsCampaignUseCase($service, $client))
                        ->onQueue(static::CREATE_A2P_SMS_CAMPAIGN_USE_CASE_JOB_QUEUE);

                    return;
                default:
                    throw new Exception("Unknown Request Type: '".$history->request_type ?? 'null'."'");
            }
        } catch (Throwable $exception) {
            Log::error(
                "Error when processing an entity: {$exception->getMessage()}",
                $entity->toArray()
            );
        }
    }
}
