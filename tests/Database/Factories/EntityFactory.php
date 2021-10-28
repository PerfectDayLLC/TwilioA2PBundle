<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\WithFaker;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;

class EntityFactory extends Factory
{
    use WithFaker;

    protected $model = Entity::class;

    public function definition(): array
    {
        return [
            'company_name' => $this->faker()->company(),
            'address' => $this->faker()->streetAddress(),
            'city' => $this->faker()->city(),
            'state' => $this->faker()->stateAbbr(),
            'zip' => $this->faker()->postcode(),
            'country' => 'US',
            'phone_number' => $this->faker()->e164PhoneNumber(),
            'twilio_phone_number_sid' => $this->faker()->regexify('PN[A-Z0-9]{16}'),
            'website' => $this->faker()->url(),
            'contact_first_name' => $this->faker()->firstName(),
            'contact_last_name' => $this->faker()->lastName(),
            'contact_email' => $this->faker()->email(),
            'contact_phone' => $this->faker()->e164PhoneNumber(),
            'webhook_url' => $this->faker()->url(),
            'fallback_webhook_url' => $this->faker()->url(),
        ];
    }
}
