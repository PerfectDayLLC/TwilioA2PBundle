<?php

namespace PerfectDayLlc\TwilioA2PBundle\Console;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use PerfectDayLlc\TwilioA2PBundle\Contracts\ClientRegistrationHistory as ClientRegistrationHistoryContract;
use PerfectDayLlc\TwilioA2PBundle\Facades\EntityRegistratorFacade as EntityRegistratorFacade;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

class CheckBrandStatus extends AbstractCommand
{
    protected $signature = 'a2p:check-brand-status {entity?}';

    protected $description = 'Twilio - Check Brand Registration Status';

    public function handle(): int
    {
        /** @var class-string<ClientRegistrationHistoryContract|Model> $entityNamespaceModel */
        $entityNamespaceModel = config('twilioa2pbundle.entity_model');

        $unregisteredClients = $entityNamespaceModel::query()
            ->whereHas(
                'twilioA2PClientRegistrationHistories',
                fn (Builder $query) => $query->where('status', 'pending')->where('request_type', 'createA2PBrand')
            )
            ->when(
                $this->argument('entity'),
                fn (Builder $query, $entityId) => $query->whereKey($entityId)
            );

        $this->withProgressBar(
            $unregisteredClients->count(),
            function (ProgressBar $bar) use ($unregisteredClients) {
                foreach ($unregisteredClients->cursor() as $entity) {
                    try {
                        /** @var ClientRegistrationHistoryContract $entity */
                        EntityRegistratorFacade::checkBrandRegistrationStatus($entity);
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
