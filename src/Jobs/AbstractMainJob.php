<?php

namespace PerfectDayLlc\TwilioA2PBundle\Jobs;

use PerfectDayLlc\TwilioA2PBundle\Entities\ClientData;
use PerfectDayLlc\TwilioA2PBundle\Services\RegisterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

abstract class AbstractMainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected RegisterService $registerService;

    protected ClientData $client;

    public function __construct(RegisterService $registerService, ClientData $client)
    {
        $this->registerService = $registerService;

        $this->client = $client;
    }
}
