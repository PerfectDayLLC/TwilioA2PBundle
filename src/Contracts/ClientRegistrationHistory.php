<?php

namespace PerfectDayLlc\TwilioA2PBundle\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;

interface ClientRegistrationHistory
{
    public static function customTwilioA2PFiltering(Builder $query): Builder;

    public function twilioA2PClientRegistrationHistories(): HasMany;

    public function getClientData(): ClientData;
}
