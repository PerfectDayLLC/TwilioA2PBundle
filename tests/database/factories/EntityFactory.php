<?php

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\Entity;

/** @var Factory $factory */
$factory->define(Entity::class, function (Faker $faker) {
    return [
        'company_name' => $faker->company(),
        'address' => $faker->streetAddress(),
        'city' => $faker->city(),
        'state' => $faker->stateAbbr(),
        'zip' => $faker->postcode(),
        'country' => 'US',
        'phone_number' => $faker->e164PhoneNumber(),
        'twilio_phone_number_sid' => $faker->regexify('PN[A-Z0-9]{16}'),
        'website' => $faker->url(),
        'contact_first_name' => $faker->firstName(),
        'contact_last_name' => $faker->lastName(),
        'contact_email' => $faker->email(),
        'contact_phone' => $faker->e164PhoneNumber(),
        'webhook_url' => $faker->url(),
        'fallback_webhook_url' => $faker->url(),
    ];
});
