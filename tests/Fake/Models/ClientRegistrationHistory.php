<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use PerfectDayLlc\TwilioA2PBundle\Models\ClientRegistrationHistory as BaseClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Tests\Database\Factories\ClientRegistrationHistoryFactory;

class ClientRegistrationHistory extends BaseClientRegistrationHistory
{
    use HasFactory;

    public static function newFactory(): ClientRegistrationHistoryFactory
    {
        return new ClientRegistrationHistoryFactory();
    }
}
