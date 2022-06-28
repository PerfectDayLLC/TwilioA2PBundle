<?php

namespace PerfectDayLlc\TwilioA2PBundle\Console;

use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use PerfectDayLlc\TwilioA2PBundle\Contracts\ClientRegistrationHistory as ClientRegistrationHistoryContract;
use PerfectDayLlc\TwilioA2PBundle\Domain\EntityRegistrator;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;

class RegisterClients extends Command
{
    protected $signature = 'a2p:client-register';

    protected $description = 'Twilio - Register all companies for the A2P 10DLC US carrier standard compliance';

    public function handle(): int
    {
        /** @var class-string<ClientRegistrationHistoryContract|Model> $entityNamespaceModel */
        $entityNamespaceModel = config('twilioa2pbundle.entity_model');

        // Get Unregistered Clients query
        $unregisteredClients = $entityNamespaceModel::query()->where(function (Builder $query) {
            return $query->whereHas(
                'twilioA2PClientRegistrationHistories',
                function (Builder $query) {
                    return $query->whereNull('status')
                        ->orWhereIn('status', Status::getOngoingA2PStatuses())
                        ->where('error', false); // TODO: Need to add tests for this condition
                }
            )
                ->orWhereDoesntHave('twilioA2PClientRegistrationHistories');
        });

        $unregisteredClients = $entityNamespaceModel::customTwilioA2PFiltering($unregisteredClients);

        foreach ($unregisteredClients->cursor() as $entity) {
            /** @var ClientRegistrationHistoryContract $entity */
            EntityRegistrator::processEntity($entity);
        }

        return 0;
    }
}
