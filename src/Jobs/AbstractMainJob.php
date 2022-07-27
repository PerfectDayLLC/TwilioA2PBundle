<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;

abstract class AbstractMainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ClientData $client;

    public function __construct(ClientData $client)
    {
        $this->client = $client;
    }

    abstract public function handle(): void;
}
