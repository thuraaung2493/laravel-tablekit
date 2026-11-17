<?php

namespace TableKitDemo\Console;

use Illuminate\Console\Scheduling\Schedule;

class Kernel extends \Illuminate\Foundation\Console\Kernel
{
    protected function commands()
    {
        $this->load(__DIR__);
    }
}
