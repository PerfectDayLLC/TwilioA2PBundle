<?php

namespace PerfectDayLlc\TwilioA2PBundle\Console;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder as DatabaseBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PerfectDayLlc\TwilioA2PBundle\Contracts\ClientRegistrationHistory as ClientRegistrationHistoryContract;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Facades\EntityRegistrator as EntityRegistratorFacade;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory;
use stdClass;
use Symfony\Component\Console\Helper\ProgressBar;
use Throwable;

class FixCustomerProfileEvaluationProcess extends AbstractCommand
{
    protected $signature = 'a2p:fix-customer-profile-evaluation-process {entity?}';

    protected $description = 'Twilio - Fix the customer profile evaluation process';

    public function handle(): int
    {
        $historiesWithErrors = DB::table(
            // This next custom query return the latest history, this is the only way on MySQL <= 5.7 to achieve it
            ClientRegistrationHistory::query()
                ->select([
                    'id',
                    'request_type',
                    'error',
                    'response',
                    'status',
                    ClientRegistrationHistory::CREATED_AT,
                    DB::raw('@row_number:=CASE WHEN @entity = `entity_id` THEN @row_number + 1 ELSE 1 END AS `num`'),
                    DB::raw('@entity:=entity_id entity_id'),
                ])
                ->fromRaw((new ClientRegistrationHistory)->getTable().', (SELECT @row_number:=0) AS t')
                ->having('num', 1)
                ->orderByRaw('entity_id, created_at DESC'),
            $relativeTableAlias = 'latest_type'
        )
            ->select('id')
            ->whereExists(function (DatabaseBuilder $query) use ($relativeTableAlias) {
                /** @var class-string<Model&ClientRegistrationHistoryContract> $entityModelString */
                $entityModelString = config('twilioa2pbundle.entity_model');

                $entityInstance = new $entityModelString;

                return $query->from($entityInstance->getTable(), $tableAlias = 'entity_table')
                    ->where("$relativeTableAlias.entity_id", DB::raw($tableAlias.'.'.$entityInstance->getKeyName()))
                    ->when(
                        $this->argument('entity'),
                        fn (EloquentBuilder $query, $entityId) => $query->where("$tableAlias.entity_id", $entityId)
                    );
            })
            ->where("$relativeTableAlias.error", true)
            ->where("$relativeTableAlias.request_type", 'evaluateCustomerProfileBundle')
            ->where("$relativeTableAlias.status", Status::BUNDLES_NONCOMPLIANT)
            ->orderByDesc("$relativeTableAlias.id");

        $this->withProgressBar(
            $historiesWithErrors->count(),
            function (ProgressBar $bar) use ($historiesWithErrors) {
                foreach ($historiesWithErrors->cursor() as $history) {
                    /** @var stdClass $history */
                    $history = ClientRegistrationHistory::find($history->id);

                    try {
                        /** @var ClientRegistrationHistory $history */
                        EntityRegistratorFacade::fixCustomerProfileForEvaluation($history);
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
