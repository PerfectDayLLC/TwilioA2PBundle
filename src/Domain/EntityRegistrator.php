<?php

namespace PerfectDayLlc\TwilioA2PBundle\Domain;

use Illuminate\Support\Facades\Log;
use PerfectDayLlc\TwilioA2PBundle\Contracts\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Entities\RegisterClientsMethodsSignatureEnum;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateA2PBrand;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateA2PSmsCampaignUseCase;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateMessagingService;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\CreateCustomerProfileAddress;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\CreateCustomerSupportDocs;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\CreateEmptyCustomerProfileStarterBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\StarterCustomerProfile\CreateEndUserCustomerProfileInfo;
use PerfectDayLlc\TwilioA2PBundle\Jobs\SubmitA2PTrustBundle;
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
             * @var \PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory|null $history
             */
            $history = $entity->twilioA2PClientRegistrationHistories()->where('error', false)->latest()->first();

            $client = $entity->getClientData();

            // Create and Submit Customer Profile if company has never been registered
            if (! ($history->status ?? false)) {
                dispatch(new CreateEmptyCustomerProfileStarterBundle($service, $client))
                    ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                return;
            } elseif ($history->request_type === 'createEmptyCustomerProfileStarterBundle') {
                dispatch(new CreateEndUserCustomerProfileInfo($service, $client))
                    ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                return;
            } elseif ($history->request_type === 'createEndUserCustomerProfileInfo') {
                dispatch(new CreateCustomerProfileAddress($service, $client))
                    ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                return;
            } elseif ($history->request_type === 'createCustomerProfileAddress') {
                dispatch(new CreateCustomerSupportDocs($service, $client))
                    ->onQueue(static::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

                return;
            }

            /**
             * If `submitCustomerProfileBundle` has been submitted for this client at least a day ago,
             * then Submit A2P Profile Bundle, Create Brand, and Create Messaging Service.
             *
             * TODO: checking the documentation, it does not explicitly say if we should continue or not
             * so I am assuming we need to receive a Status::BUNDLES_TWILIO_APPROVED to continue here.
             */
            if ($history->request_type === RegisterClientsMethodsSignatureEnum::SUBMIT_CUSTOMER_PROFILE_BUNDLE) {
                // Create Submit A2P Profile Job
                dispatch(
                    new SubmitA2PTrustBundle(
                        $service,
                        $client,
                        $history->bundle_sid ?? null
                    )
                )
                    ->onQueue(static::SUBMIT_A2P_PROFILE_BUNDLE_QUEUE);

                return;
            }

            /**
             * If Create A2p Sms Campaign Use Case.
             *
             * Documentation says that after previous step was done (Status::BUNDLES_PENDING_REVIEW sent)
             * we can immediately create the A2P Brand.
             */
            if ($history->request_type ===
                RegisterClientsMethodsSignatureEnum::SUBMIT_A2P_PROFILE_BUNDLE
            ) {
                dispatch(new CreateA2PBrand($service, $client))
                    ->onQueue(static::CREATE_A2P_BRAND_JOB_QUEUE);

                return;
            }

            // If Create A2p Sms Campaign Use Case
            if ($history->request_type ===
                RegisterClientsMethodsSignatureEnum::CREATE_A2P_BRAND
            ) {
                dispatch(new CreateMessagingService($service, $client))
                    ->onQueue(static::CREATE_MESSAGING_SERVICE_QUEUE);

                return;
            }

            if ($history->created_at->diffInDays() < 1) {
                return;
            }

            // If Create A2P SMS Campaign use case
            if ($history->request_type ===
                RegisterClientsMethodsSignatureEnum::ADD_PHONE_NUMBER_TO_MESSAGING_SERVICE
            ) {
                //Create A2p Sms Campaign UseCase Job
                dispatch(new CreateA2PSmsCampaignUseCase($service, $client))
                    ->onQueue(static::CREATE_A2P_SMS_CAMPAIGN_USE_CASE_JOB_QUEUE);
            }
        } catch (Throwable $exception) {
            Log::error(
                "Error when processing an entity: {$exception->getMessage()}",
                $entity->toArray()
            );
        }
    }
}
