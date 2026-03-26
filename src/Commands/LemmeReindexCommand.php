<?php

namespace Ranetrace\Lemme\Commands;

use Illuminate\Console\Command;
use Ranetrace\Lemme\Facades\Lemme;

class LemmeReindexCommand extends Command
{
    protected $signature = 'lemme:reindex {--clear : Clear cache before reindexing}';

    protected $description = 'Rebuild Lemme documentation caches (pages, HTML, search)';

    public function handle(): int
    {
        if ($this->option('clear')) {
            Lemme::clearCache();
            $this->info('Cleared existing Lemme caches.');
        }

        // Force rebuild pages & search data
        $pages = Lemme::getPages();
        $this->info('Indexed '.$pages->count().' pages.');

        // Warm HTML cache for each page (respect cache.enabled)
        if (config('lemme.cache.enabled')) {
            $bar = $this->output->createProgressBar($pages->count());
            foreach ($pages as $page) {
                Lemme::getPageHtml($page['slug']);
                $bar->advance();
            }
            $bar->finish();
            $this->newLine();
            $this->info('HTML cache warmed.');
        }

        $this->info('Search index ready ('.count(Lemme::getSearchData()).' entries).');

        return self::SUCCESS;
    }
}
