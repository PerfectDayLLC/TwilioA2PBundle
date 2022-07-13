<?php

namespace PerfectDayLlc\TwilioA2PBundle\Console;

use Closure;
use Illuminate\Console\Command;

abstract class AbstractCommand extends Command
{
    public function withProgressBar($totalSteps, Closure $callback): void
    {
        $bar = $this->output->createProgressBar($totalSteps);

        $bar->start();

        $callback($bar);

        $bar->finish();
    }
}
