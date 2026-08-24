<?php

namespace Database\Seeders;

use App\Testing\DemanFlowImporter;
use App\Testing\TestingFlowImporter;
use Illuminate\Database\Seeder;

/**
 * Seeds flow definitions into the DB from committed seed sources.
 * Does not leave the app depending on those files at runtime.
 */
class FlowSeeder extends Seeder
{
    public function run(): void
    {
        app(TestingFlowImporter::class)->importFromJsonTemplates(overwrite: false);
        app(DemanFlowImporter::class)->importFromConfig(overwrite: false);
    }
}
