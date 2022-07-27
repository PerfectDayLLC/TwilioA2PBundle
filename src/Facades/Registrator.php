<?php

namespace PerfectDayLlc\TwilioA2PBundle\Facades;

use Illuminate\Support\Facades\Facade;
use PerfectDayLlc\TwilioA2PBundle\Services\Registrator as RegistratorService;

/**
 * @mixin RegistratorService
 */
class Registrator extends Facade
{
    /**
     * @return class-string
     */
    protected static function getFacadeAccessor(): string
    {
        return RegistratorService::class;
    }
}
