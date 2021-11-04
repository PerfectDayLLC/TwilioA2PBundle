<?php

namespace PerfectDayLlc\TwilioA2PBundle\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PerfectDayLlc\TwilioA2PBundle\Contracts\ClientRegistrationHistory as ClientRegistrationHistoryContract;
use PerfectDayLlc\TwilioA2PBundle\Entities\RegisterClientsMethodsSignatureEnum;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateA2PSmsCampaignUseCase;
use PerfectDayLlc\TwilioA2PBundle\Jobs\SubmitA2PTrustBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\SubmitCustomerProfileBundle;
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
                    ->orWhereIn('status', Status::getOngoingA2PStatuses());
            })
            ->orWhereDoesntHave('twilioA2PClientRegistrationHistories');

        foreach ($unregisteredClients->cursor() as $entity) {
            /** @var ClientRegistrationHistoryContract $entity */
            $client = $entity->getClientData();

            // Create and Submit Customer Profile if company has never been registered
            if (! ($client->getClientRegistrationHistoryModel()->status ?? false)) {
                dispatch(
                    (new SubmitCustomerProfileBundle($service, $client))
                        ->onQueue('submit-customer-profile-bundle')
                );

                continue;
            }

            if ($client->getClientRegistrationHistoryModel()->created_at->diffInDays() < 1) {
                continue;
            }

            /**
             * If `submitCustomerProfileBundle` has been submitted for this client at least a day ago,
             * then Submit A2P Profile Bundle, Create Brand, and Create Messaging Service.
             */
            if ($client->getClientRegistrationHistoryModel()->request_type ===
                RegisterClientsMethodsSignatureEnum::SUBMIT_CUSTOMER_PROFILE_BUNDLE
            ) {
                // Create Submit A2P Profile Job
                dispatch(
                    (new SubmitA2PTrustBundle(
                        $service,
                        $client,
                        $client->getClientRegistrationHistoryModel()->bundle_sid ?? '',
                        $client->getWebhookUrl(),
                        $client->getFallbackWebhookUrl(),
                        true,
                        true
                    ))
                        ->onQueue('submit-a2p-profile-bundle')
                );

                continue;
            }

            $historyTypeInArray = in_array(
                $client->getClientRegistrationHistoryModel()->request_type,
                [
                    RegisterClientsMethodsSignatureEnum::CREATE_A_2_P_BRAND,
                    RegisterClientsMethodsSignatureEnum::CREATE_MESSAGING_SERVICE,
                    RegisterClientsMethodsSignatureEnum::ADD_PHONE_NUMBER_TO_MESSAGING_SERVICE,
                ]
            );

            // If Create A2P SMS Campaign use case
            if ($historyTypeInArray) {
                //Create A2p Sms Campaign UseCase Job
                dispatch(
                    (new CreateA2PSmsCampaignUseCase($service, $client))
                        ->onQueue('create-a2p-sms-campaign-use-case-job')
                );
            }
        }

        return 0;
    }
}
