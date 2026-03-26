<?php

namespace Ranetrace\Lemme\Commands;

use Illuminate\Console\Command;

class LemmePublishCommand extends Command
{
    public $signature = 'lemme:publish {--force : Overwrite existing assets}';

    public $description = 'Publish or update Lemme assets (CSS, JS, etc.)';

    public function handle(): int
    {
        $this->info('Publishing Lemme assets...');

        $publishArgs = [
            '--tag' => 'lemme-assets',
        ];

        if ($this->option('force')) {
            $publishArgs['--force'] = true;
        }

        $result = $this->call('vendor:publish', $publishArgs);

        if ($result === 0) {
            $this->info('Lemme assets published successfully!');

            if ($this->option('force')) {
                $this->line('<comment>Assets were overwritten with the latest versions.</comment>');
            } else {
                $this->line('<comment>Use --force to overwrite existing assets with newer versions.</comment>');
            }

            $this->newLine();
            $this->line('Assets published to: public/vendor/lemme/');
        } else {
            $this->error('Failed to publish Lemme assets.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
