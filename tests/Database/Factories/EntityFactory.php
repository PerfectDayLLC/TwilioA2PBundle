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
        return [];
    }
}
