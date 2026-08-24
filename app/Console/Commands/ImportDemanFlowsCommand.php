<?php

namespace App\Console\Commands;

use App\Testing\DemanFlowImporter;
use Illuminate\Console\Command;

class ImportDemanFlowsCommand extends Command
{
    protected $signature = 'deman-flows:import
        {--overwrite : Replace existing deman flows}';

    protected $description = 'Import demanufacture prompt checklists into the database from config/deman_prompts.php';

    public function handle(DemanFlowImporter $importer): int
    {
        try {
            $imported = $importer->importFromConfig(overwrite: (bool) $this->option('overwrite'));
        } catch (\Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Imported deman flow(s):');
        foreach ($imported as $slug) {
            $this->line(' - '.$slug);
        }

        return self::SUCCESS;
    }
}
