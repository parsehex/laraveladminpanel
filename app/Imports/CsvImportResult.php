<?php

namespace App\Imports;

class CsvImportResult
{
    public function __construct(
        public readonly int $imported,
        public readonly int $updated,
    ) {}
}
