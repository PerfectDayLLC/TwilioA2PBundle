<?php

use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factory;
use PerfectDayLlc\TwilioA2PBundle\Entities\Status;
use PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models\ClientRegistrationHistory;

/** @var Factory $factory */
$factory->define(ClientRegistrationHistory::class, function (Faker $faker) {
    return [
        'entity_id' => factory(config('twilioa2pbundle.entity_model')),
        'request_type' => $faker->word(),
        'bundle_sid' => $faker->regexify('BU[A-Z0-9]{16}'),
        'object_sid' => $faker->regexify('BU[A-Z0-9]{16}'),
        'status' => $faker->randomElement(Status::getConstants()),
        'response' => [],
        'error' => false,
    ];
});
