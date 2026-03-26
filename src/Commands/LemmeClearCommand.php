<?php

namespace Ranetrace\Lemme\Commands;

use Illuminate\Console\Command;
use Ranetrace\Lemme\Facades\Lemme;

class LemmeClearCommand extends Command
{
    public $signature = 'lemme:clear';

    public $description = 'Clear Lemme documentation cache';

    public function handle(): int
    {
        Lemme::clearCache();

        $this->info('Documentation cache cleared successfully!');

        return self::SUCCESS;
    }
}
