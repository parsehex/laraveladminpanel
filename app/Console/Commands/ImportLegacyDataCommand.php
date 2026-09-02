<?php

namespace App\Console\Commands;

use App\Legacy\LegacyDumpReader;
use App\Legacy\LegacyIdMapRepository;
use App\Legacy\LegacyImportContext;
use App\Legacy\LegacyImportOrchestrator;
use App\Legacy\LegacyImportReport;
use App\Legacy\LegacyImportReporter;
use App\Legacy\LegacyImportReset;
use App\Legacy\LegacyImportVerifier;
use App\Legacy\LegacyPatchLoader;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportLegacyDataCommand extends Command
{
    protected $signature = 'legacy:import
        {--data= : Path to legacy pg_dump data SQL file}
        {--report-only : Scan dump and emit patch suggestions without writing}
        {--dry-run : Run transforms without committing DB changes}
        {--reset : Truncate appliance-world tables before import}
        {--mode=sync : Import mode (sync only for now)}
        {--only= : Import a single legacy table key}
        {--verify : Verify imported row counts and KPI coverage}
        {--allow-unresolved : Continue when user patches cannot be resolved}';

    protected $description = 'Import legacy appliance-world data from a pg_dump data file';

    public function handle(
        LegacyPatchLoader $patchLoader,
        LegacyIdMapRepository $idMap,
        LegacyImportReset $reset,
    ): int {
        if ($this->option('verify')) {
            return $this->runVerify($patchLoader);
        }

        $dataPath = $this->resolveDataPath();
        if ($dataPath === null) {
            return self::FAILURE;
        }

        $dump = new LegacyDumpReader($dataPath);

        if ($this->option('report-only')) {
            return $this->runReportOnly($dump, $patchLoader);
        }

        return $this->runImport($dump, $patchLoader, $idMap, $reset);
    }

    private function resolveDataPath(): ?string
    {
        $data = $this->option('data');
        if ($data === null) {
            $manifestPath = database_path('legacy-import/manifest.json');
            if (is_file($manifestPath)) {
                $manifest = json_decode(File::get($manifestPath), true);
                $data = is_array($manifest) ? ($manifest['data'] ?? null) : null;
            }
        }

        if ($data === null) {
            $this->error('Provide --data=path/to/data.sql or create database/legacy-import/manifest.json');

            return null;
        }

        $path = str_starts_with($data, '/') ? $data : base_path($data);
        if (! is_readable($path)) {
            $this->error("Dump not readable: {$path}");

            return null;
        }

        return $path;
    }

    private function runReportOnly(LegacyDumpReader $dump, LegacyPatchLoader $patchLoader): int
    {
        $report = (new LegacyImportReporter($dump, $patchLoader))->build();
        $reportPath = $this->writeReport($report, $dump);

        $this->info('Report-only scan complete.');
        $this->line("Report: {$reportPath}");

        $users = $report->toArray()['sections']['users'] ?? [];
        $missing = $users['missing_patches'] ?? [];
        if ($missing !== []) {
            $this->warn('Missing user patches: '.implode(', ', $missing));
        }

        $orphans = $report->toArray()['sections']['models']['orphan_model_numbers'] ?? [];
        if ($orphans !== []) {
            $this->line('Orphan model numbers: '.count($orphans));
        }

        foreach ($report->toArray()['warnings'] as $warning) {
            $this->warn($warning);
        }

        return self::SUCCESS;
    }

    private function runImport(
        LegacyDumpReader $dump,
        LegacyPatchLoader $patchLoader,
        LegacyIdMapRepository $idMap,
        LegacyImportReset $reset,
    ): int {
        $dryRun = (bool) $this->option('dry-run');
        $strict = ! $this->option('allow-unresolved');
        $report = new LegacyImportReport;

        $patches = [
            'users' => $patchLoader->load('users'),
            'categories' => $patchLoader->load('categories'),
            'models' => $patchLoader->load('models'),
            'testing' => $patchLoader->load('testing'),
        ];

        $runId = null;
        if (! $dryRun) {
            $runId = DB::table('legacy_import_runs')->insertGetId([
                'dump_path' => $dump->path(),
                'dump_sha256' => $dump->sha256(),
                'mode' => $this->option('mode'),
                'dry_run' => false,
                'patch_hashes' => json_encode($patchLoader->hashes()),
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $context = LegacyImportContext::make(
            dump: $dump,
            patchLoader: $patchLoader,
            idMap: $idMap,
            patches: $patches,
            report: $report,
            dryRun: $dryRun,
            strict: $strict,
            runId: $runId,
        );

        $orchestrator = LegacyImportOrchestrator::default($idMap, $reset);

        try {
            $orchestrator->import(
                context: $context,
                reset: (bool) $this->option('reset'),
                only: $this->option('only'),
            );
        } catch (\Throwable $exception) {
            $report->error($exception->getMessage());
            $this->writeReport($report, $dump);
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $reportPath = $this->writeReport($report, $dump);
        $this->info($dryRun ? 'Dry run complete.' : 'Import complete.');
        $this->line("Report: {$reportPath}");

        foreach ($report->toArray()['tables'] as $table => $stats) {
            $this->line(sprintf(
                '  %s: read=%d inserted=%d updated=%d skipped=%d',
                $table,
                $stats['read'],
                $stats['inserted'],
                $stats['updated'],
                $stats['skipped'],
            ));
        }

        if (! $dryRun && $runId !== null) {
            DB::table('legacy_import_runs')->where('id', $runId)->update([
                'finished_at' => now(),
                'report_path' => $reportPath,
                'summary' => json_encode($report->toArray()),
                'updated_at' => now(),
            ]);
        }

        return self::SUCCESS;
    }

    private function runVerify(LegacyPatchLoader $patchLoader): int
    {
        $dump = null;
        $dataPath = $this->resolveDataPath();
        if ($dataPath !== null) {
            $dump = new LegacyDumpReader($dataPath);
        }

        $report = (new LegacyImportVerifier)->verify($dump);
        $reportPath = $this->writeReport($report, $dump);

        $this->info('Verification complete.');
        $this->line("Report: {$reportPath}");

        foreach ($report->toArray()['warnings'] as $warning) {
            $this->warn($warning);
        }

        return self::SUCCESS;
    }

    private function writeReport(LegacyImportReport $report, ?LegacyDumpReader $dump): string
    {
        $stamp = now()->format('Y-m-d_His');
        $directory = storage_path("legacy-import/reports/{$stamp}");
        File::ensureDirectoryExists($directory);

        if ($dump !== null) {
            File::put($directory.'/dump.txt', $dump->path()."\n".$dump->sha256());
        }

        return $report->write($directory);
    }
}
