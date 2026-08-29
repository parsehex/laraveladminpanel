<?php

namespace App\Legacy;

use App\Legacy\Importers\AppliancePartsImporter;
use App\Legacy\Importers\BrandsImporter;
use App\Legacy\Importers\CustomSalesImporter;
use App\Legacy\Importers\InventoryStatusHistoriesImporter;
use App\Legacy\Importers\LegacyTableImporter;
use App\Legacy\Importers\ModelPartsImporter;
use App\Legacy\Importers\ModelsImporter;
use App\Legacy\Importers\PartsImporter;
use App\Legacy\Importers\RepairDiagnosesImporter;
use App\Legacy\Importers\SubcategoriesImporter;
use App\Legacy\Importers\SuggestionsImporter;
use App\Legacy\Importers\TestingResultsImporter;
use App\Legacy\Importers\TruckAppliancesImporter;
use App\Legacy\Importers\TrucksImporter;
use App\Legacy\Importers\UserActionsImporter;
use Illuminate\Support\Facades\DB;

class LegacyImportOrchestrator
{
    /** @var array<string, LegacyTableImporter> */
    private array $importers;

    public function __construct(
        private readonly LegacyIdMapRepository $idMap,
        private readonly LegacyImportReset $reset,
        LegacyTableImporter ...$importers,
    ) {
        $this->importers = collect($importers)
            ->keyBy(fn (LegacyTableImporter $importer) => $importer->legacyTable())
            ->all();
    }

    public static function default(
        LegacyIdMapRepository $idMap,
        LegacyImportReset $reset,
    ): self {
        return new self(
            $idMap,
            $reset,
            new BrandsImporter,
            new SubcategoriesImporter,
            new TrucksImporter,
            new ModelsImporter,
            new PartsImporter,
            new ModelPartsImporter,
            new TruckAppliancesImporter,
            new AppliancePartsImporter,
            new InventoryStatusHistoriesImporter,
            new UserActionsImporter,
            new TestingResultsImporter,
            new RepairDiagnosesImporter,
            new SuggestionsImporter,
            new CustomSalesImporter,
        );
    }

    /**
     * @return list<string>
     */
    public function tables(): array
    {
        return array_keys($this->importers);
    }

    public function import(
        LegacyImportContext $context,
        bool $reset = false,
        ?string $only = null,
    ): LegacyImportReport {
        if ($reset) {
            $this->reset->run($context->dryRun);
        }

        $importers = $this->resolveImporters($only);

        foreach ($importers as $importer) {
            DB::transaction(function () use ($importer, $context): void {
                $importer->import($context);
            });
        }

        return $context->report;
    }

    /**
     * @return list<LegacyTableImporter>
     */
    private function resolveImporters(?string $only): array
    {
        if ($only === null) {
            return array_values($this->importers);
        }

        if (! isset($this->importers[$only])) {
            throw new \InvalidArgumentException("Unknown importer table [{$only}]");
        }

        return [$this->importers[$only]];
    }
}
