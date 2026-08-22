<?php

namespace App\Console\Commands;

use App\Testing\TestingFlowImporter;
use Illuminate\Console\Command;

class ImportTestingFlowsCommand extends Command
{
    protected $signature = 'testing-flows:import
        {path? : Path to legacy TestingConfig.php}
        {--overwrite : Overwrite existing JSON templates}

    protected $description = 'Import testing flows from the legacy PHP config into JSON templates';

    public function handle(TestingFlowImporter $importer): int
    {
        $path = $this->argument('path')
            ?: base_path('../Code/my-adminlte-site/lib/TestingConfig.php');

        if (! is_file($path)) {
            $fallback = '/Users/user/Code/Code/my-adminlte-site/lib/TestingConfig.php';
            $path = is_file($fallback) ? $fallback : $path;
        }

        if (! is_file($path)) {
            $this->error("Legacy config not found at {$path}");

            return self::FAILURE;
        }

        $imported = $importer->importFromPhpConfig($path, (bool) $this->option('overwrite'));
        $this->info('Imported '.count($imported).' flow(s):');
        foreach ($imported as $slug) {
            $this->line(' - '.$slug);
        }

        return self::SUCCESS;
    }
}
