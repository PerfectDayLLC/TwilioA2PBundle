<?php

namespace PerfectDayLlc\TwilioA2PBundle\Tests\Fake\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use PerfectDayLlc\TwilioA2PBundle\Tests\Database\Factories\EntityFactory;

class Entity extends Model
{
    use HasFactory;

    public static function newFactory(): EntityFactory
    {
        return new EntityFactory();
    }
}
