<?php

namespace PerfectDayLlc\TwilioA2PBundle\Console;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use PerfectDayLlc\TwilioA2PBundle\Contracts\ClientRegistrationHistory as ClientRegistrationHistoryContract;
use PerfectDayLlc\TwilioA2PBundle\Domain\EntityRegistrator as EntityRegistratorDomain;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Facades\EntityRegistratorFacade;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

class RegisterClients extends AbstractCommand
{
    protected $signature = 'a2p:client-register {entity?}';

    protected $description = 'Twilio - Register all companies for the A2P 10DLC US carrier standard compliance';

    public function handle(): int
    {
        /** @var class-string<ClientRegistrationHistoryContract|Model> $entityNamespaceModel */
        $entityNamespaceModel = config('twilioa2pbundle.entity_model');

        $unregisteredClients = $entityNamespaceModel::query()
            ->where(function (Builder $query) {
                return $query->where(
                    function (Builder $query) {
                        return $query->whereHas(
                            'twilioA2PClientRegistrationHistories',
                            fn (Builder $query) => $query->whereIn('status', Status::getOngoingA2PStatuses())
                                ->where('error', false)
                        )
                            ->whereDoesntHave(
                                'twilioA2PClientRegistrationHistories',
                                fn (Builder $query) => $query->where('request_type', EntityRegistratorDomain::LAST_REQUEST_TYPE)
                            );
                    }
                )
                    ->orWhereDoesntHave('twilioA2PClientRegistrationHistories');
            })
            ->when(
                $this->argument('entity'),
                fn (Builder $query, $entityId) => $query->whereKey($entityId)
            );

        $unregisteredClients = $entityNamespaceModel::customTwilioA2PFiltering($unregisteredClients);

        $this->withProgressBar(
            $unregisteredClients->count(),
            function (ProgressBar $bar) use ($unregisteredClients) {
                foreach ($unregisteredClients->cursor() as $entity) {
                    /** @var ClientRegistrationHistoryContract $entity */
                    if ($entity->twilioA2PClientRegistrationHistories()->exists() &&
                        $entity->twilioA2PClientRegistrationHistories()->latest()->value('error')
                    ) {
                        continue;
                    }

                    try {
                        EntityRegistratorFacade::processEntity($entity);
                    } catch (Throwable $exception) {
                        Log::error($exception->getMessage());
                    } finally {
                        $bar->advance();
                    }
                }
            }
        );

        return 0;
    }
}
