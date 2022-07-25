<?php

namespace PerfectDayLlc\TwilioA2PBundle\Console;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PerfectDayLlc\TwilioA2PBundle\Contracts\ClientRegistrationHistory as ClientRegistrationHistoryContract;
use PerfectDayLlc\TwilioA2PBundle\Facades\EntityRegistrator as EntityRegistratorFacade;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

class FixCustomerProfileEvaluationProcess extends AbstractCommand
{
    protected $signature = 'a2p:fix-customer-profile-evaluation-process {entity?}';

    protected $description = 'Twilio - Fix the customer profile evaluation process';

    public function handle(): int
    {
        /** @var class-string<Model&ClientRegistrationHistoryContract> $entityNamespaceModel */
        $entityNamespaceModel = config('twilioa2pbundle.entity_model');

        $unregisteredClients = $entityNamespaceModel::query()
            ->from(
                ClientRegistrationHistory::query()
                    ->select([
                        'id',
                        'request_type',
                        'error',
                        'response',
                        ClientRegistrationHistory::CREATED_AT,
                        DB::raw('@row_number:=CASE WHEN @entity = `entity_id` THEN @row_number + 1 ELSE 1 END AS `num`'),
                        DB::raw('@entity:=entity_id entity_id'),
                    ])
                    ->fromRaw((new ClientRegistrationHistory)->getTable().', (SELECT @row_number:=0) AS t')
                    ->having('num', 1)
                    ->orderByRaw('?, ? DESC', ['entity_id', ClientRegistrationHistory::CREATED_AT]),
                $relativeTableAlias = 'latest_type'
            )
            ->whereExists(function (DatabaseBuilder $query) use ($relativeTableAlias) {
                /** @var class-string<Model&ClientRegistrationHistoryContract> $entityModelString */
                $entityModelString = config('twilioa2pbundle.entity_model');

                $entityInstance = new $entityModelString;

                return $query->from($entityInstance->getTable(), $tableAlias = 'entity_table')
                    ->where("$relativeTableAlias.entity_id", DB::raw($tableAlias.'.'.$entityInstance->getKeyName()));
            })
            ->where("$relativeTableAlias.error", true)
            ->orderByDesc("$relativeTableAlias.id")
            ->when(
                $this->argument('entity'),
                fn (EloquentBuilder $query, $entityId) => $query->whereKey($entityId)
            );

        // ->whereDoesntHave('twilioA2PClientRegistrationHistories', fn (Builder $query) => $query->where('request_type', EntityRegistratorDomain::LAST_REQUEST_TYPE))

        $this->withProgressBar(
            $unregisteredClients->count(),
            function (ProgressBar $bar) use ($unregisteredClients) {
                foreach ($unregisteredClients->cursor() as $entity) {
                    /** @var ClientRegistrationHistoryContract $entity */
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
