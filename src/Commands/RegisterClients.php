<?php

namespace PerfectDayLlc\TwilioA2PBundle\Commands;

use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Jobs\CreateA2PSmsCampaignUseCase;
use PerfectDayLlc\TwilioA2PBundle\Jobs\SubmitA2PTrustBundle;
use PerfectDayLlc\TwilioA2PBundle\Jobs\SubmitCustomerProfileBundle;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Entities\ClientOwnerData;
use App\Models\Dealer;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class RegisterClients extends Command
{
    protected $signature = 'a2p:client-register';

    protected $description = 'Twilio - Register all companies for the A2P 10DLC US carrier standard compliance';

    public function handle(): int
    {
        $service = new RegisterService(
            config('services.twilio.sid'),
            config('services.twilio.token'),
            config('services.twilio.primary_customer_profile_sid'), // Look for this SID on Twilio's Unotifi User
            config('services.twilio.customer_profile_policy_sid'),
            config('services.twilio.a2p_profile_policy_sid'),
            config('services.twilio.profile_policy_type')
        );

        // Get Unregistered Clients query
        $unregisteredClients = Dealer::with('instance', 'twilioA2PCustomerRegistrationHistories')
            ->whereHas('twilioA2PCustomerRegistrationHistories', function (Builder $query) {
                return $query->whereNull('status')->orWhereIn('status', [
                    Status::BUNDLES_PENDING_REVIEW,
                    Status::BUNDLES_IN_REVIEW,
                    Status::BUNDLES_TWILIO_APPROVED,
                    Status::BRAND_PENDING,
                    Status::EXECUTED,
                ]);
            })
            ->orWhereDoesntHave('twilioA2PCustomerRegistrationHistories');

        $clientOwnerData = new ClientOwnerData(
            'Account',
            'Owner',
            'account.owner_'.uniqid(microtime(), true).'@unotifi.com'
        );
        $clientPhone = '+13606241337';

        foreach ($unregisteredClients->cursor() as $dealer) {
            /** @var Dealer $dealer */
            /** @var ClientRegistrationHistory|null $clientRegistrationHistory */
            $clientRegistrationHistory = $dealer->twilioA2PCustomerRegistrationHistories()->latest()->first();

            $client = new ClientData(
                $dealer->iddealer,
                $dealer->name,
                $dealer->address_line_1,
                $dealer->city_name,
                $dealer->state_code,
                $dealer->zip_code,
                $dealer->country_code,
                $dealer->voip_phone_number,
                $dealer->voip_phone_number_sid,
                $dealer->instance->generateUnotifiURL(),
                $clientOwnerData->getFirstname(),
                $clientOwnerData->getLastname(),
                $clientOwnerData->getEmail(),
                $clientPhone,
                $dealer->iddealer,
                $clientOwnerData,
                $clientRegistrationHistory->id ?? null,
                $clientRegistrationHistory->status ?? null
            );

            // Create and Submit Customer Profile if company has never been registered
            if (!$client->getCustomerRegistrationHistoryStatus()) {
                dispatch(
                    (new SubmitCustomerProfileBundle($service, $client))
                        ->onQueue('submit-customer-profile-bundle')
                );

                continue;
            }

            /**
             * If `submitCustomerProfileBundle` has been submitted for this client at least a day ago,
             * then Submit A2P Profile Bundle, Create Brand, and Create Messaging Service.
             */
            if ($clientRegistrationHistory->request_type === 'submitCustomerProfileBundle' &&
                $clientRegistrationHistory->created_at->diffInDays() >= 1
            ) {
                // Create Submit A2P Profile Job
                dispatch(
                    (new SubmitA2PTrustBundle(
                        $service,
                        $client,
                        $clientRegistrationHistory->bundle_sid,
                        $SMSCallbackURL = $clientRegistrationHistory->dealer->SMSCallbackURL(),
                        $SMSCallbackURL,
                        true,
                        true
                    ))
                        ->onQueue('submit-a2p-profile-bundle')
                );

                continue;
            }

            // If Create A2P SMS Campaign use case
            if (in_array($clientRegistrationHistory->request_type, ['createA2PBrand', 'createMessagingService', 'addPhoneNumberToMessagingService']) &&
                $clientRegistrationHistory->created_at->diffInDays() >= 1
            ) {
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
