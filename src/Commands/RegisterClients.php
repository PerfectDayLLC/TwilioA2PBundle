<?php

namespace PerfectDayLlc\TwilioA2PBundle\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
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
use Throwable;

class RegisterClients extends Command
{
    public const CREATE_A2P_BRAND_JOB_QUEUE = 'create-a2p-brand-job';

    public const CREATE_A2P_SMS_CAMPAIGN_USE_CASE_JOB_QUEUE = 'create-a2p-sms-campaign-use-case-job';

    public const CREATE_MESSAGING_SERVICE_QUEUE = 'create-messaging-service';

    public const SUBMIT_A2P_PROFILE_BUNDLE_QUEUE = 'submit-a2p-profile-bundle';

    public const SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE = 'submit-customer-profile-bundle';

    protected $signature = 'a2p:client-register';

    protected $description = 'Twilio - Register all companies for the A2P 10DLC US carrier standard compliance';

    public function handle(RegisterService $service): int
    {
        /** @var Model|ClientRegistrationHistoryContract|class-string $entityNamespaceModel */
        $entityNamespaceModel = config('twilioa2pbundle.entity_model');

        // Get Unregistered Clients query
        $unregisteredClients = $entityNamespaceModel::query()->where(function (Builder $query) {
            return $query->whereHas(
                'twilioA2PClientRegistrationHistories',
                function (Builder $query) {
                    return $query->whereNull('status')
                        ->orWhereIn('status', Status::getOngoingA2PStatuses())
                        ->where('error', false);
                }
            )
                ->orWhereDoesntHave('twilioA2PClientRegistrationHistories');
        });

        $unregisteredClients = $entityNamespaceModel::customTwilioA2PFiltering($unregisteredClients);

        foreach ($unregisteredClients->cursor() as $entity) {
            try {
                /**
                 * @var ClientRegistrationHistoryContract $entity
                 * @var ClientRegistrationHistory|null $history
                 */
                $history = $entity->twilioA2PClientRegistrationHistories()->where('error', false)->latest()->first();

                /** @var ClientRegistrationHistoryContract $entity */
                $client = $entity->getClientData();

                // Create and Submit Customer Profile if company has never been registered
                if (! ($history->status ?? false)) {
                    dispatch(new SubmitCustomerProfileBundle($service, $client))
                        ->onQueue(self::SUBMIT_CUSTOMER_PROFILE_BUNDLE_QUEUE);

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
                        ->onQueue(self::SUBMIT_A2P_PROFILE_BUNDLE_QUEUE);

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
                        ->onQueue(self::CREATE_A2P_BRAND_JOB_QUEUE);

                    continue;
                }

                // If Create A2p Sms Campaign Use Case
                if ($history->request_type ===
                    RegisterClientsMethodsSignatureEnum::CREATE_A2P_BRAND
                ) {
                    dispatch(new CreateMessagingService($service, $client))
                        ->onQueue(self::CREATE_MESSAGING_SERVICE_QUEUE);

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
                        ->onQueue(self::CREATE_A2P_SMS_CAMPAIGN_USE_CASE_JOB_QUEUE);
                }
            } catch (Throwable $exception) {
                Log::error(
                    "Error when processing an entity: {$exception->getMessage()}",
                    $entity->toArray()
                );

                continue;
            }
        }

        return 0;
    }
}
