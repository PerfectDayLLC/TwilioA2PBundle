<?php

namespace PerfectDayLlc\TwilioA2PBundle\Facades;

use Illuminate\Support\Facades\Facade;
use PerfectDayLlc\TwilioA2PBundle\Domain\EntityRegistrator as EntityRegistratorDomain;

/**
 * @mixin EntityRegistratorDomain
 */
class EntityRegistratorFacade extends Facade
{
    /**
     * @return class-string
     */
    protected static function getFacadeAccessor(): string
    {
        return EntityRegistratorDomain::class;
    }
}
