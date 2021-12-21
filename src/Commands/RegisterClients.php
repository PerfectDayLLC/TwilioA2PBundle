<?php

namespace PerfectDayLlc\TwilioA2PBundle\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PerfectDayLlc\TwilioA2PBundle\Contracts\ClientRegistrationHistory as ClientRegistrationHistoryContract;
use PerfectDayLlc\TwilioA2PBundle\Entities\RegisterClientsMethodsSignatureEnum;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateA2PBrand;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateA2PSmsCampaignUseCase;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateMessagingService;
use PerfectDayLlc\TwilioA2PBundle\Jobs\SubmitA2PTrustBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\SubmitCustomerProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;

class RegisterClients extends Command
{
    protected $signature = 'a2p:client-register';

    protected $description = 'Twilio - Register all companies for the A2P 10DLC US carrier standard compliance';

    public function handle(RegisterService $service): int
    {
        /** @var Model $entityNamespaceModel */
        $entityNamespaceModel = config('twilioa2pbundle.entity_model');

        // Get Unregistered Clients query
        $unregisteredClients = $entityNamespaceModel::with('twilioA2PClientRegistrationHistories')
            ->whereHas('twilioA2PClientRegistrationHistories', function (Builder $query) {
                return $query->whereNull('status')
                    ->orWhereIn('status', Status::getOngoingA2PStatuses())
                    ->where('error', false);
            })
            ->orWhereDoesntHave('twilioA2PClientRegistrationHistories');

        foreach ($unregisteredClients->cursor() as $entity) {
            /** @var ClientRegistrationHistory|null $history */
            $history = $entity->twilioA2PClientRegistrationHistories->last();

            /** @var ClientRegistrationHistoryContract $entity */
            $client = $entity->getClientData();

            // Create and Submit Customer Profile if company has never been registered
            if (! ($history->status ?? false)) {
                dispatch(new SubmitCustomerProfileBundle($service, $client))
                    ->onQueue('submit-customer-profile-bundle');

                continue;
            }

            /**
             * If `submitCustomerProfileBundle` has been submitted for this client at least a day ago,
             * then Submit A2P Profile Bundle, Create Brand, and Create Messaging Service.
             *
             * TODO: checking the documentation, it does not explicitly say if we should continue or not
             * so I am assuming we need to receive a Status::BUNDLES_TWILIO_APPROVED to continue here.
             */
            if ($history->request_type ===
                RegisterClientsMethodsSignatureEnum::SUBMIT_CUSTOMER_PROFILE_BUNDLE
            ) {
                // Create Submit A2P Profile Job
                dispatch(
                    new SubmitA2PTrustBundle(
                        $service,
                        $client,
                        $history->bundle_sid ?? null
                    )
                )
                    ->onQueue('submit-a2p-profile-bundle');

                continue;
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
                    ->onQueue('create-a2p-brand-job');

                continue;
            }

            // If Create A2p Sms Campaign Use Case
            if ($history->request_type ===
                RegisterClientsMethodsSignatureEnum::CREATE_A2P_BRAND
            ) {
                dispatch(new CreateMessagingService($service, $client))
                    ->onQueue('create-messaging-service');

                continue;
            }

            if ($history->created_at->diffInDays() < 1) {
                continue;
            }

            // If Create A2P SMS Campaign use case
            if ($history->request_type ===
                RegisterClientsMethodsSignatureEnum::ADD_PHONE_NUMBER_TO_MESSAGING_SERVICE
            ) {
                //Create A2p Sms Campaign UseCase Job
                dispatch(new CreateA2PSmsCampaignUseCase($service, $client))
                    ->onQueue('create-a2p-sms-campaign-use-case-job');
            }
        }

        return 0;
    }
}
