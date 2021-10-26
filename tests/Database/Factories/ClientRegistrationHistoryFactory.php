<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\WithFaker;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\ClientRegistrationHistory;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;

class ClientRegistrationHistoryFactory extends Factory
{
    use WithFaker;

    protected $model = ClientRegistrationHistory::class;

    public function definition(): array
    {
        return [
            'entity_id' => Entity::factory(),
            'request_type' => $this->faker()->word(),
            'bundle_sid' => $this->faker()->regexify('BU[A-Z0-9]{16}'),
            'object_sid' => $this->faker()->regexify('BU[A-Z0-9]{16}'),
            'status' => $this->faker()->randomElement(Status::getConstants()),
            'response' => [],
            'error' => false,
        ];
    }
}
