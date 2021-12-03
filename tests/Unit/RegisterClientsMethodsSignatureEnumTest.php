<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Unit;

use PerfectDayLlc\TwilioA2PBundle\Entities\RegisterClientsMethodsSignatureEnum;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;
use ReflectionClass;

class RegisterClientsMethodsSignatureEnumTest extends TestCase
{
    public function test_expected_methods_exists_on_register_service(): void
    {
        foreach ((new ReflectionClass(RegisterClientsMethodsSignatureEnum::class))->getConstants() as $method) {
            $this->assertTrue(
                (new ReflectionClass(RegisterService::class))->hasMethod($method),
                "The method '$method' was not found"
            );
        }
    }
}
