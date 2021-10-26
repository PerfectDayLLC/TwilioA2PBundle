<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;

class ClientRegistrationHistoryFactory extends Factory
{
    use WithFaker;

    public function __construct(
        $count = null,
        ?Collection $states = null,
        ?Collection $has = null,
        ?Collection $for = null,
        ?Collection $afterMaking = null,
        ?Collection $afterCreating = null,
        $connection = null)
    {
        parent::__construct($count, $states, $has, $for, $afterMaking, $afterCreating, $connection);

        $this->model = config('twilioa2pbundle.client_registration_history_model');
    }

    public function definition(): array
    {
        return [
            'entity_id' => config('twilioa2pbundle.entity_model')::factory(),
            'request_type' => $this->faker()->word(),
            'bundle_sid' => $this->faker()->regexify('BU[A-Z0-9]{16}'),
            'object_sid' => $this->faker()->regexify('BU[A-Z0-9]{16}'),
            'status' => $this->faker()->randomElement(Status::getConstants()),
            'response' => [],
            'error' => false,
        ];
    }
}
