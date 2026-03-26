<?php

namespace Ranetrace\Lemme\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Ranetrace\Lemme\Facades\Lemme;

class LemmeInstallCommand extends Command
{
    protected $signature = 'lemme:install {--force : Force overwrite existing files}';

    protected $description = 'Install Lemme documentation system with sample files';

    public function handle(): int
    {
        $this->info('Installing Lemme documentation system...');

        $docsDirectory = base_path('docs');

        if (! File::exists($docsDirectory)) {
            File::makeDirectory($docsDirectory, 0755, true);
            $this->info('Created docs directory');
        } else {
            $this->info("Directory already exists: {$docsDirectory}");
        }

        $this->createSampleFiles($docsDirectory);

        // Always force asset publishing during install to ensure latest assets
        $this->call('vendor:publish', [
            '--tag' => 'lemme-assets',
            '--force' => true,
        ]);

        $this->info('Published Lemme assets (forced to ensure latest version)');

        // Clear cache if enabled
        if (config('lemme.cache.enabled')) {
            Lemme::clearCache();
            $this->info('Cleared documentation cache');
        }

        // Determine the correct URL based on configuration
        $subdomain = config('lemme.subdomain');
        $routePrefix = config('lemme.route_prefix');

        if ($subdomain && ! $routePrefix) {
            // Using subdomain routing with consistent domain calculation
            $protocol = \Ranetrace\Lemme\Lemme::baseScheme();
            $host = \Ranetrace\Lemme\Lemme::baseHost();
            $lemmeDomain = $protocol.'://'.$subdomain.'.'.$host;
        } elseif ($routePrefix) {
            // Using route prefix (default)
            $lemmeDomain = url($routePrefix);
        } else {
            // Fallback to default docs prefix
            $lemmeDomain = url('docs');
        }

        $this->newLine();
        $this->info('✅ Lemme documentation system installed successfully!');
        $this->info('📚 Your docs are located in: docs/');
        $this->info('🌐 Your documentation will be served at: '.$lemmeDomain);

        $this->newLine();
        $this->line('Next steps:');
        $this->line('1. Access the sample documentation at: '.$lemmeDomain.' to see how it works');
        $this->line('2. Add your markdown file based documentation to the docs/ directory in your Laravel project');
        $this->line('3. Optionally configure subdomain routing in config/lemme.php if you prefer docs.yoursite.com');

        $this->newLine();
        $this->line('<comment>Tip: After updating Lemme via Composer, run "php artisan lemme:publish --force" to update assets.</comment>');
        $this->line('<comment>Tip: Use numbered directory and file names (e.g., 1_welcome, 2_api) to organize content.</comment>');
        $this->line('<comment>Tip: Pages are automatically cached the first time they are accessed for better performance.</comment>');

        return self::SUCCESS;
    }

    protected function createSampleFiles(string $docsDirectory): void
    {
        // Copy sample markdown files from package resources
        $this->copyMarkdownFiles($docsDirectory);
    }

    protected function copyMarkdownFiles(string $docsDirectory): void
    {
        $stubsPath = __DIR__.'/../../stubs';

        // Copy index.md
        $indexSource = $stubsPath.'/index.md';
        $indexDest = $docsDirectory.'/index.md';
        if (File::exists($indexSource) && (! File::exists($indexDest) || $this->option('force'))) {
            File::copy($indexSource, $indexDest);
            $this->info('Created: index.md');
        }

        // Create welcome directory and copy files
        $welcomeDir = $docsDirectory.'/1_welcome';
        if (! File::exists($welcomeDir)) {
            File::makeDirectory($welcomeDir, 0755, true);
            $this->info('Created directory: 1_welcome/');
        }

        $welcomeFiles = [
            '1_getting-started.md',
            '2_organizing-content.md',
        ];

        foreach ($welcomeFiles as $filename) {
            $source = $stubsPath.'/1_welcome/'.$filename;
            $dest = $welcomeDir.'/'.$filename;
            if (File::exists($source) && (! File::exists($dest) || $this->option('force'))) {
                File::copy($source, $dest);
                $this->info("Created: 1_welcome/{$filename}");
            }
        }
    }
}
