<?php

namespace App\Console\Commands;

use App\Testing\TestingFlowImporter;
use Illuminate\Console\Command;

class ImportTestingFlowsCommand extends Command
{
    protected $signature = 'testing-flows:import
        {path? : Optional path to legacy TestingConfig.php (otherwise seeds from resources/testing-flows JSON)}
        {--overwrite : Replace existing database flows without bumping version}';

    protected $description = 'Import testing flows into the database from JSON templates or a legacy PHP config';

    public function handle(TestingFlowImporter $importer): int
    {
        $path = $this->argument('path');
        $overwrite = (bool) $this->option('overwrite');

        try {
            if ($path) {
                if (! is_file($path)) {
                    $fallback = '/Users/user/Code/Code/my-adminlte-site/lib/TestingConfig.php';
                    $path = is_file($fallback) ? $fallback : $path;
                }

                if (! is_file($path)) {
                    $this->error("Legacy config not found at {$path}");

                    return self::FAILURE;
                }

                $imported = $importer->importFromPhpConfig($path, $overwrite);
                $this->info('Imported from PHP config:');
            } else {
                $imported = $importer->importFromJsonTemplates($overwrite);
                $this->info('Imported from JSON templates:');
            }
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($imported as $slug) {
            $this->line(' - '.$slug);
        }

        return self::SUCCESS;
    }
}
