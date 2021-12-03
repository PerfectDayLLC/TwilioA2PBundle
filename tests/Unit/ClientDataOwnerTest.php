<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Unit;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientOwnerData;
use PerfectDayLlc\TwilioA2PBundle\Tests\TestCase;

class ClientDataOwnerTest extends TestCase
{
    public function test_correct_data_is_returned(): void
    {
        $clientOwnerData = new ClientOwnerData(
            $contactName = 'John',
            $contactSurname = 'Doe',
            $contactEmail = 'johndoe@gmail.com',
        );

        $this->assertSame($contactName, $clientOwnerData->getFirstname());
        $this->assertSame($contactSurname, $clientOwnerData->getLastname());
        $this->assertSame($contactEmail, $clientOwnerData->getEmail());
    }
}
