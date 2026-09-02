<?php

namespace App\Legacy\Importers;

use App\Legacy\LegacyImportContext;

interface LegacyTableImporter
{
    public function legacyTable(): string;

    public function import(LegacyImportContext $context): void;
}
